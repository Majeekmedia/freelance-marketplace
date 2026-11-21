@extends('layouts.app')

@section('title', 'Deposit review')

@section('content')
<div class="page-head">
    <div>
        <h1>Deposit review</h1>
        <p class="sub">
            @if ($pendingCount > 0)
                {{ $pendingCount }} request{{ $pendingCount === 1 ? '' : 's' }} waiting for a decision.
            @else
                Nothing is waiting. Approving a deposit is the only way money enters the system.
            @endif
        </p>
    </div>
</div>

<nav class="queue-tabs" aria-label="Filter deposits">
    @foreach ([
        'pending' => 'Awaiting review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'all' => 'All',
    ] as $key => $label)
        <a href="{{ route('admin.deposits.index', ['status' => $key]) }}"
           class="{{ $status === $key ? 'is-active' : '' }}"
           @if ($status === $key) aria-current="page" @endif>
            {{ $label }}
            @if ($key === 'pending' && $pendingCount > 0)
                <span class="queue-count">{{ $pendingCount }}</span>
            @endif
        </a>
    @endforeach
</nav>

@if ($deposits->isEmpty())
    <div class="card" style="text-align:center;padding:3rem 1.5rem">
        <p style="margin:0 0 .35rem;font-weight:500">
            {{ $status === 'pending' ? 'No deposits awaiting review' : 'Nothing here' }}
        </p>
        <p style="margin:0;font-size:.85rem;color:var(--ink-3)">
            @if ($status === 'pending')
                New poster deposits appear here for confirmation.
            @else
                Try another filter.
            @endif
        </p>
    </div>
@else
    <div class="cardlist">
        @foreach ($deposits as $deposit)
            <a class="card dep-row" href="{{ route('admin.deposits.show', $deposit) }}">
                <div class="dep-main">
                    <div class="dep-title">
                        {{ $deposit->requestedAmount()->format() }}
                        <span class="badge badge-{{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
                    </div>
                    <div class="dep-meta">
                        {{ $deposit->user->name }}
                        <span class="dep-sep" aria-hidden="true">·</span>
                        {{ $deposit->method_snapshot['label'] ?? 'Removed method' }}
                        <span class="dep-sep" aria-hidden="true">·</span>
                        {{ $deposit->created_at->format('j M Y, H:i') }}
                    </div>
                    <div class="dep-ref">Ref {{ $deposit->transfer_reference }}</div>
                </div>

                @if ($deposit->status === \App\Domain\DepositStatus::APPROVED)
                    <div class="dep-amount">
                        credited {{ $deposit->creditedAmount()?->format() }}
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    @if ($deposits->hasPages())
        <div style="margin-top:1.25rem">{{ $deposits->links() }}</div>
    @endif
@endif
@endsection

@push('styles')
<style>
    .queue-tabs{display:flex;gap:.35rem;flex-wrap:wrap;margin:1.5rem 0 1.25rem;font-size:.85rem}
    .queue-tabs a{
        display:inline-flex;align-items:center;gap:.35rem;
        padding:.45rem .8rem;border-radius:100px;
        border:1px solid var(--rule-2);color:var(--ink-2);background:#fff;
    }
    .queue-tabs a:hover{border-color:var(--ink-3)}
    .queue-tabs a.is-active{background:var(--forest);border-color:var(--forest);color:#fff}
    .queue-count{
        font-size:.7rem;font-weight:600;padding:.05rem .4rem;border-radius:100px;
        background:var(--amber-wash);color:var(--amber);
    }
    .queue-tabs a.is-active .queue-count{background:rgba(255,255,255,.25);color:#fff}

    .dep-row{display:flex;align-items:center;justify-content:space-between;gap:1.25rem}
    .dep-main{min-width:0}
    .dep-title{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-weight:500;font-size:.98rem}
    .dep-meta{font-size:.8rem;color:var(--ink-3);margin-top:.35rem;line-height:1.5}
    .dep-sep{color:var(--rule-2)}
    .dep-ref{font-size:.75rem;color:var(--ink-3);margin-top:.2rem;font-family:ui-monospace,monospace;word-break:break-all}
    .dep-amount{font-size:.8rem;color:var(--forest);flex:none;font-variant-numeric:tabular-nums}

    @media (max-width:700px){
        .dep-row{flex-direction:column;align-items:stretch;gap:.5rem}
        .dep-amount{text-align:left}
    }
</style>
@endpush
