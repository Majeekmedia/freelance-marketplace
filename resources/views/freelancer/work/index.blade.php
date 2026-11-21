@extends('layouts.app')

@section('title', $state === 'active' ? 'Active work' : 'Completed work')

@section('content')
<a href="{{ route('dashboard') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Dashboard
</a>

<h1>{{ $state === 'active' ? 'Active work' : 'Completed work' }}</h1>
<p class="sub">
    @if ($state === 'active')
        Jobs you have been hired for and are still delivering.
    @else
        Jobs where every milestone has been released to you.
    @endif
</p>

{{-- The two states as tabs, so the counts are always visible and switching is
     one tap rather than a trip back to the dashboard. --}}
<nav class="work-tabs" aria-label="Work status">
    <a href="{{ route('freelancer.work.active') }}"
       class="{{ $state === 'active' ? 'is-active' : '' }}"
       @if ($state === 'active') aria-current="page" @endif>
        Active
        <span class="work-count">{{ $activeCount }}</span>
    </a>
    <a href="{{ route('freelancer.work.completed') }}"
       class="{{ $state === 'completed' ? 'is-active' : '' }}"
       @if ($state === 'completed') aria-current="page" @endif>
        Completed
        <span class="work-count">{{ $completedCount }}</span>
    </a>
</nav>

@if ($jobs->isEmpty())
    <div class="card" style="text-align:center;padding:2.75rem 1.5rem">
        <p style="margin:0 0 .35rem;font-weight:500">
            {{ $state === 'active' ? 'No active work right now' : 'Nothing completed yet' }}
        </p>
        <p style="margin:0 0 1.2rem;font-size:.85rem;color:var(--ink-3);line-height:1.55;max-width:46ch;margin-inline:auto">
            @if ($state === 'active')
                A job appears here once a poster hires you. Until then, browse the
                funded jobs on your dashboard.
            @else
                A job moves here only after every milestone has been released — the
                point at which you have actually been paid.
            @endif
        </p>
        <a class="btn btn-secondary btn-sm" href="{{ $state === 'active' ? route('jobs.index') : route('freelancer.work.active') }}">
            {{ $state === 'active' ? 'Browse open jobs' : 'See active work' }}
        </a>
    </div>
@else
    <div class="cardlist">
        @foreach ($jobs as $job)
            <div class="card work-row">
                <div class="work-main">
                    <div class="work-title">{{ $job->title }}</div>
                    <div class="work-meta">
                        {{ $job->category?->name ?? 'Uncategorised' }}
                        <span class="dot" aria-hidden="true">·</span>
                        {{ $job->poster?->name ?? 'A poster' }}
                        @if ($job->deadline)
                            <span class="dot" aria-hidden="true">·</span>
                            {{ $state === 'active' ? 'due' : 'was due' }} {{ $job->deadline->format('j M Y') }}
                        @endif
                    </div>
                </div>

                <div class="work-right">
                    <div class="work-amt">
                        {{ $job->budget()->format() }}
                        <small>{{ $state === 'active' ? 'In escrow' : 'Released' }}</small>
                    </div>
                    <span class="badge badge-{{ $job->status->tone() }}">{{ $job->status->label() }}</span>
                </div>
            </div>
        @endforeach
    </div>

    @if ($jobs->hasPages())
        <div style="margin-top:1.25rem">{{ $jobs->links() }}</div>
    @endif
@endif
@endsection

@push('styles')
<style>
    .work-tabs{display:flex;gap:.35rem;flex-wrap:wrap;margin:1.5rem 0 1.25rem;font-size:.85rem}
    .work-tabs a{
        display:inline-flex;align-items:center;gap:.4rem;
        padding:.45rem .85rem;border-radius:100px;
        border:1px solid var(--rule-2);color:var(--ink-2);background:#fff;
    }
    .work-tabs a:hover{border-color:var(--ink-3)}
    .work-tabs a.is-active{background:var(--forest);border-color:var(--forest);color:#fff}
    .work-count{
        font-size:.7rem;font-weight:600;padding:.05rem .4rem;border-radius:100px;
        background:var(--paper-3);color:var(--ink-2);font-variant-numeric:tabular-nums;
    }
    .work-tabs a.is-active .work-count{background:rgba(255,255,255,.24);color:#fff}

    .work-row{display:flex;align-items:center;justify-content:space-between;gap:1.25rem}
    .work-main{min-width:0}
    .work-title{font-weight:500;font-size:.95rem;line-height:1.35}
    .work-meta{font-size:.76rem;color:var(--ink-3);margin-top:.3rem;line-height:1.5}
    .dot{color:var(--rule-2);margin:0 .2rem}
    .work-right{display:flex;align-items:center;gap:.9rem;flex:none}
    .work-amt{
        font-family:var(--serif);font-size:1.1rem;
        font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap;
    }
    .work-amt small{display:block;font-family:var(--sans);font-size:.6rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);margin-top:.15rem;font-weight:500}

    @media (max-width:620px){
        .work-row{flex-direction:column;align-items:flex-start;gap:.75rem}
        .work-right{width:100%;justify-content:space-between}
        .work-amt{text-align:left}
    }
</style>
@endpush
