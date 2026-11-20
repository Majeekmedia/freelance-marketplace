@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
<h1>Accounts</h1>
<p class="sub">
    {{ $pendingCount }} awaiting approval.
    Approval grants the ability to act; it does not affect the ability to sign in.
</p>

<form class="filters" method="GET" action="{{ route('admin.users.index') }}">
    <label class="sr-only" for="status">Filter by status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach (['pending', 'approved', 'rejected', 'suspended'] as $status)
            <option value="{{ $status }}" @selected($statusFilter === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>

    <label class="sr-only" for="type">Filter by type</label>
    <select id="type" name="type">
        <option value="">All types</option>
        @foreach (['freelancer', 'poster', 'admin'] as $type)
            <option value="{{ $type }}" @selected($typeFilter === $type)>
                {{ ucfirst($type) }}
            </option>
        @endforeach
    </select>

    <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <table class="responsive">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Status</th>
                <th>Joined</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="cell-primary" data-label="Name">
                        <a href="{{ route('admin.users.show', $user) }}" style="font-weight:500">{{ $user->name }}</a>
                    </td>
                    <td data-label="Email" style="color:var(--ink-3)">{{ $user->email }}</td>
                    <td data-label="Type"><span class="badge badge-{{ $user->type->value }}">{{ $user->type->label() }}</span></td>
                    <td data-label="Status"><span class="badge badge-{{ $user->status->value }}">{{ $user->status->label() }}</span></td>
                    <td data-label="Joined" style="color:var(--ink-3)">{{ $user->created_at->format('j M Y') }}</td>
                    <td class="cell-actions">
                        <div class="actions">
                            @if ($user->isPending())
                                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                    @csrf
                                    <button class="btn btn-sm" type="submit">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm" type="submit">Reject</button>
                                </form>
                            @elseif ($user->status->value === 'approved')
                                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                    @csrf
                                    <button class="btn btn-danger btn-sm" type="submit">Suspend</button>
                                </form>
                            @elseif ($user->status->value === 'suspended')
                                <form method="POST" action="{{ route('admin.users.reinstate', $user) }}">
                                    @csrf
                                    <button class="btn btn-sm" type="submit">Reinstate</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:2rem .5rem;text-align:center;color:var(--ink-3)">
                        No accounts match this filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:1.1rem">
    {{ $users->links() }}
</div>
@endsection
