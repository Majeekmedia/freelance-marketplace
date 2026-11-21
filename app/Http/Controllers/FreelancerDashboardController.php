<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\JobStatus;
use App\Domain\LedgerKind;
use App\Models\Job;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A freelancer's home (FR-018).
 *
 * The problem this solves: a signed-in freelancer whose only action is "edit your
 * profile" has a dashboard that feels empty and tells them nothing. Their real
 * questions are "is there work worth applying for", "what am I in the middle of",
 * and "what have I actually earned".
 *
 * So this page answers those three, in that order, and links out for depth:
 *   - open jobs, filtered to their profile skills where possible
 *   - active work and completed work, on their own pages
 *   - earnings, derived from the ledger
 *
 * Deliberately NOT here: applications and messaging. Neither exists yet, and an
 * empty "Applications" panel would claim a feature the product does not have.
 */
class FreelancerDashboardController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $user->freelancerProfile;

        return view('freelancer.dashboard', [
            /*
             * `$user` and `$isPending` are passed explicitly rather than read
             * from `auth()` inside the template, so this view works the same
             * whichever controller renders it and a missing variable becomes a
             * test failure instead of a blank page.
             */
            'user' => $user,
            'isPending' => $user->isPending(),
            'profile' => $profile,
            'openJobs' => $this->openJobsMatching($profile),
            /*
             * Whether there are ANY open jobs, so the empty state can tell the
             * truth. "No open jobs right now" when six exist but none match the
             * freelancer's skills is a false claim that hides real work from
             * them — the worst possible error on this page.
             */
            'totalOpenJobs' => Job::query()->publiclyVisible()->count(),
            'filteredBySkills' => ! empty($profile?->skills),
            'activeCount' => $this->activeJobs($user)->count(),
            'completedCount' => $this->completedJobs($user)->count(),
            'earnings' => $this->earnings($user),
            'profileIsPublic' => (bool) ($profile?->is_public),
            'profileComplete' => $this->profileComplete($profile),
        ]);
    }

    /**
     * Open jobs, preferring ones that mention the freelancer's skills.
     *
     * Not a sophisticated match: a LIKE against title and description. That is
     * honest about what it is, and it is more useful than an unfiltered list for
     * someone with a narrow specialism. If there is no profile or no skills, it
     * falls back to the newest open jobs.
     *
     * @return \Illuminate\Support\Collection<int, Job>
     */
    private function openJobsMatching(?object $profile): \Illuminate\Support\Collection
    {
        $query = Job::query()
            ->publiclyVisible()
            ->with(['category', 'poster'])
            ->latest('published_at')
            ->limit(6);

        $skills = array_slice((array) ($profile?->skills ?? []), 0, 5);

        if ($skills !== []) {
            $query->where(function ($q) use ($skills): void {
                foreach ($skills as $skill) {
                    $term = '%'.trim((string) $skill).'%';
                    $q->orWhere('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                }
            });
        }

        return $query->get();
    }

    /**
     * Jobs this freelancer is currently on.
     *
     * `hired_freelancer_id` is the only signal that exists today; delivery and
     * acceptance states arrive with M7.
     */
    private function activeJobs(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Job::query()
            ->where('hired_freelancer_id', $user->id)
            ->whereIn('status', [
                JobStatus::IN_PROGRESS->value,
                JobStatus::DELIVERED->value,
            ]);
    }

    /**
     * Jobs this freelancer has finished.
     *
     * A completed job is one whose status reached COMPLETED — meaning all
     * milestones were released. Anything merely "delivered" is still active,
     * because the money has not moved yet.
     */
    private function completedJobs(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Job::query()
            ->where('hired_freelancer_id', $user->id)
            ->where('status', JobStatus::COMPLETED->value);
    }

    /** What this freelancer has actually been paid, from the ledger. */
    private function earnings(User $user): \App\Domain\Money
    {
        return $this->ledger->lifetimeCredited($user);
    }

    /**
     * How complete the profile is, as a small checklist.
     *
     * Shown instead of a percentage. "60% complete" is a number a user cannot act
     * on; "add portfolio work" is.
     *
     * @return list<string> Missing items, empty when complete.
     */
    private function profileComplete(?object $profile): array
    {
        $missing = [];

        if ($profile === null) {
            return ['a headline', 'a short bio', 'your skills'];
        }

        if (blank($profile->headline)) {
            $missing[] = 'a headline';
        }

        if (blank($profile->bio)) {
            $missing[] = 'a short bio';
        }

        if (empty($profile->skills)) {
            $missing[] = 'your skills';
        }

        return $missing;
    }
}
