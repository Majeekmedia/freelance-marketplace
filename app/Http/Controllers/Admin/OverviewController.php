<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\DepositStatus;
use App\Domain\JobStatus;
use App\Domain\LedgerKind;
use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\Job;
use App\Models\LedgerEntry;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin overview (FR-002).
 *
 * Every number here is a database count. Nothing is illustrative, estimated or
 * hardcoded — an operator who acts on a wrong queue depth stops trusting the
 * console, and then stops using it.
 *
 * Where a figure would be misleading at this stage of the build, the page says
 * so rather than showing a zero that looks like a fact.
 */
class OverviewController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.overview', [
            'queues' => $this->queues(),
            'money' => $this->money(),
            'accounts' => $this->accounts(),
            'recent' => $this->recentActivity(),
            'notYet' => $this->notYetBuilt(),
        ]);
    }

    /**
     * Work waiting for a human.
     *
     * @return array<string, array{label: string, count: int, route: string|null, hint: string}>
     */
    private function queues(): array
    {
        return [
            'deposits' => [
                'label' => 'Deposits awaiting review',
                'count' => DepositRequest::pending()->count(),
                'route' => 'admin.deposits.index',
                'hint' => 'Money does not exist until these are approved.',
            ],
            'accounts' => [
                'label' => 'Accounts awaiting approval',
                'count' => User::pending()->count(),
                'route' => 'admin.users.index',
                'hint' => 'A pending account can browse but cannot act.',
            ],
            'drafts' => [
                'label' => 'Job drafts (not public)',
                'count' => Job::where('status', JobStatus::DRAFT->value)->count(),
                'route' => null,
                'hint' => 'Poster work in progress. Visible to its owner only.',
            ],
        ];
    }

    /**
     * Money at rest, derived from the ledger rather than stored.
     *
     * These are sums over ledger rows, so they cannot drift from the balances
     * users see — which is the point of the ledger design.
     *
     * @return array<string, int>
     */
    private function money(): array
    {
        $byKind = LedgerEntry::query()
            ->selectRaw('kind, direction, SUM(amount_cents) as total')
            ->groupBy('kind', 'direction')
            ->get();

        $credits = 0;
        $inEscrow = 0;

        foreach ($byKind as $row) {
            $total = (int) $row->total;
            $kind = $row->kind;

            if ($kind instanceof LedgerKind) {
                if ($kind === LedgerKind::DEPOSIT_CREDIT || $kind === LedgerKind::ADMIN_CREDIT) {
                    $credits += $total;
                }

                if ($kind === LedgerKind::ESCROW_HOLD) {
                    $inEscrow += $total;
                }
            }
        }

        // Released/refunded escrow reduces what is still held.
        $released = (int) DB::table('ledger_entries')
            ->whereIn('kind', [
                LedgerKind::ESCROW_RELEASE_CREDIT->value,
                LedgerKind::ESCROW_REFUND->value,
            ])
            ->sum('amount_cents');

        return [
            'deposited_cents' => $credits,
            'escrow_held_cents' => max(0, $inEscrow - $released),
            'ledger_rows' => LedgerEntry::count(),
        ];
    }

    /**
     * Account totals by type and status.
     *
     * @return array<string, int>
     */
    private function accounts(): array
    {
        return [
            'total' => User::count(),
            'freelancers' => User::where('type', 'freelancer')->count(),
            'posters' => User::where('type', 'poster')->count(),
            'admins' => User::where('type', 'admin')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];
    }

    /**
     * The last few decisions, so an operator can see what just happened without
     * navigating. Reads the audit log rather than the deposits table, because a
     * decision and its record should agree.
     */
    private function recentActivity(): \Illuminate\Support\Collection
    {
        return \App\Models\AuditLog::query()
            ->with('actor')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    /**
     * Things a reader might expect to see here but which are not built yet.
     *
     * Stated plainly. An empty panel labelled "Escrow" that never populates is
     * worse than a line saying it arrives with the milestone that creates it.
     *
     * @return list<string>
     */
    private function notYetBuilt(): array
    {
        return [
            'Releasing or refunding escrow — arrives with milestone M5.',
            'An audit-log browser — the data is recorded now, the viewer is M6.',
            'Withdrawal review — M6, once payouts exist.',
        ];
    }
}
