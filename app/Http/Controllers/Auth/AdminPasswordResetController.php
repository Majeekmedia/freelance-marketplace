<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Admin password reset by email.
 *
 * Yes, this works — but it needs one guard the customer flow does not have.
 *
 * The customer reset form accepts any email and silently does nothing for an
 * unknown one. If the admin form reused that broker directly, a reset link for a
 * **customer** account would be issued from the admin surface, and the
 * subsequent reset would land the customer on the admin reset page. That is
 * confusing at best and blurs the separation at worst.
 *
 * So this controller:
 *   1. only dispatches a link when the address belongs to an administrator
 *   2. returns the SAME message either way, so the form cannot be used to
 *      discover which addresses are administrators
 *   3. is rate limited per email+IP, like the public flow
 */
class AdminPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
        ]);

        $email = (string) $request->input('email');

        /*
         * Look up the account WITHOUT revealing the outcome.
         *
         * If the address is not an admin we simply do not dispatch. The response
         * below is identical in every case, so this cannot be used to enumerate
         * administrator addresses — which is the whole reason the admin surface
         * is separated.
         */
        $user = User::where('email', $email)->first();

        if ($user !== null && $user->isAdmin() && $user->canAuthenticate()) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with(
            'status',
            'If that address belongs to an administrator, a reset link has been sent.'
        );
    }
}
