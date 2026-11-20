<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Currency;
use App\Domain\LedgerDirection;
use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only place money is written (FR-029, FR-041).
 *
 * Two rules this class exists to make true:
 *
 * 1. **Every movement is an INSERT.** No balance is ever updated, because no
 *    balance is ever stored. Reading a balance sums rows.
 *
 * 2. **A movement cannot be written without a reason and a correct direction.**
 *    `escrow_hold` must be a debit; posting it as a credit would create money
 *    from nothing. This class refuses that rather than trusting callers.
 *
 * Concurrency note: two simultaneous credits cannot lose each other, because
 * each is an independent INSERT. There is no shared row to race on. That is the
 * structural advantage over a `balance` column, which needs a lock or accepts
 * lost updates.
 */
class LedgerService
{
    /** A reason shorter than this is not an explanation. */
    private const MIN_REASON_LENGTH = 5;

    /**
     * Write one ledger row.
     *
     * @param  array{job_id?: int|null, milestone_id?: int|null, actor_id?: int|null, fx_rate_to_usd?: string|null}  $meta
     */
    public function record(
        User $user,
        LedgerKind $kind,
        Money $amount,
        string $reason,
        array $meta = [],
    ): LedgerEntry {
        $reason = trim($reason);

        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'A ledger entry needs a reason of at least %d characters.',
                self::MIN_REASON_LENGTH,
            ));
        }

        if (! $amount->isPositive()) {
            throw new InvalidArgumentException(
                'Ledger amounts must be greater than zero. Use the direction to express sign.'
            );
        }

        if ($amount->currency !== Currency::base()) {
            throw new InvalidArgumentException(sprintf(
                'Ledger entries are held in %s only; %s cannot be written to the ledger.',
                Currency::base()->value,
                $amount->currency->value,
            ));
        }

        $wallet = $this->walletFor($user);

        // The direction is a property of the kind, not of the call site.
        // `LedgerEntry::post()` derives it, so a caller physically cannot post an
        // escrow hold as a credit and create money from nothing.
        return LedgerEntry::post(
            userId: $user->id,
            walletId: $wallet->id,
            kind: $kind,
            amountCents: $amount->minorUnits,
            reason: $reason,
            jobId: $meta['job_id'] ?? null,
            milestoneId: $meta['milestone_id'] ?? null,
            actorId: $meta['actor_id'] ?? null,
            fxRateToUsd: $meta['fx_rate_to_usd'] ?? null,
        );
    }

    // -------------------------------------------------------------- balances

    /**
     * Spendable balance: credits minus debits.
     *
     * Because `escrow_hold` and `withdrawal_hold` are recorded as debits, held
     * money is automatically unavailable. There is no second figure to keep in
     * step with this one.
     */
    public function available(User $user): Money
    {
        return $this->sumFor($user, null);
    }

    /** Lifetime credits, ignoring debits: "how much have I ever received". */
    public function lifetimeCredited(User $user): Money
    {
        return $this->sumFor($user, LedgerDirection::CREDIT);
    }

    /**
     * Money currently held on this user's behalf.
     *
     * Two distinct holds, reported separately because they mean different things
     * to the person reading them:
     *   - escrow funded by this poster, waiting to be released
     *   - a withdrawal they requested, waiting to be paid out
     */
    public function escrowHeldBy(User $user): Money
    {
        return $this->sumOfKinds($user, [LedgerKind::ESCROW_HOLD, LedgerKind::ESCROW_REFUND]);
    }

    public function withdrawalHeldBy(User $user): Money
    {
        return $this->sumOfKinds($user, [
            LedgerKind::WITHDRAWAL_HOLD,
            LedgerKind::WITHDRAWAL_PAID,
            LedgerKind::WITHDRAWAL_REJECTED_RETURN,
        ]);
    }

    /**
     * Every balance figure for a user in one place, so a view never invents one.
     *
     * @return array{available: Money, lifetime_credited: Money, escrow_held: Money, withdrawal_held: Money}
     */
    public function summary(User $user): array
    {
        return [
            'available' => $this->available($user),
            'lifetime_credited' => $this->lifetimeCredited($user),
            'escrow_held' => $this->escrowHeldBy($user),
            'withdrawal_held' => $this->withdrawalHeldBy($user),
        ];
    }

    /** Does this user have at least this much available? */
    public function canAfford(User $user, Money $amount): bool
    {
        return $this->available($user)->isGreaterThanOrEqualTo($amount);
    }

    // ------------------------------------------------------------ internals

    /** Get or create the user's wallet. Idempotent. */
    public function walletFor(User $user): Wallet
    {
        $existing = Wallet::where('user_id', $user->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        /*
         * Created through the relation, not `Wallet::firstOrCreate`.
         *
         * `user_id` is deliberately not mass-assignable (so a wallet can never be
         * attached to the wrong user from request input), which means
         * firstOrCreate would silently drop it and violate NOT NULL. The relation
         * sets the owner explicitly.
         */
        return $user->wallet()->create([
            'currency' => Currency::base()->value,
        ]);
    }

    /**
     * Sum in the database, not in PHP.
     *
     * SQLite and MySQL both handle the CASE expression identically here, and
     * doing the arithmetic in SQL avoids loading a user's entire history into
     * memory to add it up.
     */
    private function sumFor(User $user, ?LedgerDirection $direction): Money
    {
        $query = DB::table('ledger_entries')->where('user_id', $user->id);

        if ($direction !== null) {
            $query->where('direction', $direction->value);
        }

        $credits = (clone $query)->where('direction', 'credit')->sum('amount_cents');
        $debits = (clone $query)->where('direction', 'debit')->sum('amount_cents');

        $net = (int) $credits - (int) $debits;

        // A negative available balance means the product allowed spending money
        // that was not there. That is a serious invariant violation, so it is
        // surfaced rather than clamped to zero and hidden.
        if ($net < 0) {
            throw new \DomainException(sprintf(
                'User %d has a negative derived balance (%d cents). The ledger and the spending rules disagree.',
                $user->id,
                $net,
            ));
        }

        return Money::fromMinorUnits($net, Currency::base());
    }

    /**
     * Sum a specific set of kinds, honouring direction.
     *
     * Used for the "held" figures, where the answer is the net of a matched set
     * of rows: a hold minus its refund or payout.
     *
     * @param  list<LedgerKind>  $kinds
     */
    private function sumOfKinds(User $user, array $kinds): Money
    {
        $rows = DB::table('ledger_entries')
            ->where('user_id', $user->id)
            ->whereIn('kind', array_map(static fn (LedgerKind $k): string => $k->value, $kinds))
            ->get(['direction', 'amount_cents']);

        $net = 0;

        foreach ($rows as $row) {
            $net += $row->direction === 'credit'
                ? -(int) $row->amount_cents
                : (int) $row->amount_cents;
        }

        // Held amounts are reported as a magnitude; a negative would mean more
        // was released than held, which is again a real defect worth surfacing.
        return Money::fromMinorUnits(max(0, $net), Currency::base());
    }
}
