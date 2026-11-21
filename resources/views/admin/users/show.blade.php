@extends('layouts.admin')

@section('title', $user->name)

@section('content')
<h1>{{ $user->name }}</h1>
<p class="sub">{{ $user->email }}</p>

<div class="card">
    {{--
        This was a two-column table of label/value pairs. It is not tabular data
        — it is a description list — so <dl> is both the more correct element and
        the one that reflows on a phone without any width gymnastics.
    --}}
    <dl class="facts">
        <div class="fact-row"><dt>Type</dt><dd>{{ $user->type->label() }}</dd></div>
        <div class="fact-row"><dt>Status</dt><dd><span class="badge badge-{{ $user->status->value }}">{{ $user->status->label() }}</span></dd></div>
        <div class="fact-row"><dt>Joined</dt><dd>{{ $user->created_at->format('j F Y, H:i') }}</dd></div>
        <div class="fact-row"><dt>Can sign in</dt><dd>{{ $user->canAuthenticate() ? 'Yes' : 'No' }}</dd></div>
        <div class="fact-row"><dt>Can act</dt><dd>{{ $user->canAct() ? 'Yes' : 'No' }}</dd></div>
    </dl>
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
