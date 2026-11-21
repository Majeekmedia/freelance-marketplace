<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A freelancer's own work, in two states: active and completed (FR-018).
 *
 * One controller with a `$state` argument rather than two near-identical classes.
 * The queries differ only in the status filter, and duplicating the page twice is
 * how the two views drift apart until one is missing a fix the other got.
 *
 * The distinction that matters:
 *   active    -> in_progress or delivered. The money has not moved.
 *   completed -> COMPLETED, meaning every milestone was released.
 *
 * "Delivered" is deliberately still active: work submitted is not work paid, and
 * telling a freelancer it is finished when the escrow is untouched would be the
 * most damaging thing this page could do.
 */
class FreelancerWorkController extends Controller
{
    public function active(Request $request): View
    {
        return $this->render($request, 'active');
    }

    public function completed(Request $request): View
    {
        return $this->render($request, 'completed');
    }

    private function render(Request $request, string $state): View
    {
        /** @var User $user */
        $user = $request->user();

        $jobs = $this->queryFor($user, $state)
            ->with(['category', 'poster'])
            ->latest()
            ->paginate(12);

        return view('freelancer.work.index', [
            'jobs' => $jobs,
            'state' => $state,
            'activeCount' => $this->queryFor($user, 'active')->count(),
            'completedCount' => $this->queryFor($user, 'completed')->count(),
        ]);
    }

    /** @return Builder<Job> */
    private function queryFor(User $user, string $state): Builder
    {
        $query = Job::query()->where('hired_freelancer_id', $user->id);

        return match ($state) {
            'active' => $query->whereIn('status', [
                JobStatus::IN_PROGRESS->value,
                JobStatus::DELIVERED->value,
            ]),
            'completed' => $query->where('status', JobStatus::COMPLETED->value),
            default => abort(404),
        };
    }
}
