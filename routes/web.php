<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobManageController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDirectoryController;
use App\Http\Controllers\WalletController;
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

    /*
     * Wallet. Deliberately has no {user} segment: the wallet shown is always
     * the signed-in user's, so there is no id to tamper with (FR-018).
     */
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
});

/*
|--------------------------------------------------------------------------
| Deposits (FR-039)
|--------------------------------------------------------------------------
| Posting a deposit is gated on an approved poster: a pending account has no
| business claiming a transfer. Reading the proof is authorised per record in
| the controller, because the image is only for the poster and an admin.
*/
Route::middleware(['auth', 'account'])->group(function (): void {
    Route::get('/wallet/deposits', [DepositController::class, 'index'])->name('wallet.deposits.index');
    Route::post('/wallet/deposits', [DepositController::class, 'store'])->name('wallet.deposits.store');
    Route::get('/wallet/deposits/{deposit}', [DepositController::class, 'show'])
        ->whereNumber('deposit')
        ->name('wallet.deposits.show');
    Route::get('/wallet/deposits/{deposit}/proof', [DepositController::class, 'proof'])
        ->whereNumber('deposit')
        ->name('wallet.deposits.proof');
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
| Public jobs
|--------------------------------------------------------------------------
| A draft or archived job 404s rather than 403s: a 403 would confirm that a
| given id exists, which would let anyone enumerate unannounced work.
|
| `{job}` binds by id for now. Slugs would be friendlier but must be unique
| and immutable-ish; that is a later refinement, not a correctness issue.
*/

Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])
    ->whereNumber('job')
    ->name('jobs.show');

/*
|--------------------------------------------------------------------------
| Poster job management
|--------------------------------------------------------------------------
| Drafts are created and edited here. There is deliberately NO publish route:
| a job becomes public only when EscrowService has moved the budget into an
| escrow hold, which is a money operation, not a form submission.
*/

Route::middleware(['auth', 'account'])->group(function (): void {
    Route::get('/jobs/manage', [JobManageController::class, 'index'])->name('jobs.manage.index');
    Route::get('/jobs/manage/create', [JobManageController::class, 'create'])->name('jobs.manage.create');
    Route::post('/jobs/manage', [JobManageController::class, 'store'])->name('jobs.manage.store');
    Route::get('/jobs/manage/{job}', [JobManageController::class, 'show'])
        ->whereNumber('job')
        ->name('jobs.manage.show');
    Route::get('/jobs/manage/{job}/edit', [JobManageController::class, 'edit'])
        ->whereNumber('job')
        ->name('jobs.manage.edit');
    Route::put('/jobs/manage/{job}', [JobManageController::class, 'update'])
        ->whereNumber('job')
        ->name('jobs.manage.update');
    Route::delete('/jobs/manage/{job}', [JobManageController::class, 'destroy'])
        ->whereNumber('job')
        ->name('jobs.manage.destroy');
});

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

/*
|--------------------------------------------------------------------------
| Admin console
|--------------------------------------------------------------------------
| A separate surface from the customer app: its own sign-in, its own layout,
| its own navigation. Two reasons, both deliberate:
|
|  1. The public /login is the page a phishing kit clones. If a privileged
|     session could be created from it, the admin door would be decorative.
|  2. Admin navigation shares almost nothing with the customer app. Bolting
|     "Accounts" into a freelancer's menu made both experiences worse.
|
| The login routes sit OUTSIDE the `admin` gate — you cannot be an admin before
| you have signed in.
*/

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');
    });

    Route::post('/logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});

/*
| The protected console.
|
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
        Route::get('/', OverviewController::class)->name('overview');
        Route::redirect('/home', '/admin')->name('home');

        // Sections whose feature is not built yet. Named routes so the nav can
        // link honestly instead of hiding items.
        Route::get('/soon/{section}', PlaceholderController::class)->name('placeholder');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reinstate', [AdminUserController::class, 'reinstate'])->name('users.reinstate');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        /*
         * Payment methods (FR-038).
         *
         * `toggle` is a POST rather than a PUT because it is a single explicit
         * action, and a POST cannot be triggered by a stray prefetch or a link
         * crawler the way a GET could.
         */
        Route::get('/payment-methods', [AdminPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::get('/payment-methods/create', [AdminPaymentMethodController::class, 'create'])->name('payment-methods.create');
        Route::post('/payment-methods', [AdminPaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::get('/payment-methods/{paymentMethod}/edit', [AdminPaymentMethodController::class, 'edit'])->name('payment-methods.edit');
        Route::put('/payment-methods/{paymentMethod}', [AdminPaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::post('/payment-methods/{paymentMethod}/toggle', [AdminPaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
        Route::delete('/payment-methods/{paymentMethod}', [AdminPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        /*
         * Deposit review queue (FR-040).
         *
         * Approving is the only path in the product that creates money, so both
         * decisions are POST and the service behind them re-checks the status
         * under a row lock.
         */
        Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
        Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])
            ->whereNumber('deposit')
            ->name('deposits.show');
        Route::get('/deposits/{deposit}/proof', [AdminDepositController::class, 'proof'])
            ->whereNumber('deposit')
            ->name('deposits.proof');
        Route::post('/deposits/{deposit}/approve', [AdminDepositController::class, 'approve'])
            ->whereNumber('deposit')
            ->name('deposits.approve');
        Route::post('/deposits/{deposit}/reject', [AdminDepositController::class, 'reject'])
            ->whereNumber('deposit')
            ->name('deposits.reject');
    });
