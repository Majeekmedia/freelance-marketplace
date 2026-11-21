@extends('layouts.admin')

@section('title', 'Change password')

@section('content')
<h1>Change password</h1>
<p class="sub">Your admin password. Changing it signs out every other session.</p>

<form method="POST" action="{{ route('admin.settings.password.update') }}" style="max-width:460px">
    @csrf

    <div class="card">
        <div class="field">
            <label for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" required
                   autocomplete="current-password">
            <p class="hint">Required — it proves the session owner is present.</p>
        </div>

        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" required
                   autocomplete="new-password">
            <p class="hint">
                At least 12 characters, with upper and lower case, a number and a symbol.
            </p>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password">
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Change password</button>
            <a class="btn btn-secondary" href="{{ route('admin.overview') }}">Cancel</a>
        </div>
    </div>
</form>

<div class="card" style="max-width:460px">
    <h2 class="section-head">Forgotten it?</h2>
    <p class="note" style="border:0;padding:0;margin:0 0 .9rem">
        A reset link can be emailed to your account. Only an address registered as
        an administrator receives one, and the page confirms nothing either way.
    </p>
    <a class="btn btn-secondary btn-sm" href="{{ route('admin.password.request') }}">
        Send a reset link
    </a>
</div>
@endsection

@push('styles')
<style>
    .field{margin-bottom:1rem}
    .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .field input{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);
    }
    .field input:focus{outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest)}
    .hint{font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0;line-height:1.5}
    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}
    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .7rem}
    @media (max-width:560px){ .form-actions .btn{flex:1} }
</style>
@endpush
