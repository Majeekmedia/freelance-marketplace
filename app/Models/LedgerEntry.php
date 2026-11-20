<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\LedgerDirection;
use App\Domain\LedgerKind;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the immutable ledger (FR-029).
 *
 * ENFORCED IMMUTABILITY
 * ---------------------
 * `saving` throws on update, and `deleting` throws always. This is the model-level
 * half of the guarantee; `LedgerEntryImmutabilityTest` proves it, and there is no
 * route anywhere that could reach an update or delete.
 *
 * Corrections are made by inserting a compensating row. That way "why is this
 * number what it is" is always answerable by reading history, which is the whole
 * point of a ledger.
 *
 * The `created_at` column is the only timestamp (there is no `updated_at`),
 * because a row that cannot change has no last-modified time.
 */
class LedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = true;

    /** Only created_at exists; updated_at would imply mutability. */
    public const UPDATED_AT = null;

    /**
     * Nothing is fillable.
     *
     * Rows are created exclusively by `LedgerService`, which validates the kind
     * against its required direction and refuses a non-positive amount. Allowing
     * mass assignment here would create a second, unvalidated path to writing
     * money — exactly the thing to avoid in the one table that must never be
     * wrong.
     *
     * @var list<string>
     */
    protected $fillable = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => LedgerKind::class,
            'direction' => LedgerDirection::class,
            'amount_cents' => 'integer',
            'fx_rate_to_usd' => 'string',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Updates are refused outright.
     *
     * Overridden as well as guarded by events, because `update()` fills
     * attributes *before* firing `updating`, so an empty `$fillable` produced a
     * MassAssignmentException — technically a refusal, but a message that points
     * the reader at mass assignment instead of at the ledger rule they broke.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \DomainException(
            'Ledger entries are immutable. Insert a compensating entry instead of editing one.'
        );
    }

    /** Deletes are refused outright, for the same reason as update(). */
    public function delete()
    {
        throw new \DomainException(
            'Ledger entries cannot be deleted. Insert a compensating entry instead.'
        );
    }

    protected static function booted(): void
    {
        /*
         * Belt and braces.
         *
         * The overrides above cover the Eloquent entry points. These events cover
         * paths that bypass them — `save()` on a dirty existing model, a bulk
         * query builder update, force-delete. Anything that reaches the database
         * through the model lands here.
         */
        static::updating(function (): never {
            throw new \DomainException(
                'Ledger entries are immutable. Insert a compensating entry instead of editing one.'
            );
        });

        static::deleting(function (): never {
            throw new \DomainException(
                'Ledger entries cannot be deleted. Insert a compensating entry instead.'
            );
        });
    }

    // ------------------------------------------------------------- relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    // ---------------------------------------------------------------- money

    /** The amount as a value object, with this row's direction applied. */
    public function signedAmount(): Money
    {
        $minor = $this->direction->apply((int) $this->amount_cents);

        // Money is non-negative by construction, so a debit is expressed as a
        // subtraction rather than a negative value object.
        return Money::fromMinorUnits(abs($minor), Currency::fromCode((string) $this->currency));
    }

    public function amount(): Money
    {
        return Money::fromMinorUnits((int) $this->amount_cents, Currency::fromCode((string) $this->currency));
    }

    public function isCredit(): bool
    {
        return $this->direction === LedgerDirection::CREDIT;
    }

    /**
     * Write a new row.
     *
     * Deliberately not mass assignment. `$fillable` is empty so that
     * `LedgerEntry::create($request->all())` — or any future careless code — has
     * no path into this table. The only way in is this method, which takes
     * explicit arguments and is called exclusively by `LedgerService` after it
     * has validated the kind's direction, the amount and the reason.
     *
     * This is the "one door" pattern for the table that must never be wrong.
     */
    public static function post(
        int $userId,
        int $walletId,
        LedgerKind $kind,
        int $amountCents,
        string $reason,
        ?int $jobId = null,
        ?int $milestoneId = null,
        ?int $actorId = null,
        ?string $fxRateToUsd = null,
    ): self {
        $entry = new self();

        $entry->user_id = $userId;
        $entry->wallet_id = $walletId;
        $entry->kind = $kind;
        $entry->direction = $kind->direction();
        $entry->amount_cents = $amountCents;
        $entry->currency = Currency::base()->value;
        $entry->reason = $reason;
        $entry->job_id = $jobId;
        $entry->milestone_id = $milestoneId;
        $entry->actor_id = $actorId;
        $entry->fx_rate_to_usd = $fxRateToUsd;

        $entry->save();

        return $entry;
    }

    // --------------------------------------------------------------- scopes

    /** @param  Builder<LedgerEntry>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param  Builder<LedgerEntry>  $query */
    public function scopeOfKind(Builder $query, LedgerKind $kind): void
    {
        $query->where('kind', $kind->value);
    }
}
