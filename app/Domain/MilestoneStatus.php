<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A milestone's position in its own small lifecycle (FR-032).
 *
 * The money rule (T-013 onward): a milestone amount counts against the job
 * budget from the moment the job is funded, and is only ever released by the
 * admin. `released` and `refunded` are terminal — a released payment cannot be
 * quietly reversed by another release call.
 */
enum MilestoneStatus: string
{
    case PENDING = 'pending';
    case DELIVERED = 'delivered';
    case ACCEPTED = 'accepted';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'In progress',
            self::DELIVERED => 'Delivered',
            self::ACCEPTED => 'Accepted',
            self::RELEASED => 'Released',
            self::REFUNDED => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::DELIVERED => 'pending',
            self::ACCEPTED => 'approved',
            self::RELEASED => 'approved',
            self::REFUNDED => 'suspended',
        };
    }

    /** Money has already left escrow for this milestone. */
    public function isSettled(): bool
    {
        return $this === self::RELEASED || $this === self::REFUNDED;
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::DELIVERED, self::REFUNDED],
            self::DELIVERED => [self::ACCEPTED, self::PENDING, self::REFUNDED],
            self::ACCEPTED => [self::RELEASED, self::REFUNDED],
            self::RELEASED => [],
            self::REFUNDED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
