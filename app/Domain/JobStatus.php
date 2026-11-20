<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The life of a job (FR-009).
 *
 * The rule this enum exists to enforce: **a job is only public once its budget
 * is actually held in escrow.** That is the product's whole promise, so
 * "published" is not a flag a poster can set — it is a status the system grants
 * after money has moved.
 *
 *   draft       → created by the poster, invisible to everyone else
 *   published   → budget funded; visible and open to applications
 *   in_progress → a freelancer has been hired
 *   delivered   → work submitted, awaiting poster acceptance
 *   completed   → all milestones released
 *   refunded    → escrow returned to the poster
 *   archived    → removed from listings, kept for records
 *
 * Anything not reachable through allowedTransitions() throws, so a stray
 * assignment cannot skip escrow.
 */
enum JobStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case IN_PROGRESS = 'in_progress';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
    case ARCHIVED = 'archived';

    /** Status a new job starts in. Never public. */
    public static function initial(): self
    {
        return self::DRAFT;
    }

    /**
     * May a visitor see this job at all?
     *
     * Drafts and archived jobs return 404 rather than 403: a 403 would confirm
     * that a job with that id exists, which leaks a poster's unfinished work.
     */
    public function isPubliclyVisible(): bool
    {
        return match ($this) {
            self::PUBLISHED, self::IN_PROGRESS, self::DELIVERED, self::COMPLETED => true,
            self::DRAFT, self::REFUNDED, self::ARCHIVED => false,
        };
    }

    /** May a freelancer apply? Only while open and funded. */
    public function acceptsApplications(): bool
    {
        return $this === self::PUBLISHED;
    }

    /** May the poster still edit the brief? Locked once someone is hired. */
    public function isEditable(): bool
    {
        return match ($this) {
            self::DRAFT, self::PUBLISHED => true,
            self::IN_PROGRESS, self::DELIVERED, self::COMPLETED, self::REFUNDED, self::ARCHIVED => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Open',
            self::IN_PROGRESS => 'In progress',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Completed',
            self::REFUNDED => 'Refunded',
            self::ARCHIVED => 'Archived',
        };
    }

    /** Badge style key used by the views. */
    public function tone(): string
    {
        return match ($this) {
            self::DRAFT => 'draft',
            self::PUBLISHED => 'approved',
            self::IN_PROGRESS => 'pending',
            self::DELIVERED => 'pending',
            self::COMPLETED => 'approved',
            self::REFUNDED => 'suspended',
            self::ARCHIVED => 'deleted',
        };
    }

    /**
     * Where this status may go next.
     *
     * DRAFT → PUBLISHED is deliberately reachable only via EscrowService, which
     * is the only code allowed to call it. There is no route that sets this
     * status directly from request input.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::ARCHIVED],
            self::PUBLISHED => [self::IN_PROGRESS, self::REFUNDED, self::ARCHIVED],
            self::IN_PROGRESS => [self::DELIVERED, self::REFUNDED],
            self::DELIVERED => [self::COMPLETED, self::IN_PROGRESS],
            self::COMPLETED => [self::ARCHIVED],
            self::REFUNDED => [self::ARCHIVED],
            self::ARCHIVED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
