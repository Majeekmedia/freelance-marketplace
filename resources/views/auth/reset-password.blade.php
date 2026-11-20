@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')
<div class="card">
    <h1>Choose a new password</h1>
    <p class="sub">This link can only be used once.</p>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                   autocomplete="email" required>
        </div>

        <div class="field">
            <label for="password">New password</label>
            <input id="password" type="password" name="password"
                   autocomplete="new-password" required autofocus>
            <p class="hint">At least 8 characters, with letters and numbers.</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   autocomplete="new-password" required>
        </div>

        <button class="btn" type="submit">Reset password</button>
    </form>
</div>
@endsection
