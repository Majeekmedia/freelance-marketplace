<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A user's own wallet (FR-018).
 *
 * There is no `/wallet/{id}` route. The wallet shown is always the signed-in
 * user's, resolved from the session — so "change the id to see someone else's
 * money" has no id to change. That is a stronger guarantee than a policy check,
 * and it is why this controller takes no identifier at all.
 */
class WalletController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        $entries = $user->ledgerEntries()
            ->with(['job', 'actor'])
            ->latest('id')
            ->paginate(25);

        return view('wallet.show', [
            'summary' => $this->ledger->summary($user),
            'entries' => $entries,
        ]);
    }
}
