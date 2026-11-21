<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepositRequest;
use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Services\LedgerService;
use App\Support\DepositProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * Poster deposit requests (FR-039).
 *
 * The flow this controller implements:
 *   1. poster sees only *enabled* methods, with the admin's real details
 *   2. they transfer money outside the app
 *   3. they submit amount + method + reference + proof
 *   4. the request is `pending` and the balance is UNCHANGED
 *
 * Step 4 is the point. Approving happens in `Admin\DepositController` and is
 * the only place a `deposit_credit` row is written.
 */
class DepositController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * The funding page: balance, enabled methods, and past requests.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $deposits = DepositRequest::query()
            ->forUser($user->id)
            ->with('paymentMethod')
            ->latest()
            ->paginate(10);

        return view('wallet.deposits.index', [
            'methods' => PaymentMethod::query()->enabled()->get(),
            'deposits' => $deposits,
            'available' => $this->ledger->available($user),
            'pendingTotal' => $this->pendingTotalFor($user->id),
        ]);
    }

    /**
     * Submit a deposit request.
     *
     * Wrapped in a transaction for one reason: the file write and the row insert
     * must not diverge. If the insert fails after the upload succeeded, the file
     * is deleted rather than left orphaned in private storage forever.
     */
    public function store(StoreDepositRequest $request): RedirectResponse
    {
        $user = $request->user();
        $method = $request->paymentMethod();

        $path = DepositProof::store($request->file('proof'));

        try {
            DB::transaction(function () use ($request, $user, $method, $path): void {
                $deposit = new DepositRequest([
                    'payment_method_id' => $method->id,
                    'amount_requested_cents' => $request->amountCents(),
                    'transfer_reference' => (string) $request->input('transfer_reference'),
                    'proof_path' => $path,
                    // Frozen instructions (FR-039d): editing the method later
                    // must not rewrite what this poster was told to pay.
                    'method_snapshot' => $method->snapshot(),
                    'currency' => $method->currencyCode(),
                ]);

                $deposit->user_id = $user->id;
                $deposit->save();
            });
        } catch (\Throwable $e) {
            // The row did not save, so the file must not linger.
            DepositProof::remove($path);

            throw $e;
        }

        return redirect()
            ->route('wallet.deposits.index')
            ->with('status', 'Deposit submitted. It appears in your wallet once an admin confirms it.');
    }

    /** A poster's own request detail. */
    public function show(Request $request, DepositRequest $deposit): View
    {
        if (! $request->user()->can('view', $deposit)) {
            abort(404);
        }

        $deposit->load(['paymentMethod', 'reviewer']);

        return view('wallet.deposits.show', [
            'deposit' => $deposit,
        ]);
    }

    /**
     * Stream the proof image.
     *
     * Authorised, then streamed through the app — there is no public URL for a
     * proof, by design (FR-039e). The policy is checked here rather than in
     * `DepositProof` so the access rule lives in exactly one place.
     */
    public function proof(Request $request, DepositRequest $deposit): StreamedResponse
    {
        if (! $request->user()->can('viewProof', $deposit)) {
            abort(404);
        }

        return DepositProof::response($deposit->proof_path, 'deposit-proof-'.$deposit->id);
    }

    /** Total still awaiting review, so the poster knows what is in flight. */
    private function pendingTotalFor(int $userId): \App\Domain\Money
    {
        $cents = (int) DepositRequest::query()
            ->forUser($userId)
            ->pending()
            ->sum('amount_requested_cents');

        return \App\Domain\Money::fromMinorUnits($cents);
    }
}
