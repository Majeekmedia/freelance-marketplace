@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<div class="card">
    <h1>Sign in</h1>
    <p class="sub">Welcome back.</p>

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

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password"
                   autocomplete="current-password" required>
        </div>

        <div class="row-between">
            <label class="checkbox" for="remember">
                <input id="remember" type="checkbox" name="remember" value="1">
                Remember me
            </label>
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button class="btn" type="submit">Sign in</button>
    </form>

    <p class="alt">No account yet? <a href="{{ route('register') }}">Create one</a></p>
</div>
@endsection
