<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

/**
 * Portfolio ownership (FR-008, AC-006).
 *
 * Object-level, not just "is signed in". Freelancer A must never be able to
 * edit or delete B's entry, and the check must hold for a direct request — not
 * only for a hidden button.
 */
class PortfolioPolicy
{
    /** Anyone may read a portfolio item belonging to a publicly visible freelancer. */
    public function view(?User $viewer, Portfolio $portfolio): bool
    {
        if ($viewer !== null && $viewer->id === $portfolio->user_id) {
            return true;
        }

        if ($viewer?->isAdmin()) {
            return true;
        }

        $owner = $portfolio->user;

        return $owner !== null && $owner->status->isPubliclyVisible();
    }

    public function create(User $user): bool
    {
        return $user->isFreelancer() && $user->canAct();
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->id === $portfolio->user_id
            || $user->isAdmin();
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }

    public function reorder(User $user): bool
    {
        return $user->isFreelancer() && $user->canAct();
    }
}
