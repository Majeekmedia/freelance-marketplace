<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('welcome'))->name('home');

/*
|--------------------------------------------------------------------------
| Guest-only auth routes
|--------------------------------------------------------------------------
| Throttles are applied per route as well as inside LoginController, because a
| route-level limit protects the endpoint even if a future controller forgets.
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:3,10')
        ->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:3,10')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
| No `account` gate here on purpose: a pending user must be able to sign in and
| reach their dashboard. Approval gates actions, not login (assumption A7), and
| the dashboard tells them their account is awaiting approval.
*/

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Both gates:
|   `admin`   → requires the admin type (404 otherwise, so the area is not
|               confirmed to accounts that have no business knowing)
|   `account` → requires an approved status; an admin is created approved, so
|               this is defence in depth rather than the primary control
*/

Route::middleware(['auth', 'admin', 'account'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::redirect('/', '/admin/users')->name('home');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reinstate', [AdminUserController::class, 'reinstate'])->name('users.reinstate');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
