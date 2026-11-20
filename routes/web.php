<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDirectoryController;
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
| Public directory
|--------------------------------------------------------------------------
| Deliberately outside the `auth` group: a visitor must be able to judge a
| freelancer before creating an account. The policy decides visibility, and a
| pending or private profile is unreachable.
*/

Route::get('/freelancers', [PublicDirectoryController::class, 'freelancers'])->name('freelancers.index');
Route::get('/freelancers/{freelancer}', [ProfileController::class, 'show'])->name('freelancers.show');

/*
|--------------------------------------------------------------------------
| Freelancer own area
|--------------------------------------------------------------------------
| No `account` gate on editing the profile: a pending freelancer must be able
| to fill it in so an admin has something to review. Publishing publicly is
| gated separately by the policy.
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [PortfolioController::class, 'store'])
        ->middleware('account')
        ->name('portfolio.store');
    Route::put('/portfolio/{portfolio}', [PortfolioController::class, 'update'])
        ->middleware('account')
        ->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroy'])
        ->middleware('account')
        ->name('portfolio.destroy');
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
