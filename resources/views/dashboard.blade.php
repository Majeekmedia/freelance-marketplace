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
        <h2 style="font-family:var(--serif);font-weight:400;font-size:1.3rem;margin:0 0 .6rem">Administration</h2>
        <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1rem">
            {{ \App\Models\User::pending()->count() }} account(s) awaiting approval.
        </p>
        <a class="btn" href="{{ route('admin.users.index') }}">Review accounts</a>

    @elseif ($dashboardView === 'poster')
        <h2 style="font-family:var(--serif);font-weight:400;font-size:1.3rem;margin:0 0 .6rem">Your hiring</h2>
        <p style="font-size:.875rem;color:var(--ink-2);margin:0">
            Posting jobs, funding escrow and reviewing applicants arrive in milestone M4–M7.
        </p>

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
