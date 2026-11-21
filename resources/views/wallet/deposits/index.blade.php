@extends('layouts.app')

@section('title', 'Fund your wallet')

@section('content')
<a href="{{ route('wallet.show') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Wallet
</a>

<h1>Fund your wallet</h1>
<p class="sub">Transfer using one of the methods below, then submit the details so we can confirm it.</p>

<div class="fund-summary">
    <div>
        <div class="fund-label">Available now</div>
        <div class="fund-amount">{{ $available->format() }}</div>
    </div>
    @if ($pendingTotal->isPositive())
        <div>
            <div class="fund-label">Awaiting confirmation</div>
            <div class="fund-amount fund-amount-pending">{{ $pendingTotal->format() }}</div>
        </div>
    @endif
</div>

@if ($methods->isEmpty())
    {{--
        Honest empty state. Telling a poster to "make a transfer" with nowhere to
        send it would be worse than saying the feature is not ready.
    --}}
    <div class="card" style="text-align:center;padding:3rem 1.5rem">
        <p style="margin:0 0 .4rem;font-weight:500">Funding is not open yet</p>
        <p style="margin:0;font-size:.85rem;color:var(--ink-3);line-height:1.55">
            No payment methods are available at the moment. Please check back, or
            contact support if you need to fund urgently.
        </p>
    </div>
@else

<h2 class="fund-head">Step 1 — Send the transfer</h2>
<p class="fund-note">
    Transfer to one of these. Copy the details exactly; this is the destination the
    admin has set up.
</p>

