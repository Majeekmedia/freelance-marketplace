<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Password reset (FR-005).
 *
 * Uses Laravel's password broker, which stores a hashed one-time token with an
 * expiry and deletes it on success. The key properties the tests must prove:
 *  - a token cannot be reused
 *  - an expired or invalid token is rejected
 *  - the response never reveals whether an email exists
 */
class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link.
     *
     * Always returns the same message regardless of whether the email exists.
     * If it differed, an attacker could enumerate registered accounts with this
     * form alone (FR-006).
     */
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // The broker silently does nothing for an unknown email.
        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            'If that email address is registered, a password reset link has been sent.'
        );
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Any existing session for this account becomes invalid.
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }
}
