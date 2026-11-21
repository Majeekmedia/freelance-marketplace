<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\DepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveDepositRequest;
use App\Http\Requests\RejectDepositRequest;
use App\Models\DepositRequest;
use App\Services\DepositApprovalService;
use App\Services\LedgerService;
use App\Support\DepositProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin deposit review queue (FR-040).
 *
 * Approving is the only place in the product where money is created from
 * outside. Everything here defers the actual money movement to
 * `DepositApprovalService` so there is exactly one implementation of that rule.
 */
class DepositController extends Controller
{
    public function __construct(
        private readonly DepositApprovalService $approvals,
        private readonly LedgerService $ledger,
    ) {
    }

    /** The queue: pending first, then recent decisions. */
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');

        $query = DepositRequest::query()->with(['user', 'paymentMethod']);

        if ($status === 'pending') {
            $query->awaitingReview();
        } elseif ($status === 'approved') {
            $query->where('status', DepositStatus::APPROVED->value)->latest('reviewed_at');
        } elseif ($status === 'rejected') {
            $query->where('status', DepositStatus::REJECTED->value)->latest('reviewed_at');
        } else {
            $query->latest();
        }

        return view('admin.deposits.index', [
            'deposits' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'pendingCount' => DepositRequest::pending()->count(),
        ]);
    }

    public function show(DepositRequest $deposit): View
    {
        $deposit->load(['user', 'paymentMethod', 'reviewer']);

        return view('admin.deposits.show', [
            'deposit' => $deposit,
            'posterBalance' => $this->ledger->available($deposit->user),
        ]);
    }

    /** Stream the proof to an admin. Authorised by the admin gate on the route. */
    public function proof(DepositRequest $deposit): StreamedResponse
    {
        return DepositProof::response($deposit->proof_path, 'deposit-proof-'.$deposit->id);
    }

    public function approve(ApproveDepositRequest $request, DepositRequest $deposit): RedirectResponse
    {
        /*
         * Admin-ness is checked here; "is it still pending" is NOT.
         *
         * The policy also requires `isPending()`, but calling it first would
         * produce a bare 403 "unauthorized" for the most common real case: two
         * admins opening the same queue, or one double-clicking. That is not an
         * authorisation problem, it is a state problem, and the person needs to
         * be told which. The service re-checks under a row lock, so the money is
         * protected regardless of the order here.
         */
        abort_unless($request->user()->isAdmin(), 404);

        try {
            $this->approvals->approve(
                admin: $request->user(),
                deposit: $deposit,
                credited: $request->creditedAmount(),
                reason: (string) $request->input('reason'),
                ipAddress: $request->ip(),
            );
        } catch (\DomainException $e) {
            // Already decided — say so, rather than 500 or a bare 403.
            return back()->withErrors(['deposit' => $e->getMessage()]);
        }

        return back()->with('status', 'Deposit approved and the wallet credited.');
    }

    public function reject(RejectDepositRequest $request, DepositRequest $deposit): RedirectResponse
    {
        // Same ordering as approve(): admin here, pending state in the service.
        abort_unless($request->user()->isAdmin(), 404);

        try {
            $this->approvals->reject(
                admin: $request->user(),
                deposit: $deposit,
                reason: (string) $request->input('reason'),
                ipAddress: $request->ip(),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['deposit' => $e->getMessage()]);
        }

        return back()->with('status', 'Deposit rejected. Nothing was credited.');
    }
}
