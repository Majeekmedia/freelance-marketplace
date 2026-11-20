@extends('layouts.app')

@section('title', 'Payment methods')

@section('content')
<div class="page-head">
    <div>
        <h1>Payment methods</h1>
        <p class="sub">How posters fund their wallet. Disabled methods are hidden from posters.</p>
    </div>
    <a class="btn" href="{{ route('admin.payment-methods.create') }}">Add method</a>
</div>

@if ($methods->isEmpty())
    <div class="card" style="text-align:center;padding:3rem 1.5rem;margin-top:1.5rem">
        <p style="margin:0 0 .4rem;font-weight:500">No payment methods yet</p>
        <p style="margin:0 0 1.2rem;font-size:.85rem;color:var(--ink-3)">
            Posters cannot fund a wallet until at least one method is enabled.
        </p>
        <a class="btn" href="{{ route('admin.payment-methods.create') }}">Add the first method</a>
    </div>
@else
    <div class="cardlist" style="margin-top:1.5rem">
        @foreach ($methods as $method)
            <div class="card pm-row">
                <div class="pm-main">
                    <div class="pm-title">
                        {{ $method->label }}
                        @if ($method->trashed())
                            <span class="badge badge-deleted">Deleted</span>
                        @elseif ($method->is_enabled)
                            <span class="badge badge-approved">Enabled</span>
                        @else
                            <span class="badge badge-pending">Disabled</span>
                        @endif

                        @unless ($method->isComplete())
                            <span class="badge badge-suspended">Incomplete</span>
                        @endunless
                    </div>

                    <div class="pm-meta">
                        {{ $method->type->label() }}
                        <span class="pm-sep" aria-hidden="true">·</span>
                        {{ $method->summary() }}
                    </div>

                    @unless ($method->isComplete())
                        <p class="pm-warning">
                            Missing: {{ implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), $method->missingFields())) }}.
                            Posters cannot use this method until it is complete.
                        </p>
                    @endunless
                </div>

                <div class="pm-actions">
                    @unless ($method->trashed())
                        <form method="POST" action="{{ route('admin.payment-methods.toggle', $method) }}" style="margin:0">
                            @csrf
                            <button class="btn btn-secondary btn-sm" type="submit">
                                {{ $method->is_enabled ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    @endunless

                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.payment-methods.edit', $method) }}">Edit</a>

                    @unless ($method->trashed())
                        <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" style="margin:0"
                              onsubmit="return confirm('Remove this payment method? Existing deposit requests keep their record of it.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('styles')
<style>
    .pm-row{display:flex;align-items:center;justify-content:space-between;gap:1.25rem}
    .pm-main{min-width:0}
    .pm-title{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;font-weight:500;font-size:.95rem}
    .pm-meta{font-size:.8rem;color:var(--ink-3);margin-top:.35rem;line-height:1.5}
    .pm-sep{color:var(--rule-2)}
    .pm-warning{font-size:.78rem;color:var(--amber);margin:.45rem 0 0;line-height:1.5}
    .pm-actions{display:flex;gap:.4rem;flex-wrap:wrap;flex:none}

    @media (max-width:700px){
        .pm-row{flex-direction:column;align-items:stretch;gap:.9rem}
        .pm-actions{border-top:1px solid var(--rule);padding-top:.85rem}
        .pm-actions form{flex:1;display:flex}
        .pm-actions .btn{flex:1}
        .pm-actions a.btn{flex:1}
    }
</style>
@endpush
