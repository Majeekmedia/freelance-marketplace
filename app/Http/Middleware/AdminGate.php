<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the admin role on every admin route.
 *
 * This is the single enforcement point for admin access. Hiding a nav link is
 * not access control (FR-002 of the security steering), so every route under
 * the admin prefix passes through here rather than relying on a view condition.
 *
 * The role check is on `type`, not `status`: an admin account is created
 * approved, and an admin whose status somehow became non-approved is caught by
 * AccountGate which runs first.
 */
class AdminGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! $user->isAdmin()) {
            // 404 rather than 403: do not confirm that an admin area exists to
            // an account that has no business knowing.
            abort(404);
        }

        return $next($request);
    }
}
