<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\DepositStatus;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A poster's claim that they transferred money (FR-039).
 *
 * The single most important property of this model: **creating it credits
 * nothing.** The derived balance is unchanged until an admin approves. That is
 * asserted in the tests, because it is the difference between a marketplace that
 * can be trusted and one where typing a number is the same as having money.
 */
class DepositRequest extends Model
{
    use HasFactory;

    /**
     * `status`, `credited_amount_cents`, `reviewed_by` and `reviewed_at` are not
     * fillable. Every one of them is an admin decision, and a poster submitting
     * a form must not be able to influence any of them.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_method_id',
        'amount_requested_cents',
        'transfer_reference',
        'proof_path',
        'method_snapshot',
        'currency',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'amount_requested_cents' => 'integer',
            'credited_amount_cents' => 'integer',
            'method_snapshot' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------- relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ---------------------------------------------------------------- money

    public function requestedAmount(): Money
    {
        return Money::fromMinorUnits(
            (int) $this->amount_requested_cents,
            Currency::fromCode((string) $this->currency),
        );
    }

    public function creditedAmount(): ?Money
    {
        if ($this->credited_amount_cents === null) {
            return null;
        }

        return Money::fromMinorUnits(
            (int) $this->credited_amount_cents,
            Currency::fromCode((string) $this->currency),
        );
    }

    /**
     * Did the admin credit a different amount to the one requested?
     *
     * Worth surfacing in the UI: a poster who sent 100 and sees 97 credited
     * should be able to see that a fee was deducted rather than wonder.
     */
    public function wasAdjusted(): bool
    {
        return $this->credited_amount_cents !== null
            && (int) $this->credited_amount_cents !== (int) $this->amount_requested_cents;
    }

    // ---------------------------------------------------------------- state

    /**
     * Is this request still waiting for a decision?
     */
    public function isPending(): bool
    {
        return $this->status === DepositStatus::PENDING;
    }

    /** @param  Builder<DepositRequest>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', DepositStatus::PENDING->value);
    }

    /** @param  Builder<DepositRequest>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param  Builder<DepositRequest>  $query */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', DepositStatus::PENDING->value)->oldest('created_at');
    }
}
