@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $user->name }}</h1>
        <p class="sub">
            Freelancer
            <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
            <span class="badge badge-{{ $user->status->value }}">{{ $user->status->label() }}</span>
        </p>
    </div>
    <a class="btn btn-secondary" href="{{ route('portfolio.index') }}">Manage portfolio</a>
</div>

@if ($isPending)
    <div class="alert alert-note">
        <strong>Your account is awaiting approval.</strong><br>
        You can browse and edit your profile. You cannot apply for work until an
        administrator approves you.
    </div>
@endif

{{-- Earnings first: it is the number a freelancer actually cares about. --}}
<div class="earn-strip">
    <div>
        <div class="earn-label">Paid to you so far</div>
        <div class="earn-value">{{ $earnings->format() }}</div>
    </div>
    <div class="earn-actions">
        <a class="btn btn-secondary btn-sm" href="{{ route('wallet.show') }}">Wallet</a>
        @if ($profileIsPublic)
            <a class="btn btn-secondary btn-sm" href="{{ route('freelancers.show', auth()->id()) }}">View public profile</a>
        @endif
    </div>
</div>

{{--
    Work in two states, each linking to its own page.

    This exists so the dashboard is not scanty: even with no active work the page
    shows that the concept exists, what the counts are, and where to look. An
    empty panel with no heading is the thing to avoid.
--}}
<div class="dash-grid">
    <a class="card dash-card" href="{{ route('freelancer.work.active') }}">
        <span class="dash-label">Active work</span>
        <span class="dash-number {{ $activeCount > 0 ? 'is-live' : '' }}">{{ $activeCount }}</span>
        <span class="dash-hint">
            @if ($activeCount > 0)
                Jobs you are currently on. Money is held in escrow until milestones are released.
            @else
                Nothing in progress. When a poster hires you, the job appears here.
            @endif
        </span>
        <span class="dash-go">See active work &rarr;</span>
    </a>

    <a class="card dash-card" href="{{ route('freelancer.work.completed') }}">
        <span class="dash-label">Completed</span>
        <span class="dash-number {{ $completedCount > 0 ? 'is-live' : '' }}">{{ $completedCount }}</span>
        <span class="dash-hint">
            @if ($completedCount > 0)
                Finished jobs, fully paid out.
            @else
                Completed jobs appear here once every milestone has been released.
            @endif
        </span>
        <span class="dash-go">See completed work &rarr;</span>
    </a>
</div>

{{-- The profile checklist, only while something is missing. --}}
@if ($profileComplete !== [])
    <div class="card notice-card">
        <div class="notice-body">
            <span class="notice-title">Your profile needs {{ implode(', ', $profileComplete) }}</span>
            <span class="notice-hint">
                Posters judge you from this before they ever message. A complete
                profile is the difference between being shortlisted and being skipped.
            </span>
        </div>
        <a class="btn btn-sm" href="{{ route('profile.edit') }}">Finish profile</a>
    </div>
@endif

