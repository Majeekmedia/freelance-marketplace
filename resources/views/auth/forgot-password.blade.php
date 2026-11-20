@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')
<div class="card">
    <h1>Reset your password</h1>
    <p class="sub">Enter your email address and we will send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" required autofocus>
        </div>

        <button class="btn" type="submit">Send reset link</button>
    </form>

    <p class="alt"><a href="{{ route('login') }}">Back to sign in</a></p>
</div>
@endsection
