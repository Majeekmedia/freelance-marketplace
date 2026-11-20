<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A wallet: an identity for money, not an amount (FR-041).
 *
 * This class intentionally has no `balance` property and no `increment` usage.
 * Reading a balance means asking `LedgerService`.
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * `user_id` is not fillable: a wallet is always created through the owning
     * relation so it cannot be attached to the wrong user from request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'currency',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->orderBy('id');
    }

    public function currencyEnum(): Currency
    {
        return Currency::fromCode((string) $this->currency);
    }
}