{{-- Open jobs. Real listings, filtered to this freelancer's skills. --}}
<div class="jobs-head">
    <h2 class="section-head">Open jobs</h2>
    <a class="jobs-all" href="{{ route('jobs.index') }}">Browse all &rarr;</a>
</div>

@if ($openJobs->isEmpty())
    <div class="card empty-note">
        @if ($totalOpenJobs > 0 && $filteredBySkills)
            {{--
                The honest case: jobs DO exist, they just do not mention this
                freelancer's skills. Saying "no jobs" here would hide real work
                from the person who could apply for it.
            --}}
            <p style="margin:0 0 .3rem;font-weight:500">
                {{ $totalOpenJobs }} job{{ $totalOpenJobs === 1 ? '' : 's' }} open, none matching your skills
            </p>
            <p style="margin:0 0 1.1rem;font-size:.85rem;color:var(--ink-3);line-height:1.55">
                We filter by the skills on your profile. Browse everything to see
                whether any of them fit anyway.
            </p>
            <a class="btn btn-secondary btn-sm" href="{{ route('jobs.index') }}">See all open jobs</a>
        @else
            <p style="margin:0 0 .3rem;font-weight:500">No open jobs right now</p>
            <p style="margin:0;font-size:.85rem;color:var(--ink-3);line-height:1.55">
                Every job on the platform has its budget funded before it is listed, so
                this list is short by design. Check back soon.
            </p>
        @endif
    </div>
@else
    <div class="cardlist">
        @foreach ($openJobs as $job)
            <a class="card job-line" href="{{ route('jobs.show', $job) }}">
                <div class="job-line-main">
                    <div class="job-line-title">{{ $job->title }}</div>
                    <div class="job-line-meta">
                        {{ $job->category?->name ?? 'Uncategorised' }}
                        <span class="dot" aria-hidden="true">·</span>
                        {{ $job->poster?->name ?? 'A poster' }}
                        @if ($job->deadline)
                            <span class="dot" aria-hidden="true">·</span>
                            due {{ $job->deadline->format('j M') }}
                        @endif
                    </div>
                </div>
                <div class="job-line-amt">
                    {{ $job->budget()->format() }}
                    <small>In escrow</small>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection

@push('styles')
<style>
    .earn-strip{
        display:flex;align-items:center;justify-content:space-between;
        gap:1.25rem;flex-wrap:wrap;
        background:var(--forest-wash);border:1px solid #CBDDD4;border-radius:12px;
        padding:1.1rem 1.25rem;margin-bottom:1.25rem;
    }
    .earn-label{font-size:.7rem;letter-spacing:.07em;text-transform:uppercase;color:var(--forest);font-weight:600}
    .earn-value{font-family:var(--serif);font-size:2rem;line-height:1.1;margin-top:.15rem;color:var(--forest);font-variant-numeric:tabular-nums}
    .earn-actions{display:flex;gap:.45rem;flex-wrap:wrap}

    .dash-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:.75rem;margin-bottom:1.5rem}
    .dash-card{display:block;transition:border-color .15s ease,box-shadow .15s ease}
    .dash-card:hover{border-color:var(--rule-2);box-shadow:0 3px 14px rgba(22,20,15,.07)}
    .dash-card + .dash-card{margin-top:0}
    .dash-label{display:block;font-size:.7rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);font-weight:600}
    .dash-number{display:block;font-family:var(--serif);font-size:2rem;line-height:1.1;margin:.3rem 0 0;font-variant-numeric:tabular-nums}
    .dash-number.is-live{color:var(--forest)}
    .dash-hint{display:block;font-size:.78rem;color:var(--ink-3);margin-top:.45rem;line-height:1.5}
    .dash-go{display:block;font-size:.76rem;color:var(--forest);margin-top:.7rem}

    .notice-card{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
    .notice-body{min-width:0}
    .notice-title{display:block;font-weight:500;font-size:.9rem}
    .notice-hint{display:block;font-size:.8rem;color:var(--ink-3);margin-top:.25rem;line-height:1.5;max-width:60ch}

    .jobs-head{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-bottom:.9rem}
    .section-head{font-family:var(--serif);font-weight:400;font-size:1.25rem;margin:0}
    .jobs-all{font-size:.8rem;color:var(--forest)}

    .job-line{display:flex;align-items:center;justify-content:space-between;gap:1.25rem}
    .job-line-main{min-width:0}
    .job-line-title{font-weight:500;font-size:.95rem;line-height:1.35}
    .job-line-meta{font-size:.76rem;color:var(--ink-3);margin-top:.3rem;line-height:1.5}
    .dot{color:var(--rule-2);margin:0 .2rem}
    .job-line-amt{
        font-family:var(--serif);font-size:1.1rem;flex:none;
        font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap;
    }
    .job-line-amt small{display:block;font-family:var(--sans);font-size:.6rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);margin-top:.15rem;font-weight:500}

    .empty-note{text-align:center;padding:2.25rem 1.5rem}

    @media (min-width:620px){
        .dash-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (max-width:560px){
        .earn-strip{flex-direction:column;align-items:flex-start;gap:.9rem}
        .earn-actions{width:100%}
        .earn-actions .btn{flex:1}
        .job-line{flex-direction:column;align-items:flex-start;gap:.5rem}
        .job-line-amt{text-align:left}
        .notice-card .btn{width:100%}
    }
</style>
@endpush
