<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Freelancer profile ownership (FR-007).
 *
 * Reading a public profile and editing a profile are different questions, so
 * they are separate methods rather than one `manage` check.
 */
class FreelancerProfilePolicy
{
    /** A visitor may read a profile only when it is public and approved. */
    public function view(?User $viewer, User $owner): bool
    {
        if ($viewer !== null && ($viewer->id === $owner->id || $viewer->isAdmin())) {
            return true;
        }

        if (! $owner->status->isPubliclyVisible()) {
            return false;
        }

        $profile = $owner->freelancerProfile;

        return $profile === null || $profile->is_public;
    }

    public function update(User $user, User $owner): bool
    {
        return $user->id === $owner->id || $user->isAdmin();
    }

    /**
     * Editing requires an approved account.
     *
     * Deliberately stricter than `update`: a pending user may complete their
     * profile (so an admin has something to review) but this method guards the
     * act of publishing it publicly.
     */
    public function publish(User $user, User $owner): bool
    {
        return $user->id === $owner->id && $user->canAct();
    }
}
