<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Attempts allowed per email+IP pair before throttling. */
    private const MAX_ATTEMPTS = 5;

    /** Throttle window in seconds. */
    private const DECAY_SECONDS = 60;

    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate a user.
     *
     * Generic failure message on purpose: the response is identical whether the
     * email does not exist or the password is wrong, so the endpoint cannot be
     * used to enumerate accounts (FR-006).
     *
     * Throttling is per email+IP, which resists both a single-account attack and
     * a spray across many accounts from one source, without a permanent lockout
     * that an attacker could use against a victim.
     */
    public function store(LoginRequest $request): RedirectResponse
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

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        // Status is checked AFTER credentials so that a suspended account gets
        // the same message as a wrong password — status must not leak either.
        //
        // An administrator is refused here too, with the identical message.
        // Admins have a separate door (`/admin/login`) and allowing them through
        // the public form would make that separation decorative. Saying "use the
        // admin login" would confirm the address is an administrator, which is
        // exactly the fact worth hiding — so this path is byte-identical to a
        // wrong password.
        $user = Auth::user();

        if ($user !== null && (! $user->canAuthenticate() || $user->isAdmin())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function throttleKey(LoginRequest $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip()
        );
    }
}