<div class="fund-methods">
    @foreach ($methods as $method)
        <div class="card fund-method" data-method-id="{{ $method->id }}">
            <div class="fund-method-head">
                <span class="fund-method-label">{{ $method->label }}</span>
                <span class="badge badge-freelancer">{{ $method->type->label() }}</span>
            </div>

            <dl class="facts" style="margin-top:.9rem">
                @if ($method->type === \App\Domain\PaymentMethodType::BANK_TRANSFER)
                    <div><dt>Bank</dt><dd>{{ $method->bank_name }}</dd></div>
                    <div><dt>Account name</dt><dd>{{ $method->account_name }}</dd></div>
                    <div>
                        <dt>Account number</dt>
                        <dd class="mono">{{ $method->account_number }}</dd>
                    </div>
                    @if ($method->routing_detail)
                        <div><dt>Routing / sort code</dt><dd class="mono">{{ $method->routing_detail }}</dd></div>
                    @endif
                @else
                    <div><dt>Asset</dt><dd>{{ $method->crypto_asset }}</dd></div>
                    <div>
                        <dt>Network</dt>
                        <dd>
                            {{ $method->crypto_network }}
                            <span class="fund-warn">Send on this network only.</span>
                        </dd>
                    </div>
                    <div>
                        <dt>Address</dt>
                        <dd class="mono fund-address">{{ $method->crypto_address }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endforeach
</div>

<h2 class="fund-head">Step 2 — Tell us what you sent</h2>
<p class="fund-note">
    Your balance is not credited until an admin confirms the transfer. Submitting
    this form only puts it in the review queue.
</p>

<form method="POST" action="{{ route('wallet.deposits.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card">
        <div class="field">
            <label for="payment_method_id">Paid into</label>
            <select id="payment_method_id" name="payment_method_id" required>
                <option value="">Choose the method you used</option>
                @foreach ($methods as $method)
                    <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>
                        {{ $method->label }} — {{ $method->summary() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="duo field" style="max-width:560px">
            <div>
                <label for="amount">Amount sent</label>
                <input id="amount" name="amount" type="text" inputmode="decimal" required
                       value="{{ old('amount') }}" placeholder="250.00">
                <p class="hint">The amount you actually transferred.</p>
            </div>
            <div>
                <label for="transfer_reference">Reference</label>
                <input id="transfer_reference" name="transfer_reference" type="text" required
                       minlength="4" maxlength="255"
                       value="{{ old('transfer_reference') }}"
                       placeholder="Bank ref or transaction hash">
            </div>
        </div>

        <div class="field">
            <label for="proof">Proof of transfer</label>
            <input id="proof" name="proof" type="file" required
                   accept="image/png,image/jpeg,image/webp">
            <p class="hint">
                A screenshot or photo showing the amount, the destination and the reference.
                PNG, JPEG or WebP, up to {{ intdiv(\App\Support\DepositProof::MAX_KILOBYTES, 1024) }} MB.
                Only you and the admin can see it.
            </p>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Submit for review</button>
            <a class="btn btn-secondary" href="{{ route('wallet.show') }}">Cancel</a>
        </div>
    </div>
</form>
@endif

@if ($deposits->isNotEmpty())
    <h2 class="fund-head">Your deposit requests</h2>

    <div class="cardlist">
        @foreach ($deposits as $deposit)
            <a class="card deposit-row" href="{{ route('wallet.deposits.show', $deposit) }}">
                <div class="deposit-main">
                    <div class="deposit-title">
                        {{ $deposit->requestedAmount()->format() }}
                        <span class="badge badge-{{ $deposit->status->tone() }}">{{ $deposit->status->label() }}</span>
                    </div>
                    <div class="deposit-meta">
                        {{ $deposit->method_snapshot['label'] ?? 'Removed method' }}
                        <span class="deposit-sep" aria-hidden="true">·</span>
                        {{ $deposit->created_at->format('j M Y') }}
                        <span class="deposit-sep" aria-hidden="true">·</span>
                        {{ $deposit->transfer_reference }}
                    </div>
                    @if ($deposit->status === \App\Domain\DepositStatus::APPROVED && $deposit->wasAdjusted())
                        <p class="deposit-adjusted">
                            Credited {{ $deposit->creditedAmount()?->format() }} — the amount received
                            differed from the amount sent.
                        </p>
                    @endif
                    @if ($deposit->status === \App\Domain\DepositStatus::REJECTED && $deposit->rejection_reason)
                        <p class="deposit-reason">{{ $deposit->rejection_reason }}</p>
                    @endif
                </div>
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
    .fund-summary{display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:2rem}
    .fund-label{font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);font-weight:600}
    .fund-amount{font-family:var(--serif);font-size:1.7rem;line-height:1.2;margin-top:.2rem;font-variant-numeric:tabular-nums}
    .fund-amount-pending{color:var(--amber)}

    .fund-head{font-family:var(--serif);font-weight:400;font-size:1.25rem;margin:2rem 0 .4rem}
    .fund-note{font-size:.84rem;color:var(--ink-3);margin:0 0 1rem;line-height:1.55;max-width:60ch}

    .fund-methods{display:grid;gap:.75rem;margin-bottom:.5rem}
    .fund-method{padding:1.15rem 1.25rem}
    .fund-method + .fund-method{margin-top:0}
    .fund-method-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
    .fund-method-label{font-weight:600;font-size:.95rem}
    .fund-warn{display:block;font-size:.75rem;color:var(--amber);margin-top:.2rem}
    .fund-address{word-break:break-all;font-size:.82rem}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;letter-spacing:.01em}

    .field{margin-bottom:1rem}
    .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .field input,.field select{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);min-width:0;
    }
    .field input[type="file"]{padding:.5rem;font-size:.85rem}
    .field input:focus,.field select:focus{outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest)}
    .hint{font-size:.75rem;color:var(--ink-3);margin:.35rem 0 0;line-height:1.5}
    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}
    @media (max-width:560px){ .form-actions .btn{flex:1} }

    .deposit-row{display:block;transition:border-color .15s ease}
    .deposit-row:hover{border-color:var(--rule-2)}
    .deposit-title{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-weight:500;font-size:.95rem}
    .deposit-meta{font-size:.78rem;color:var(--ink-3);margin-top:.35rem;line-height:1.5}
    .deposit-sep{color:var(--rule-2)}
    .deposit-adjusted{font-size:.78rem;color:var(--amber);margin:.45rem 0 0;line-height:1.5}
    .deposit-reason{font-size:.78rem;color:var(--danger);margin:.45rem 0 0;line-height:1.5}
</style>
@endpush
