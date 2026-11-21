@extends('layouts.admin')

@section('title', 'Overview')

@section('content')
<h1>Overview</h1>
<p class="sub">Live counts. Nothing here is estimated.</p>

{{--
    Queue cards. Each is a link because a count an operator cannot act on is
    trivia. Where there is no destination yet (job drafts), there is no link —
    rather than a link to a page that does not exist.
--}}
<div class="queue-grid">
    @foreach ($queues as $key => $queue)
        @if ($queue['route'])
            <a class="card queue-card" href="{{ route($queue['route']) }}">
                <span class="queue-label">{{ $queue['label'] }}</span>
                <span class="queue-number {{ $queue['count'] > 0 ? 'queue-number-live' : '' }}">{{ $queue['count'] }}</span>
                <span class="queue-hint">{{ $queue['hint'] }}</span>
                <span class="queue-go">Open &rarr;</span>
            </a>
        @else
            <div class="card queue-card queue-card-static">
                <span class="queue-label">{{ $queue['label'] }}</span>
                <span class="queue-number {{ $queue['count'] > 0 ? 'queue-number-live' : '' }}">{{ $queue['count'] }}</span>
                <span class="queue-hint">{{ $queue['hint'] }}</span>
            </div>
        @endif
    @endforeach
</div>

<div class="split" style="margin-top:1.6rem">
    <div>
        <div class="card">
            <h2 class="section-head">Money</h2>
            <dl class="facts">
                <div>
                    <dt>Deposited into the platform</dt>
                    <dd class="figure">{{ \App\Domain\Money::fromMinorUnits($money['deposited_cents'])->format() }}</dd>
                </div>
                <div>
                    <dt>Currently held in escrow</dt>
                    <dd class="figure">{{ \App\Domain\Money::fromMinorUnits($money['escrow_held_cents'])->format() }}</dd>
                </div>
                <div>
                    <dt>Ledger entries</dt>
                    <dd class="figure">{{ number_format($money['ledger_rows']) }}</dd>
                </div>
            </dl>
            <p class="note">
                These are sums over the ledger, not stored totals — the same source
                every user's balance is derived from, so they cannot disagree.
            </p>
        </div>

        <div class="card">
            <h2 class="section-head">Recent decisions</h2>
            @if ($recent->isEmpty())
                <p class="note" style="margin:0">
                    No admin actions recorded yet.
                </p>
            @else
                <ul class="activity">
                    @foreach ($recent as $entry)
                        <li>
                            <span class="activity-action">{{ $entry->action }}</span>
                            <span class="activity-meta">
                                {{ $entry->actor?->name ?? 'System' }}
                                <span aria-hidden="true">·</span>
                                {{ $entry->created_at->format('j M, H:i') }}
                                @if ($entry->amount_cents !== null)
                                    <span aria-hidden="true">·</span>
                                    {{ $entry->amount()?->format() }}
                                @endif
                            </span>
                            @if ($entry->reason)
                                <span class="activity-reason">{{ $entry->reason }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div>
        <div class="card">
            <h2 class="section-head">Accounts</h2>
            <dl class="facts">
                <div><dt>Total</dt><dd class="figure">{{ number_format($accounts['total']) }}</dd></div>
                <div><dt>Freelancers</dt><dd>{{ number_format($accounts['freelancers']) }}</dd></div>
                <div><dt>Posters</dt><dd>{{ number_format($accounts['posters']) }}</dd></div>
                <div><dt>Admins</dt><dd>{{ number_format($accounts['admins']) }}</dd></div>
                <div><dt>Suspended</dt><dd>{{ number_format($accounts['suspended']) }}</dd></div>
            </dl>
            <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}" style="width:100%;margin-top:1rem">
                Review accounts
            </a>
        </div>

        <div class="card">
            <h2 class="section-head">Not built yet</h2>
            {{--
                Stated rather than hidden. An operator needs to know which powers
                this console does NOT have, or they will assume a missing button
                means a missing permission.
            --}}
            <ul class="not-yet">
                @foreach ($notYet as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .queue-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:.75rem}
    .queue-card{display:block;transition:border-color .15s ease,box-shadow .15s ease}
    .queue-card:hover{border-color:var(--rule-2);box-shadow:0 3px 14px rgba(22,20,15,.07)}
    .queue-card-static{cursor:default}
    .queue-label{display:block;font-size:.7rem;letter-spacing:.07em;text-transform:uppercase;color:var(--ink-3);font-weight:600}
    .queue-number{
        display:block;font-family:var(--serif);font-size:2.1rem;line-height:1.1;
        margin:.35rem 0 0;font-variant-numeric:tabular-nums;
    }
    /* A non-zero queue is work waiting. Amber, not red: it is not an error, but
       it should not read as "nothing to do" either. */
    .queue-number-live{color:var(--amber)}
    .queue-hint{display:block;font-size:.76rem;color:var(--ink-3);margin-top:.4rem;line-height:1.5}
    .queue-go{display:block;font-size:.75rem;color:var(--forest);margin-top:.6rem}

    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .9rem}
    .figure{font-family:var(--serif);font-size:1.45rem;line-height:1.15;font-variant-numeric:tabular-nums}
    .note{font-size:.76rem;color:var(--ink-3);margin:.9rem 0 0;line-height:1.55;border-top:1px solid var(--rule);padding-top:.8rem}

    .activity{list-style:none;margin:0;padding:0;display:grid;gap:.75rem}
    .activity li{padding-bottom:.75rem;border-bottom:1px solid var(--rule)}
    .activity li:last-child{border-bottom:0;padding-bottom:0}
    .activity-action{display:block;font-size:.83rem;font-weight:600}
    .activity-meta{display:block;font-size:.73rem;color:var(--ink-3);margin-top:.15rem}
    .activity-reason{display:block;font-size:.75rem;color:var(--ink-2);margin-top:.2rem;line-height:1.45}

    .not-yet{margin:0;padding-left:1.05rem;font-size:.79rem;color:var(--ink-2);line-height:1.6}
    .not-yet li{margin-bottom:.45rem}
    .not-yet li:last-child{margin-bottom:0}

    @media (min-width:620px){
        .queue-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    }
</style>
@endpush
