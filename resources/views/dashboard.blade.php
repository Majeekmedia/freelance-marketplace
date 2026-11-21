@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1>{{ $user->name }}</h1>
<p class="sub">
    {{ $user->type->label() }} ·
    <span class="badge badge-{{ $user->status->value }}">{{ $user->status->label() }}</span>
</p>

@if ($isPending)
    <div class="card">
        <div class="alert alert-note" role="status" style="margin-bottom:0">
            <strong>Your account is awaiting approval.</strong><br>
            You can browse and edit your profile. You cannot apply for work, post jobs
            or send messages until an administrator approves you.
        </div>
    </div>
@endif

<div class="card">
    @if ($dashboardView === 'admin')
        {{--
            An admin browsing the customer app lands here. This is deliberately
            NOT the admin dashboard — the console is its own surface with its own
            sign-in and layout. Presenting a half-console here is what made the
            two experiences blur together.
        --}}
        <h2 style="font-family:var(--serif);font-weight:400;font-size:1.3rem;margin:0 0 .6rem">Administration</h2>
        <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
            Admin work happens in a separate console, with its own navigation and
            its own sign-in.
        </p>
        <a class="btn" href="{{ route('admin.overview') }}">Open admin console</a>

    @elseif ($dashboardView === 'poster')
        <h2 style="font-family:var(--serif);font-weight:400;font-size:1.3rem;margin:0 0 .6rem">Your hiring</h2>
        @php
            $jobCount = auth()->user()->postedJobs()->count();
            $draftCount = auth()->user()->postedJobs()->where('status', 'draft')->count();
        @endphp
        @if ($jobCount === 0)
            <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
                Post a job, fund its budget into escrow, and freelancers can apply.
                Nothing is public until the money is held.
            </p>
            <a class="btn" href="{{ route('jobs.manage.create') }}">Post a job</a>
        @else
            <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
                {{ $jobCount }} job{{ $jobCount === 1 ? '' : 's' }}
                @if ($draftCount > 0)
                    · {{ $draftCount }} still in draft and not visible to anyone
                @endif
            </p>
            <div class="actions" style="justify-content:flex-start">
                <a class="btn btn-secondary" href="{{ route('jobs.manage.index') }}">Manage jobs</a>
                <a class="btn" href="{{ route('jobs.manage.create') }}">Post a job</a>
            </div>
        @endif

    @else
        <h2 style="font-family:var(--serif);font-weight:400;font-size:1.3rem;margin:0 0 .6rem">Your work</h2>
        @php
            $profile = auth()->user()->freelancerProfile;
        @endphp
        @if ($profile === null)
            <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
                Add a headline and a few portfolio pieces so posters can judge your work
                before they message you.
            </p>
            <a class="btn" href="{{ route('profile.edit') }}">Set up your profile</a>
        @else
            <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
                {{ $profile->headline ?: 'No headline yet' }}
                @if ($profile->is_public)
                    · visible in the public directory
                @else
                    · hidden from the public directory
                @endif
            </p>
            <div class="actions" style="justify-content:flex-start">
                <a class="btn btn-secondary" href="{{ route('profile.edit') }}">Edit profile</a>
                <a class="btn btn-secondary" href="{{ route('portfolio.index') }}">Portfolio</a>
                @if ($profile->is_public && auth()->user()->status->canAct())
                    <a class="btn" href="{{ route('freelancers.show', auth()->id()) }}">View public page</a>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
