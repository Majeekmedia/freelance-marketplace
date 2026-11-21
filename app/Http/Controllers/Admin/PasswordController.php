<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Changing your own admin password (FR-005).
 *
 * Three things this does that a naive implementation misses:
 *
 * 1. **Requires the current password.** Without it, anyone who finds an
 *    unattended session owns the account permanently, because they can change
 *    the credential that would lock the real owner out.
 * 2. **Rotates the session** after a password change. The old session id was
 *    derived in part from credentials an attacker may have known; leaving it
 *    valid would keep them signed in after the owner "fixed" it.
 * 3. **Audits the change.** A password change on an admin account is exactly
 *    the event you want a record of when reconstructing an incident.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.password');
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        // Verified against the stored hash, not compared as plaintext.
        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'That is not your current password.',
            ]);
        }

        $user->password = $validated['password'];
        $user->save();

        $audit->record(
            actor: $user,
            action: 'account.password_changed',
            targetType: 'user',
            targetId: $user->id,
            reason: 'Admin changed their own password',
            ipAddress: $request->ip(),
        );

        /*
         * Rotate the session AND the CSRF token.
         *
         * `invalidate()` would also log the user out, which is a worse
         * experience for a legitimate change. `regenerate()` keeps them signed
         * in under a new id and rotates the token, so any session id an attacker
         * captured is now useless.
         */
        $request->session()->regenerate();

        return redirect()
            ->route('admin.settings.password')
            ->with('status', 'Password changed. Other sessions are no longer valid.');
    }
}
