@extends('layouts.app')

@section('title', 'Deposit review')

@section('content')
<a href="{{ route('admin.deposits.index') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Deposit review
</a>

<h1>{{ $deposit->requestedAmount()->format() }}</h1>
<p class="sub">
    <span class="badge badge-{{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
    <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
    {{ $deposit->user->name }}
    <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
    submitted {{ $deposit->created_at->format('j M Y, H:i') }}
</p>

<div class="split">
    <div>
        <div class="card">
            <h2 class="section-head">Proof of transfer</h2>
            <a class="proof-link" href="{{ route('admin.deposits.proof', $deposit) }}" target="_blank" rel="noopener">
                <img src="{{ route('admin.deposits.proof', $deposit) }}" alt="Proof of transfer">
                <span>Open full size</span>
            </a>
        </div>

        @if ($deposit->isPending())
            <div class="card">
                <h2 class="section-head">Decision</h2>

                {{--
                    Approve. The amount defaults to what was requested but is an
                    editable field, because banks deduct fees and what arrived is
                    often less. Editing it credits what actually landed.
                --}}
                <form method="POST" action="{{ route('admin.deposits.approve', $deposit) }}" class="decision-form">
                    @csrf
                    <div class="duo">
                        <div>
                            <label for="credited_amount">Amount to credit</label>
                            <input id="credited_amount" name="credited_amount" type="text" inputmode="decimal"
                                   value="{{ $deposit->requestedAmount()->toDecimal() }}">
                            <p class="hint">Change this if a fee was deducted and less arrived.</p>
                        </div>
                        <div>
                            <label for="approve_reason">Reason</label>
                            <input id="approve_reason" name="reason" type="text" required minlength="5" maxlength="255"
                                   value="Deposit confirmed against submitted proof">
                            <p class="hint">Recorded in the audit log.</p>
                        </div>
                    </div>

                    <button class="btn" type="submit">Approve and credit</button>
                </form>

                <hr class="decision-rule">

                {{-- Reject. Credits nothing; the reason goes to the poster. --}}
                <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}" class="decision-form">
                    @csrf
                    <div>
                        <label for="reject_reason">Rejection reason</label>
                        <input id="reject_reason" name="reason" type="text" required minlength="5" maxlength="255"
                               placeholder="Reference does not match any transfer received">
                        <p class="hint">The poster sees this. Nothing is credited.</p>
                    </div>

                    <button class="btn btn-danger" type="submit">Reject deposit</button>
                </form>
            </div>
        @else
            <div class="card">
                <h2 class="section-head">Decision</h2>
                <dl class="facts">
                    <div>
                        <dt>Outcome</dt>
                        <dd>
                            <span class="badge badge-{{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
                        </dd>
                    </div>
                    @if ($deposit->creditedAmount() !== null)
                        <div>
                            <dt>Amount credited</dt>
                            <dd style="font-family:var(--serif);font-size:1.3rem">{{ $deposit->creditedAmount()->format() }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Reviewed by</dt>
                        <dd>{{ $deposit->reviewer?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Reviewed at</dt>
                        <dd>{{ $deposit->reviewed_at?->format('j M Y, H:i') ?? '—' }}</dd>
                    </div>
                    @if ($deposit->rejection_reason)
                        <div>
                            <dt>Reason</dt>
                            <dd>{{ $deposit->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>
                <p class="hint" style="margin-top:1rem;border-top:1px solid var(--rule);padding-top:.9rem">
                    This request has been decided and cannot be changed. A correction would be a
                    separate adjustment, so the history stays truthful.
                </p>
            </div>
        @endif
    </div>

    <div>
        <div class="card">
            <h2 class="section-head">What the poster was told to pay</h2>
            <dl class="facts">
                <div><dt>Method</dt><dd>{{ $deposit->method_snapshot['label'] ?? 'Removed method' }}</dd></div>
                @if (($deposit->method_snapshot['type'] ?? null) === 'bank_transfer')
                    <div><dt>Bank</dt><dd>{{ $deposit->method_snapshot['bank_name'] ?? '—' }}</dd></div>
                    <div><dt>Account name</dt><dd>{{ $deposit->method_snapshot['account_name'] ?? '—' }}</dd></div>
                    <div><dt>Account number</dt><dd class="mono">{{ $deposit->method_snapshot['account_number'] ?? '—' }}</dd></div>
                @else
                    <div><dt>Asset</dt><dd>{{ $deposit->method_snapshot['crypto_asset'] ?? '—' }}</dd></div>
                    <div><dt>Network</dt><dd>{{ $deposit->method_snapshot['crypto_network'] ?? '—' }}</dd></div>
                    <div><dt>Address</dt><dd class="mono">{{ $deposit->method_snapshot['crypto_address'] ?? '—' }}</dd></div>
                @endif
            </dl>
            <p class="hint" style="margin-top:.9rem;border-top:1px solid var(--rule);padding-top:.8rem">
                Frozen at submission. Compare this against the proof before crediting.
            </p>
        </div>

        <div class="card">
            <h2 class="section-head">Poster</h2>
            <dl class="facts">
                <div><dt>Name</dt><dd>{{ $deposit->user->name }}</dd></div>
                <div><dt>Reference given</dt><dd class="mono">{{ $deposit->transfer_reference }}</dd></div>
                <div>
                    <dt>Current available balance</dt>
                    <dd style="font-family:var(--serif);font-size:1.3rem">{{ $posterBalance->format() }}</dd>
                </div>
            </dl>
            {{--
                A projection of what crediting would do. Only meaningful while the
                request is still pending: once it is decided, "if you credit X" is
                a stale hypothetical sitting next to a settled fact, and on an
                already-approved request it would double-count the credit.
            --}}
            @if ($deposit->isPending())
                <p class="hint" style="margin-top:.9rem;border-top:1px solid var(--rule);padding-top:.8rem">
                    If you credit {{ $deposit->requestedAmount()->format() }}, the balance becomes
                    {{ $posterBalance->add($deposit->requestedAmount())->format() }}.
                </p>
            @else
                <p class="hint" style="margin-top:.9rem;border-top:1px solid var(--rule);padding-top:.8rem">
                    This balance already includes the decision above.
                </p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .8rem}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;word-break:break-all}

    .proof-link{display:block;border:1px solid var(--rule);border-radius:10px;overflow:hidden}
    .proof-link img{display:block;width:100%;height:auto;max-height:440px;object-fit:contain;background:var(--paper-2)}
    .proof-link span{display:block;padding:.55rem .7rem;font-size:.78rem;color:var(--forest);border-top:1px solid var(--rule);text-decoration:underline}

    .decision-form{display:grid;gap:.9rem}
    .decision-form label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .decision-form input{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);min-width:0;
    }
    .decision-form input:focus{outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest)}
    .decision-form button{justify-self:start}
    .decision-rule{border:0;border-top:1px solid var(--rule);margin:1.35rem 0}
    .hint{font-size:.75rem;color:var(--ink-3);margin:.35rem 0 0;line-height:1.5}

    @media (max-width:560px){
        .decision-form button{justify-self:stretch;width:100%}
    }
</style>
@endpush
