<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\JobStatus;
use App\Http\Requests\JobRequest;
use App\Models\Category;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Poster's own jobs (FR-009).
 *
 * Every action resolves an actual job and authorises against it. The index is
 * scoped to the owner in the query, not filtered in the view.
 *
 * Note what is missing: there is no `publish` action. A poster cannot make a
 * job public here, because public means "budget is in escrow", and that is a
 * money operation belonging to EscrowService (T-014).
 */
class JobManageController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = Job::query()
            ->ownedBy($request->user())
            ->with('category')
            ->latest()
            ->paginate(15);

        return view('jobs.manage.index', [
            'jobs' => $jobs,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Job::class);

        return view('jobs.manage.create', [
            'categories' => Category::query()->active()->get(),
        ]);
    }

    public function store(JobRequest $request): RedirectResponse
    {
        $this->authorize('create', Job::class);

        $job = new Job($request->jobAttributes());
        $job->poster_id = $request->user()->id;
        $job->status = JobStatus::initial();
        $job->currency = 'USD';
        $job->save();

        return redirect()
            ->route('jobs.manage.show', $job)
            ->with('status', 'Draft saved. It is private until its budget is funded.');
    }

    public function show(Request $request, Job $job): View
    {
        // 404 rather than 403 for a stranger's draft, so ids cannot be probed.
        if (! $request->user()->can('view', $job)) {
            abort(404);
        }

        $job->load('category');

        return view('jobs.manage.show', [
            'job' => $job,
            'canEdit' => $request->user()->can('update', $job),
        ]);
    }

    public function edit(Request $request, Job $job): View
    {
        if (! $request->user()->can('update', $job)) {
            abort(404);
        }

        return view('jobs.manage.edit', [
            'job' => $job,
            'categories' => Category::query()->active()->get(),
        ]);
    }

    public function update(JobRequest $request, Job $job): RedirectResponse
    {
        if (! $request->user()->can('update', $job)) {
            abort(404);
        }

        $job->fill($request->jobAttributes());
        $job->save();

        return redirect()
            ->route('jobs.manage.show', $job)
            ->with('status', 'Job updated.');
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        if (! $request->user()->can('delete', $job)) {
            abort(404);
        }

        $job->delete();

        return redirect()
            ->route('jobs.manage.index')
            ->with('status', 'Draft deleted.');
    }
}
