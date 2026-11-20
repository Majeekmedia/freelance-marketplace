@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')
<div class="card">
    <h1>Dashboard</h1>
    <p class="sub">Signed in as {{ auth()->user()->name }}.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    <p style="font-size:.875rem;color:var(--ink-2);margin:0 0 1.1rem">
        Account type: <strong>{{ auth()->user()->type->label() }}</strong><br>
        Status: <strong>{{ auth()->user()->status->label() }}</strong>
    </p>

    @unless (auth()->user()->canAct())
        <div class="alert alert-note" role="status">
            Your account is awaiting approval. You can browse and edit your profile,
            but you cannot apply, post or message until an administrator approves you.
        </div>
    @endunless

    <div class="alert alert-note" style="margin-top:1.1rem">
        Dashboard panels for freelancers, posters and admins arrive in later milestones.
    </div>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:.5rem">
        @csrf
        <button class="btn btn-secondary" type="submit">Sign out</button>
    </form>
</div>
@endsection
