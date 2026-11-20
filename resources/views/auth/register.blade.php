@extends('layouts.auth')

@section('title', 'Create an account')

@section('content')
<div class="card">
    <h1>Create an account</h1>
    <p class="sub">Choose how you want to use the platform. This cannot be changed later.</p>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <fieldset style="border:0;padding:0;margin:0 0 1.1rem">
            <legend class="sr-only">Account type</legend>
            <div class="choice">
                <input id="type-freelancer" type="radio" name="type" value="freelancer"
                       @checked(old('type', 'freelancer') === 'freelancer')>
                <label for="type-freelancer">
                    <b>Freelancer</b>
                    <span>Find work, apply, get paid</span>
                </label>

                <input id="type-poster" type="radio" name="type" value="poster"
                       @checked(old('type') === 'poster')>
                <label for="type-poster">
                    <b>Job poster</b>
                    <span>Post funded jobs, hire</span>
                </label>
            </div>
        </fieldset>

        <div class="field">
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   autocomplete="name" required autofocus>
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" required>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password"
                   autocomplete="new-password" required>
            <p class="hint">At least 8 characters, with letters and numbers.</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   autocomplete="new-password" required>
        </div>

        <button class="btn" type="submit">Create account</button>
    </form>

    <p class="alt">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
</div>
@endsection
