@extends('layouts.app')

@section('title', $user->name)

@section('content')
<h1>{{ $user->name }}</h1>
<p class="sub">{{ $user->email }}</p>

<div class="card">
    <table>
        <tbody>
            <tr><th style="width:140px">Type</th><td>{{ $user->type->label() }}</td></tr>
            <tr><th>Status</th><td><span class="badge badge-{{ $user->status->value }}">{{ $user->status->label() }}</span></td></tr>
            <tr><th>Joined</th><td>{{ $user->created_at->format('j F Y, H:i') }}</td></tr>
            <tr><th>Can sign in</th><td>{{ $user->canAuthenticate() ? 'Yes' : 'No' }}</td></tr>
            <tr><th>Can act</th><td>{{ $user->canAct() ? 'Yes' : 'No' }}</td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="actions" style="justify-content:flex-start">
        @if ($user->isPending())
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                <button class="btn" type="submit">Approve</button>
            </form>
            <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                @csrf
                <button class="btn btn-secondary" type="submit">Reject</button>
            </form>
        @elseif ($user->status->value === 'approved')
            <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                @csrf
                <button class="btn btn-danger" type="submit">Suspend</button>
            </form>
        @elseif ($user->status->value === 'suspended')
            <form method="POST" action="{{ route('admin.users.reinstate', $user) }}">
                @csrf
                <button class="btn" type="submit">Reinstate</button>
            </form>
        @endif

        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Back to accounts</a>
    </div>
</div>
@endsection
