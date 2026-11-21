<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin sign-in, at a separate URL from the public login (FR-002).
 *
 * This is a **hard separation**, and it is symmetric:
 *
 *   - a non-admin who finds /admin/login cannot sign in through it, so the
 *     admin surface cannot be reached with a customer credential
 *   - an admin who tries the public /login is refused there (see
 *     `LoginController`), so an admin session cannot be created from the
 *     customer-facing door
 *
 * Both directions matter. Blocking only one leaves a route to a privileged
 * session, and the interesting attack on a marketplace is a phishing page that
 * mimics whichever login is easier to reach.
 *
 * Every failure produces the same message, for the same reason as the public
 * form: the response must not reveal whether an account exists, whether it is an
 * admin, or whether the password was merely wrong.
 */
class AdminLoginController extends Controller
{
    /** Attempts allowed before a cooldown. Tighter than the public form. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            $this->fail();
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        /*
         * Role and status are checked AFTER credentials, and both end the same
         * way. Checking the role first would make a valid customer credential
         * return "not an admin", which confirms the credential is real.
         */
        if ($user === null || ! $user->isAdmin() || ! $user->canAuthenticate()) {
            $this->fail();
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.overview'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Log out and throw the generic credential error.
     *
     * Extracted so every failure path is provably identical — the test asserts
     * that all four failure cases produce a byte-identical message.
     */
    private function fail(): never
    {
        Auth::logout();

        throw ValidationException::withMessages([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'admin-login|'.mb_strtolower((string) $request->input('email')).'|'.$request->ip();
    }
}
