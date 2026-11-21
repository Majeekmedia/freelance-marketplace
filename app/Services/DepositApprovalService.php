<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Currency;
use App\Domain\DepositStatus;
use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Approving and rejecting deposits (FR-040).
 *
 * This class is where money first enters the system. Everything about it is
 * therefore defensive:
 *
 *  - The decision and the ledger credit happen in **one transaction**. A ledger
 *    row without a decided request, or a decided request without a ledger row,
 *    are both unacceptable states.
 *  - Approval is **idempotent-refusing**: a request that is already decided
 *    throws rather than crediting twice. A double-click, a retried request or a
 *    stale browser tab must not create money (FR-040b).
 *  - The credited amount is the **admin's** figure, not the poster's. Banks
 *    deduct fees, so what arrived is often less than what was sent.
 */
class DepositApprovalService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Approve a deposit and credit the poster's wallet.
     *
     * @param  Money|null  $credited  What actually arrived. Defaults to the amount
     *                                requested when the admin does not override it.
     * @param  string  $reason  Required — an unexplained credit is not auditable.
     *
     * @throws \DomainException when the request has already been decided
     */
    public function approve(
        User $admin,
        DepositRequest $deposit,
        ?Money $credited = null,
        string $reason = 'Deposit confirmed against submitted proof',
        ?string $ipAddress = null,
    ): DepositRequest {
        $credited ??= $deposit->requestedAmount();
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new \InvalidArgumentException(
                'Approving a deposit needs a reason of at least 5 characters.'
            );
        }

        if (! $credited->isPositive()) {
            throw new \InvalidArgumentException('A deposit credit must be greater than zero.');
        }

        return DB::transaction(function () use ($admin, $deposit, $credited, $reason, $ipAddress): DepositRequest {
            /*
             * Lock and re-read inside the transaction.
             *
             * Checking the status on the instance passed in would be a
             * time-of-check/time-of-use bug: two admins opening the same queue,
             * or one admin double-clicking, could both see `pending` and both
             * credit. The row lock makes the second one fail.
             */
            $locked = DepositRequest::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status->isDecided()) {
                throw new \DomainException(sprintf(
                    'Deposit request %d has already been %s. It cannot be approved again.',
                    $locked->id,
                    $locked->status->value,
                ));
            }

            // One ledger row, written only after the guard above passed.
            $entry = $this->ledger->record(
                user: $locked->user,
                kind: LedgerKind::DEPOSIT_CREDIT,
                amount: $credited,
                reason: $reason,
                meta: ['actor_id' => $admin->id],
            );

            $locked->status = DepositStatus::APPROVED;
            $locked->credited_amount_cents = $credited->minorUnits;
            $locked->reviewed_by = $admin->id;
            $locked->reviewed_at = now();
            $locked->save();

            // Audit in the same transaction: a credit without an audit row is
            // exactly the gap FR-028 exists to close.
            $this->audit->depositDecision(
                actor: $admin,
                depositId: $locked->id,
                approved: true,
                credited: $credited,
                reason: $reason,
                ipAddress: $ipAddress,
                context: [
                    'requested_cents' => $locked->amount_requested_cents,
                    'credited_cents' => $credited->minorUnits,
                    'ledger_entry_id' => $entry->id,
                    'transfer_reference' => $locked->transfer_reference,
                ],
            );

            return $locked;
        });
    }

    /**
     * Reject a deposit. Writes no ledger row.
     *
     * The reason is mandatory here too: "why was my deposit rejected" is the
     * question this field exists to answer.
     */
    public function reject(
        User $admin,
        DepositRequest $deposit,
        string $reason,
        ?string $ipAddress = null,
    ): DepositRequest {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new \InvalidArgumentException(
                'Rejecting a deposit needs a reason of at least 5 characters.'
            );
        }

        return DB::transaction(function () use ($admin, $deposit, $reason, $ipAddress): DepositRequest {
            $locked = DepositRequest::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status->isDecided()) {
                throw new \DomainException(sprintf(
                    'Deposit request %d has already been %s. It cannot be rejected now.',
                    $locked->id,
                    $locked->status->value,
                ));
            }

            $locked->status = DepositStatus::REJECTED;
            $locked->rejection_reason = $reason;
            $locked->reviewed_by = $admin->id;
            $locked->reviewed_at = now();
            // Deliberately no credited amount: nothing was credited.
            $locked->credited_amount_cents = null;
            $locked->save();

            $this->audit->depositDecision(
                actor: $admin,
                depositId: $locked->id,
                approved: false,
                credited: null,
                reason: $reason,
                ipAddress: $ipAddress,
                context: [
                    'requested_cents' => $locked->amount_requested_cents,
                    'transfer_reference' => $locked->transfer_reference,
                ],
            );

            return $locked;
        });
    }
}
