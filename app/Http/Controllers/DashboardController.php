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
 * A freelancer gets their own controller: their dashboard answers questions this
 * one cannot (open jobs, active work, earnings) and was too substantial to bolt
 * into a role branch. Everyone else renders here.
 *
 * A user who registers and sees nothing useful assumes the product is broken, so
 * every branch below explains its own state rather than showing an empty page.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // A freelancer's home lives in its own controller.
        if ($user->isFreelancer()) {
            return app(FreelancerDashboardController::class)($request);
        }

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
