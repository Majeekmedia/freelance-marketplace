<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DepositRequest;
use App\Models\User;

/**
 * Deposit request access (FR-039e).
 *
 * The rule that matters: a proof image is readable only by the poster who
 * submitted it and by an admin. Never by another poster, never by a freelancer,
 * never by an unauthenticated visitor.
 *
 * This is object-level and it covers the *file*, not just the row — the
 * controller asks this policy before streaming the proof.
 */
class DepositRequestPolicy
{
    public function view(User $user, DepositRequest $deposit): bool
    {
        return $user->id === $deposit->user_id || $user->isAdmin();
    }

    /**
     * Reading the uploaded proof image.
     *
     * Same rule as viewing the request, stated separately because it is the more
     * sensitive of the two: the row is a claim, the image is a bank screenshot.
     */
    public function viewProof(User $user, DepositRequest $deposit): bool
    {
        return $this->view($user, $deposit);
    }

    public function create(User $user): bool
    {
        return $user->isPoster() && $user->canAct();
    }

    /**
     * Only an admin decides.
     *
     * Deliberately not the owner: approving your own deposit request would be
     * the same as minting money.
     */
    public function review(User $user, DepositRequest $deposit): bool
    {
        return $user->isAdmin() && $deposit->isPending();
    }
}
