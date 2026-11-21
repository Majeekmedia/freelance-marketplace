@extends('layouts.app')

@section('title', 'Deposit request')

@section('content')
<a href="{{ route('wallet.deposits.index') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Fund wallet
</a>

<h1>{{ $deposit->requestedAmount()->format() }}</h1>
<p class="sub">
    <span class="badge badge-{{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
    <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
    submitted {{ $deposit->created_at->format('j M Y, H:i') }}
</p>

@if ($deposit->status === \App\Domain\DepositStatus::PENDING)
    <div class="alert alert-note">
        <strong>Awaiting review.</strong>
        Your balance has not changed yet. An admin will confirm the transfer and credit
        your wallet.
    </div>
@elseif ($deposit->status === \App\Domain\DepositStatus::APPROVED)
    <div class="alert alert-ok">
        <strong>Approved.</strong>
        {{ $deposit->creditedAmount()?->format() }} was added to your wallet
        @if ($deposit->reviewer)
            by {{ $deposit->reviewer->name }}
        @endif
        @if ($deposit->reviewed_at)
            on {{ $deposit->reviewed_at->format('j M Y') }}
        @endif.
        @if ($deposit->wasAdjusted())
            <br>The amount credited differs from the amount you sent — banks and networks
            often deduct a fee, and only what arrived is credited.
        @endif
    </div>
@else
    <div class="alert alert-error">
        <strong>Rejected.</strong>
        @if ($deposit->rejection_reason)
            {{ $deposit->rejection_reason }}
        @else
            No reason was recorded.
        @endif
    </div>
@endif

<div class="split">
    <div>
        <div class="card">
            <dl class="facts">
                <div>
                    <dt>Amount sent</dt>
                    <dd style="font-family:var(--serif);font-size:1.4rem">{{ $deposit->requestedAmount()->format() }}</dd>
                </div>
                @if ($deposit->creditedAmount() !== null)
                    <div>
                        <dt>Amount credited</dt>
                        <dd style="font-family:var(--serif);font-size:1.4rem">{{ $deposit->creditedAmount()->format() }}</dd>
                    </div>
                @endif
                <div>
                    <dt>Reference</dt>
                    <dd class="mono">{{ $deposit->transfer_reference }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <h2 class="section-head">Proof of transfer</h2>
            <a class="proof-link" href="{{ route('wallet.deposits.proof', $deposit) }}" target="_blank" rel="noopener">
                <img src="{{ route('wallet.deposits.proof', $deposit) }}" alt="Proof of transfer"
                     onerror="this.style.display='none'; this.parentElement.classList.add('proof-link-broken')">
                <span>Open full size</span>
            </a>
            <p class="hint" style="margin-top:.7rem">
                Only you and the admin can see this image.
            </p>
        </div>
    </div>

    <div>
        {{--
            The instructions the poster was given, from the snapshot — not from
            the method as it stands now (FR-039d). If an admin has since changed
            the account, this page still shows what was actually paid.
        --}}
        <div class="card">
            <h2 class="section-head">Where you were told to pay</h2>
            <dl class="facts">
                <div>
                    <dt>Method</dt>
                    <dd>{{ $deposit->method_snapshot['label'] ?? 'Removed method' }}</dd>
                </div>

                @if (($deposit->method_snapshot['type'] ?? null) === 'bank_transfer')
                    <div><dt>Bank</dt><dd>{{ $deposit->method_snapshot['bank_name'] ?? '—' }}</dd></div>
                    <div><dt>Account name</dt><dd>{{ $deposit->method_snapshot['account_name'] ?? '—' }}</dd></div>
                    <div><dt>Account number</dt><dd class="mono">{{ $deposit->method_snapshot['account_number'] ?? '—' }}</dd></div>
                @else
                    <div><dt>Asset</dt><dd>{{ $deposit->method_snapshot['crypto_asset'] ?? '—' }}</dd></div>
                    <div><dt>Network</dt><dd>{{ $deposit->method_snapshot['crypto_network'] ?? '—' }}</dd></div>
                    <div><dt>Address</dt><dd class="mono" style="word-break:break-all">{{ $deposit->method_snapshot['crypto_address'] ?? '—' }}</dd></div>
                @endif
            </dl>

            <p class="hint" style="margin-top:.9rem;border-top:1px solid var(--rule);padding-top:.8rem">
                These are the details as they were when you submitted. Later changes to
                the payment method do not alter this record.
            </p>
        </div>

        <a class="btn btn-secondary" href="{{ route('wallet.show') }}" style="width:100%;margin-top:1rem">
            Back to wallet
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .8rem}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;word-break:break-all}

    .proof-link{display:block;position:relative;border:1px solid var(--rule);border-radius:10px;overflow:hidden}
    .proof-link img{display:block;width:100%;height:auto;max-height:420px;object-fit:contain;background:var(--paper-2)}
    .proof-link span{
        display:block;padding:.55rem .7rem;font-size:.78rem;color:var(--forest);
        border-top:1px solid var(--rule);text-decoration:underline;
    }
    .proof-link-broken::after{
        content:"The proof image could not be loaded.";
        display:block;padding:1.5rem;font-size:.82rem;color:var(--ink-3);text-align:center;
    }
</style>
@endpush
