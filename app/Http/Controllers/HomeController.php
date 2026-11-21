<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\JobStatus;
use App\Domain\LedgerKind;
use App\Domain\PaymentMethodType;
use App\Domain\UserType;
use App\Models\Job;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The public landing page.
 *
 * Two rules govern everything here:
 *
 * 1. **Every figure is derived.** No total is stored, estimated or rounded up.
 *    The prototype this page was built from carried "$128,400 in escrow",
 *    "12,480 freelancers" and a "4.8 average from 2,041 reviews" — all invented.
 *    A landing page claiming impressive traction is the first thing an
 *    experienced buyer checks, and being caught there costs more than the
 *    marketing gains.
 *
 * 2. **The job board shows real jobs or honestly shows none.** Only jobs whose
 *    budget is actually held in escrow appear, so the "Funded" badge is a fact
 *    rather than a claim.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'featuredJobs' => $this->featuredJobs(),
            'stats' => $this->stats(),
            'categories' => \App\Models\Category::query()->active()->limit(6)->get(),
        ]);
    }

    /**
     * The most recent publicly visible jobs.
     *
     * `publiclyVisible()` requires both a public status and an escrow row with
     * money in it, so nothing unfunded can reach the landing page.
     *
     * @return \Illuminate\Support\Collection<int, Job>
     */
    private function featuredJobs(): \Illuminate\Support\Collection
    {
        return Job::query()
            ->publiclyVisible()
            ->with(['category', 'poster'])
            ->latest('published_at')
            ->limit(6)
            ->get();
    }

    /**
     * Real counts for the hero.
     *
     * `in_escrow_cents` is a ledger sum: escrow holds minus releases and refunds.
     * It cannot disagree with the figure an admin sees in the console, because
     * both compute it the same way from the same rows.
     *
     * @return array{freelancers: int, posters: int, in_escrow_cents: int, jobs_funded: int, jobs_released: int}
     */
    private function stats(): array
    {
        $held = (int) DB::table('ledger_entries')
            ->where('kind', LedgerKind::ESCROW_HOLD->value)
            ->sum('amount_cents');

        $returned = (int) DB::table('ledger_entries')
            ->whereIn('kind', [
                LedgerKind::ESCROW_RELEASE_CREDIT->value,
                LedgerKind::ESCROW_REFUND->value,
            ])
            ->sum('amount_cents');

        return [
            'freelancers' => User::query()
                ->where('type', UserType::FREELANCER->value)
                ->approved()
                ->count(),
            'posters' => User::query()
                ->where('type', UserType::POSTER->value)
                ->approved()
                ->count(),
            'in_escrow_cents' => max(0, $held - $returned),
            'jobs_funded' => Job::query()->publiclyVisible()->count(),
            'jobs_released' => Job::query()
                ->where('status', JobStatus::COMPLETED->value)
                ->count(),
        ];
    }
}
