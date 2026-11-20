<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\UserStatus;
use App\Models\Job;
use App\Models\User;

/**
 * Job visibility and ownership (FR-009).
 *
 * The public/private decision lives here rather than in each controller so a
 * new page cannot forget it. `view()` is the single gate that decides whether a
 * draft exists for a given visitor.
 */
class JobPolicy
{
    /**
     * Who may see this job.
     *
     * Note the return of `false` for a draft viewed by a stranger: the
     * controller turns that into a 404, not a 403. A 403 would confirm the id
     * exists, which would let anyone enumerate an agency's unannounced work.
     */
    public function view(?User $viewer, Job $job): bool
    {
        if ($viewer !== null) {
            if ($viewer->id === $job->poster_id) {
                return true;
            }

            if ($viewer->isAdmin()) {
                return true;
            }
        }

        // A draft is visible to nobody but its owner and admins.
        if ($job->status->value === 'draft') {
            return false;
        }

        return $job->status->isPubliclyVisible();
    }

    /** Only an approved poster may create jobs. */
    public function create(User $user): bool
    {
        return $user->isPoster() && $user->status === UserStatus::APPROVED;
    }

    /**
     * Only the owner may edit, and only while the brief is still editable.
     *
     * Once someone is hired the description is effectively a contract, so it
     * locks. A freelancer who quoted on specific scope must not find it changed
     * underneath them.
     */
    public function update(User $user, Job $job): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $job->poster_id && $job->status->isEditable();
    }

    public function delete(User $user, Job $job): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // A funded job cannot be deleted — money is held against it. It must be
        // refunded first, which is an auditable path.
        return $user->id === $job->poster_id && $job->isDraft();
    }

    /** Managing applicants is an owner-only action. */
    public function manage(User $user, Job $job): bool
    {
        return $user->isAdmin() || $user->id === $job->poster_id;
    }
}
