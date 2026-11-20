<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks gated actions for accounts that are not approved (FR-004).
 *
 * Deliberately distinct from authentication: a pending account CAN log in and
 * browse and edit its own profile — it just cannot apply, post, fund or message.
 * Approving *actions* rather than *login* means an admin never has to let
 * someone in to see the profile they are reviewing (assumption A7).
 *
 * A suspended, rejected or deleted account is logged out entirely, because
 * `status->canAuthenticate()` is false for those.
 */
class AccountGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        // A suspended account should not hold a usable session at all.
        if (! $user->canAuthenticate()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'This account is not permitted to sign in.');
        }

        if (! $user->canAct()) {
            abort(403, 'Your account is awaiting approval. You can browse, but not act yet.');
        }

        return $next($request);
    }
}
