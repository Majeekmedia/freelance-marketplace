<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public job listing and detail (FR-009, FR-011).
 *
 * Both actions apply the model's `publiclyVisible` scope, so a draft cannot
 * leak through a filter, a page-2 link or a direct id. A job that is not
 * publicly visible produces 404 — never 403 — because a 403 confirms the id
 * exists.
 */
class JobController extends Controller
{
    public function index(Request $request): View
    {
        $query = Job::query()
            ->publiclyVisible()
            ->with(['category', 'poster', 'escrow']);

        if ($request->filled('category')) {
            $slug = (string) $request->query('category');

            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));

            if ($term !== '') {
                $query->where(function ($q) use ($term): void {
                    $q->where('title', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            }
        }

        return view('jobs.public.index', [
            'jobs' => $query->latest('published_at')->paginate(12)->withQueryString(),
            'categories' => Category::query()->active()->get(),
            'activeCategory' => $request->query('category'),
            'search' => $request->query('q'),
        ]);
    }

    public function show(Job $job): View
    {
        // Two conditions, same as the listing scope: a public status *and*
        // money actually held. Checking only the status would let a direct id
        // reach a page that claims escrow funding it does not have.
        if (! $job->status->isPubliclyVisible() || ! $job->isFunded()) {
            abort(404);
        }

        $job->load(['category', 'poster', 'milestones', 'escrow']);

        return view('jobs.public.show', [
            'job' => $job,
        ]);
    }
}
