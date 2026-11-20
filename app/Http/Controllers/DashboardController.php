<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing surface for an authenticated user.
 *
 * Renders a different dashboard per account type, and — critically — explains
 * the pending state rather than showing an empty page. A user who registers and
 * sees nothing useful assumes the product is broken.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'isPending' => $user->isPending(),
            'dashboardView' => $this->dashboardFor($user),
        ]);
    }

    private function dashboardFor(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin';
        }

        return $user->isPoster() ? 'poster' : 'freelancer';
    }
}
