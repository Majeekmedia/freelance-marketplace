<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Where an account stands, independent of what type it is.
 *
 * The important distinction for this product (FR-004):
 *   pending  → can log in and browse, but CANNOT act
 *   approved → full permissions for its type
 *   suspended→ cannot log in
 *
 * Approval gates *actions*, not login. That was a deliberate decision: a user
 * who cannot log in cannot edit the profile the admin needs to review.
 */
enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    /** Status a new registration starts in. */
    public static function initial(): self
    {
        return self::PENDING;
    }

    /** May this account perform gated actions (apply, post, fund, message)? */
    public function canAct(): bool
    {
        return $this === self::APPROVED;
    }

    /** May this account authenticate at all? */
    public function canAuthenticate(): bool
    {
        return match ($this) {
            self::PENDING, self::APPROVED => true,
            self::REJECTED, self::SUSPENDED, self::DELETED => false,
        };
    }

    /** Statuses a user may hold and still appear in public listings. */
    public function isPubliclyVisible(): bool
    {
        return $this === self::APPROVED;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SUSPENDED => 'Suspended',
            self::DELETED => 'Deleted',
        };
    }

    /** Statuses an admin may transition to from this one. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::SUSPENDED, self::DELETED],
            self::REJECTED => [self::PENDING],
            self::SUSPENDED => [self::APPROVED, self::DELETED],
            self::DELETED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
