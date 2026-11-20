@extends('layouts.app')

@section('title', $job->title)

@section('content')
<a href="{{ route('jobs.manage.index') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Your jobs
</a>

<div class="page-head">
    <div style="min-width:0">
        <h1>{{ $job->title }}</h1>
        <p class="sub">
            <span class="badge badge-{{ $job->status->tone() }}">{{ $job->status->label() }}</span>
            <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
            {{ $job->category?->name ?? 'Uncategorised' }}
        </p>
    </div>
    @if ($canEdit)
        <a class="btn btn-secondary" href="{{ route('jobs.manage.edit', $job) }}">Edit</a>
    @endif
</div>

@if ($job->isDraft())
    <div class="alert alert-note">
        <strong>This job is private.</strong>
        Only you can see it. It becomes public once its budget is held in escrow.
    </div>
@endif

<div class="split">
    <div>
        <div class="card">
            <dl class="facts">
                <div>
                    <dt>Budget</dt>
                    <dd style="font-family:var(--serif);font-size:1.7rem;line-height:1.15">{{ $job->budget()->format() }}</dd>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
                    <div>
                        <dt>Deadline</dt>
                        <dd>{{ $job->deadline?->format('j M Y') ?? 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Escrow</dt>
                        <dd>
                            @if ($job->isFunded())
                                <span class="badge badge-approved">Funded</span>
                            @else
                                <span class="badge badge-draft">Not funded</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </dl>

            @if ($job->isDraft())
                <p style="font-size:.78rem;color:var(--ink-3);margin:1.1rem 0 0;line-height:1.5;border-top:1px solid var(--rule);padding-top:.9rem">
                    Funding opens in the next milestone. Until then this job cannot be published.
                </p>
            @endif
        </div>
    </div>

    <div>
        <div class="card">
            <h2 style="font-family:var(--serif);font-weight:400;font-size:1.1rem;margin:0 0 .7rem">The work</h2>
            <div style="font-size:.92rem;color:var(--ink-2);white-space:pre-line;line-height:1.7">{{ $job->description }}</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (min-width:860px){
        .split > div:first-child{order:2}
        .split > div:last-child{order:1}
    }
</style>
@endpush
