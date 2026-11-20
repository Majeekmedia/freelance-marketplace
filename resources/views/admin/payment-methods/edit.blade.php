@extends('layouts.app')

@section('title', 'Edit payment method')

@section('content')
<a href="{{ route('admin.payment-methods.index') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Payment methods
</a>

<h1>Edit payment method</h1>
<p class="sub">
    Editing changes the instructions for <strong>future</strong> deposits only.
    Requests already submitted keep the details the poster was shown.
</p>

<form method="POST" action="{{ route('admin.payment-methods.update', $method) }}">
    @csrf
    @method('PUT')
    <div class="card pm-form">
        @include('admin.payment-methods._form', ['method' => $method, 'types' => $types])

        <div class="form-actions">
            <button class="btn" type="submit">Save changes</button>
            <a class="btn btn-secondary" href="{{ route('admin.payment-methods.index') }}">Cancel</a>
        </div>
    </div>
</form>
@endsection

@push('styles')
@include('admin.payment-methods._form-styles')
@endpush
