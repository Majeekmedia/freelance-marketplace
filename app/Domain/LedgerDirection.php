<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Which way money moved.
 *
 * Direction is a separate column rather than the sign of the amount (FR-029).
 * Two reasons, both practical:
 *
 *  1. `amount_cents > 0` can be enforced by a database constraint. A signed
 *     amount cannot, so a stray negative would be indistinguishable from a bug.
 *  2. Summing is explicit and readable in SQL: credit minus debit. A signed sum
 *     invites someone to write `SUM(amount)` and quietly produce a wrong number.
 */
enum LedgerDirection: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    /** +1 for a credit, -1 for a debit. Used when summing in PHP. */
    public function sign(): int
    {
        return $this === self::CREDIT ? 1 : -1;
    }

    /** Apply this direction to an amount, for reconciliation sums. */
    public function apply(int $minorUnits): int
    {
        return $minorUnits * $this->sign();
    }

    public function label(): string
    {
        return $this === self::CREDIT ? 'Credit' : 'Debit';
    }
}
