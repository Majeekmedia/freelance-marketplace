@extends('layouts.app')

@section('title', 'Your jobs')

@section('content')
<div class="page-head">
    <div>
        <h1>Your jobs</h1>
        <p class="sub">Drafts are private. A job goes public once its budget is funded.</p>
    </div>
    <a class="btn" href="{{ route('jobs.manage.create') }}">Post a job</a>
</div>

@if ($jobs->isEmpty())
    <div class="card" style="text-align:center;padding:3rem 1.5rem;margin-top:1.5rem">
        <p style="margin:0 0 1rem;color:var(--ink-3)">No jobs yet.</p>
        <a class="btn" href="{{ route('jobs.manage.create') }}">Post your first job</a>
    </div>
@else
    {{--
        Card list, not a table. On a phone the action buttons and the budget must
        stay reachable; a five-column table pushes them off-screen.
    --}}
    <div class="cardlist" style="margin-top:1.5rem">
        @foreach ($jobs as $job)
            <div class="card job-row">
                <div class="job-row-main">
                    <div style="min-width:0">
                        <a href="{{ route('jobs.manage.show', $job) }}" class="job-row-title">{{ $job->title }}</a>
                        <div class="job-row-meta">
                            <span class="badge badge-{{ $job->status->tone() }}">{{ $job->status->label() }}</span>
                            <span class="job-row-dot" aria-hidden="true">·</span>
                            <span>{{ $job->category?->name ?? 'Uncategorised' }}</span>
                            <span class="job-row-dot" aria-hidden="true">·</span>
                            <span>{{ $job->created_at->format('j M Y') }}</span>
                        </div>
                    </div>
                    <div class="job-row-money">{{ $job->budget()->format() }}</div>
                </div>

                @if ($job->status->isEditable() || $job->isDraft())
                    <div class="job-row-actions">
                        @if ($job->status->isEditable())
                            <a class="btn btn-secondary btn-sm" href="{{ route('jobs.manage.edit', $job) }}">Edit</a>
                        @endif
                        @if ($job->isDraft())
                            <form method="POST" action="{{ route('jobs.manage.destroy', $job) }}" style="margin:0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($jobs->hasPages())
        <div style="margin-top:1.5rem">{{ $jobs->links() }}</div>
    @endif
@endif
@endsection

@push('styles')
<style>
    .job-row{display:flex;align-items:center;gap:1.25rem}
    .job-row-main{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex:1;min-width:0}
    .job-row-title{font-weight:500;font-size:.98rem;display:block}
    .job-row-title:hover{color:var(--forest)}
    .job-row-meta{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;font-size:.78rem;color:var(--ink-3);margin-top:.35rem}
    .job-row-dot{color:var(--rule-2)}
    .job-row-money{font-family:var(--serif);font-size:1.2rem;flex:none;font-variant-numeric:tabular-nums}
    .job-row-actions{display:flex;gap:.4rem;flex:none;flex-wrap:wrap}

    /* Stack on narrow screens: money above, actions full width below. */
    @media (max-width:700px){
        .job-row{flex-direction:column;align-items:stretch;gap:.9rem}
        .job-row-main{flex-direction:column;align-items:flex-start;gap:.5rem}
        .job-row-money{font-size:1.3rem}
        .job-row-actions{border-top:1px solid var(--rule);padding-top:.85rem}
        .job-row-actions .btn{flex:1}
        .job-row-actions form{flex:1;display:flex}
        .job-row-actions form .btn{width:100%}
    }
</style>
@endpush
