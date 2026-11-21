<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Money;
use App\Models\AuditLog;
use App\Models\User;

/**
 * The single door for writing audit rows (FR-028).
 *
 * Why a service rather than calling `AuditLog::record()` directly:
 *
 *  - **The actor comes from the session, not from the call site.** A controller
 *    passes `$request->user()` once; nothing can pass an arbitrary user id and
 *    attribute an action to someone else.
 *  - **Action names are constants.** `deposit.approved` typed as a string in six
 *    places will eventually be typed `deposit.approved ` or `deposits.approved`,
 *    and the audit trail then lies by omission when someone searches for it.
 *  - **One place to add fields.** When IP or user-agent capture is needed, it is
 *    added here once.
 */
class AuditLogger
{
    /** Action names. Constants so a typo cannot silently split the trail. */
    public const DEPOSIT_APPROVED = 'deposit.approved';

    public const DEPOSIT_REJECTED = 'deposit.rejected';

    public const PAYMENT_METHOD_CREATED = 'payment_method.created';

    public const PAYMENT_METHOD_UPDATED = 'payment_method.updated';

    public const PAYMENT_METHOD_TOGGLED = 'payment_method.toggled';

    public const PAYMENT_METHOD_DELETED = 'payment_method.deleted';

    public const WALLET_CREDITED = 'wallet.credited';

    public const WALLET_DEBITED = 'wallet.debited';

    public const ACCOUNT_APPROVED = 'account.approved';

    public const ACCOUNT_SUSPENDED = 'account.suspended';

    /**
     * Record an action.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function record(
        ?User $actor,
        string $action,
        string $targetType,
        int $targetId,
        ?string $reason = null,
        ?Money $amount = null,
        ?array $context = null,
        ?string $ipAddress = null,
    ): AuditLog {
        return AuditLog::record(
            actorId: $actor?->id,
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            reason: $reason,
            amountCents: $amount?->minorUnits,
            currency: $amount?->currency->value,
            context: $context,
            ipAddress: $ipAddress,
        );
    }

    // ------------------------------------------------------ named shortcuts

    /**
     * A deposit decision.
     *
     * Both outcomes are audited, including the rejection — a rejected deposit is
     * a decision someone may need to defend later.
     */
    public function depositDecision(
        User $actor,
        int $depositId,
        bool $approved,
        ?Money $credited,
        string $reason,
        ?string $ipAddress = null,
        ?array $context = null,
    ): AuditLog {
        return $this->record(
            actor: $actor,
            action: $approved ? self::DEPOSIT_APPROVED : self::DEPOSIT_REJECTED,
            targetType: 'deposit_request',
            targetId: $depositId,
            reason: $reason,
            amount: $credited,
            context: $context,
            ipAddress: $ipAddress,
        );
    }
}
