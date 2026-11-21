<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Where a deposit request stands (FR-039, FR-040).
 *
 * The state machine is small on purpose. A deposit is either waiting for a human
 * to look at it, or it has been decided, and "decided" is terminal — approving a
 * rejected request, or vice versa, would make the ledger history contradict
 * itself.
 */
enum DepositStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Awaiting review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::APPROVED => 'approved',
            self::REJECTED => 'suspended',
        };
    }

    /** Has a human already decided this one? */
    public function isDecided(): bool
    {
        return $this !== self::PENDING;
    }

    /**
     * Where this status may go.
     *
     * Both approved and rejected are terminal. Allowing approved → rejected
     * would mean a ledger credit already exists for a request now marked
     * rejected, which is a contradiction nobody can reconcile.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED, self::REJECTED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
