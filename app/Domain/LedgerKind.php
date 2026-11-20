<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * What a ledger row represents (FR-029).
 *
 * Every balance change in the product is one of these. The kind is not
 * decoration: it is how a dispute is resolved six months later, and how the UI
 * explains a balance line by line (FR-041d). "Why is my balance $40?" is
 * answered by listing rows, not by defending an unexplained number.
 *
 * Adding a case here is a deliberate act — it means a new way for money to move.
 */
enum LedgerKind: string
{
    /** A poster's deposit request was approved by an admin. */
    case DEPOSIT_CREDIT = 'deposit_credit';

    /** A poster funded a job: money leaves available balance into escrow. */
    case ESCROW_HOLD = 'escrow_hold';

    /** An admin released a milestone to the hired freelancer. */
    case ESCROW_RELEASE_CREDIT = 'escrow_release_credit';

    /** Unreleased escrow returned to the poster. */
    case ESCROW_REFUND = 'escrow_refund';

    /** A freelancer requested a withdrawal: funds held, not yet paid. */
    case WITHDRAWAL_HOLD = 'withdrawal_hold';

    /** An admin marked a withdrawal paid. */
    case WITHDRAWAL_PAID = 'withdrawal_paid';

    /** An admin rejected a withdrawal: the held amount returns. */
    case WITHDRAWAL_REJECTED_RETURN = 'withdrawal_rejected_return';

    /** A manual admin credit. */
    case ADMIN_CREDIT = 'admin_credit';

    /** A manual admin debit. */
    case ADMIN_DEBIT = 'admin_debit';

    /** Direct payment to a freelancer outside the escrow path. */
    case JOB_PAYMENT_CREDIT = 'job_payment_credit';

    /**
     * The direction this kind must carry.
     *
     * Declared per kind so a caller cannot write `escrow_hold` as a credit by
     * mistake — a mistake that would silently create money. `LedgerService`
     * checks this and throws.
     */
    public function direction(): LedgerDirection
    {
        return match ($this) {
            self::DEPOSIT_CREDIT,
            self::ESCROW_RELEASE_CREDIT,
            self::ESCROW_REFUND,
            self::WITHDRAWAL_REJECTED_RETURN,
            self::ADMIN_CREDIT,
            self::JOB_PAYMENT_CREDIT => LedgerDirection::CREDIT,

            self::ESCROW_HOLD,
            self::WITHDRAWAL_HOLD,
            self::WITHDRAWAL_PAID,
            self::ADMIN_DEBIT => LedgerDirection::DEBIT,
        };
    }

    /** Human label for a ledger listing. */
    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_CREDIT => 'Deposit',
            self::ESCROW_HOLD => 'Funded into escrow',
            self::ESCROW_RELEASE_CREDIT => 'Milestone released',
            self::ESCROW_REFUND => 'Escrow refunded',
            self::WITHDRAWAL_HOLD => 'Withdrawal requested',
            self::WITHDRAWAL_PAID => 'Withdrawal paid',
            self::WITHDRAWAL_REJECTED_RETURN => 'Withdrawal rejected — returned',
            self::ADMIN_CREDIT => 'Adjustment (credit)',
            self::ADMIN_DEBIT => 'Adjustment (debit)',
            self::JOB_PAYMENT_CREDIT => 'Job payment',
        };
    }
}
