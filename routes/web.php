<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('welcome'))->name('home');

/*
|--------------------------------------------------------------------------
| Authentication routes
|--------------------------------------------------------------------------
| Only the named `login` route is required for now: Laravel's `auth` middleware
| redirects unauthenticated users to the route named `login`, and without it the
| middleware raises a 500 rather than redirecting. The full auth flow is T-005;
| this placeholder keeps the redirect contract intact until then.
*/

Route::get('/login', fn () => response('Sign in — implemented in T-005'))
    ->name('login');
