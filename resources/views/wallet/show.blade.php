@extends('layouts.app')

@section('title', 'Wallet')

@section('content')
<h1>Wallet</h1>
<p class="sub">Every figure here is the sum of the entries below it.</p>

{{--
    Balance cards. The explanatory line under each figure matters: users assume
    a wallet number is stored and can therefore drift. Saying "derived from N
    entries" is the honest answer and it points at the evidence underneath.
--}}
<div class="wallet-grid">
    <div class="card wallet-card wallet-card-primary">
        <div class="wallet-label">Available</div>
        <div class="wallet-amount">{{ $summary['available']->format() }}</div>
        <p class="wallet-note">Ready to fund a job or withdraw.</p>
    </div>

    <div class="card wallet-card">
        <div class="wallet-label">Held in escrow</div>
        <div class="wallet-amount">{{ $summary['escrow_held']->format() }}</div>
        <p class="wallet-note">Funded into jobs, not yet released.</p>
    </div>

    <div class="card wallet-card">
        <div class="wallet-label">Held for withdrawal</div>
        <div class="wallet-amount">{{ $summary['withdrawal_held']->format() }}</div>
        <p class="wallet-note">Requested, awaiting payout.</p>
    </div>

    <div class="card wallet-card">
        <div class="wallet-label">Lifetime received</div>
        <div class="wallet-amount">{{ $summary['lifetime_credited']->format() }}</div>
        <p class="wallet-note">Everything ever credited, including what you've spent.</p>
    </div>
</div>

<h2 class="wallet-history-head">History</h2>

@if ($entries->isEmpty())
    <div class="card" style="text-align:center;padding:2.75rem 1.5rem">
        <p style="margin:0 0 .35rem;font-weight:500">No entries yet</p>
        <p style="margin:0;font-size:.85rem;color:var(--ink-3)">
            Deposits, escrow, releases and withdrawals all appear here.
        </p>
    </div>
@else
    <div class="cardlist">
        @foreach ($entries as $entry)
            <div class="card ledger-row">
                <div class="ledger-main">
                    <div class="ledger-kind">
                        <span class="ledger-dot ledger-dot-{{ $entry->direction->value }}" aria-hidden="true"></span>
                        {{ $entry->kind->label() }}
                    </div>
                    <div class="ledger-reason">{{ $entry->reason }}</div>
                    <div class="ledger-meta">
                        {{ $entry->created_at->format('j M Y, H:i') }}
                        @if ($entry->job)
                            <span class="ledger-sep" aria-hidden="true">·</span>
                            {{ $entry->job->title }}
                        @endif
                        @if ($entry->actor)
                            <span class="ledger-sep" aria-hidden="true">·</span>
                            by {{ $entry->actor->name }}
                        @endif
                    </div>
                </div>

                <div class="ledger-amount ledger-amount-{{ $entry->direction->value }}">
                    {{ $entry->isCredit() ? '+' : '−' }}{{ $entry->amount()->format() }}
                </div>
            </div>
        @endforeach
    </div>

    @if ($entries->hasPages())
        <div style="margin-top:1.25rem">{{ $entries->links() }}</div>
    @endif

    <p class="wallet-footnote">
        Entries are permanent. A correction appears as a new entry rather than
        changing an old one, so this history always explains the balance above.
    </p>
@endif
@endsection

@push('styles')
<style>
    .wallet-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:.75rem;margin-bottom:2rem}
    .wallet-card{padding:1.15rem 1.25rem}
    .wallet-card + .wallet-card{margin-top:0}
    .wallet-card-primary{background:var(--forest-wash);border-color:#CBDDD4}
    .wallet-label{
        font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;
        color:var(--ink-3);font-weight:600;
    }
    .wallet-card-primary .wallet-label{color:var(--forest)}
    .wallet-amount{
        font-family:var(--serif);font-size:1.85rem;line-height:1.15;
        margin:.3rem 0 0;font-variant-numeric:tabular-nums;
    }
    .wallet-card-primary .wallet-amount{font-size:2.2rem;color:var(--forest)}
    .wallet-note{font-size:.75rem;color:var(--ink-3);margin:.4rem 0 0;line-height:1.45}
    .wallet-card-primary .wallet-note{color:var(--forest-2)}

    .wallet-history-head{
        font-family:var(--serif);font-weight:400;font-size:1.25rem;
        margin:0 0 .9rem;
    }

    .ledger-row{display:flex;align-items:center;justify-content:space-between;gap:1.25rem;padding:1rem 1.15rem}
    .ledger-main{min-width:0}
    .ledger-kind{
        display:flex;align-items:center;gap:.45rem;
        font-size:.87rem;font-weight:500;
    }
    .ledger-dot{width:6px;height:6px;border-radius:50%;flex:none}
    .ledger-dot-credit{background:var(--forest)}
    .ledger-dot-debit{background:var(--ink-3)}
    .ledger-reason{font-size:.82rem;color:var(--ink-2);margin-top:.25rem;line-height:1.45}
    .ledger-meta{font-size:.74rem;color:var(--ink-3);margin-top:.3rem;line-height:1.5}
    .ledger-sep{color:var(--rule-2);margin:0 .2rem}

    .ledger-amount{
        font-family:var(--serif);font-size:1.1rem;flex:none;
        font-variant-numeric:tabular-nums;white-space:nowrap;
    }
    .ledger-amount-credit{color:var(--forest)}
    .ledger-amount-debit{color:var(--ink-2)}

    .wallet-footnote{
        font-size:.76rem;color:var(--ink-3);
        margin:1.25rem 0 0;line-height:1.55;
        border-top:1px solid var(--rule);padding-top:.9rem;
    }

    @media (min-width:560px){
        .wallet-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (min-width:960px){
        .wallet-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    }
    @media (max-width:520px){
        .ledger-row{flex-direction:column;align-items:flex-start;gap:.5rem}
        .ledger-amount{font-size:1.25rem}
    }
</style>
@endpush
