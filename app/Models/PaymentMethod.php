<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\PaymentMethodType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Instructions a poster follows to fund their wallet (FR-038).
 *
 * The important behaviours:
 *
 *  - `enabled()` is the only scope the poster-facing page may use. A disabled
 *    method must never reach a poster.
 *  - `snapshot()` captures the details at the moment a poster submits a request,
 *    so editing a method later cannot rewrite what that poster was told to pay.
 *  - `isComplete()` is the type-aware validity check; a crypto method with no
 *    network is not a usable method.
 */
class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * `type` is not fillable: it decides which fields are required, so it is set
     * explicitly and validated before save rather than arriving from input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'bank_name',
        'account_name',
        'account_number',
        'routing_detail',
        'crypto_asset',
        'crypto_network',
        'crypto_address',
        'is_enabled',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // --------------------------------------------------------------- scopes

    /**
     * Methods a poster may be shown.
     *
     * Every poster-facing query must go through this. A disabled method reaching
     * the funding page would tell someone to pay into an account the admin has
     * deliberately switched off.
     *
     * @param  Builder<PaymentMethod>  $query
     */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true)->orderBy('sort_order')->orderBy('id');
    }

    // ------------------------------------------------------------ validity

    /**
     * Is this method usable as it stands?
     *
     * Type-aware, because "complete" means different things for a bank account
     * and a crypto address (FR-038d).
     */
    public function isComplete(): bool
    {
        foreach ($this->type->requiredFields() as $field) {
            $value = $this->getAttribute($field);

            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return trim((string) $this->label) !== '';
    }

    /** @return list<string> */
    public function missingFields(): array
    {
        $missing = [];

        foreach ($this->type->requiredFields() as $field) {
            $value = $this->getAttribute($field);

            if (! is_string($value) || trim($value) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Clear the fields that do not belong to this type.
     *
     * Prevents a method that changed type from showing a poster a stale address
     * alongside a bank account — two contradictory sets of instructions.
     */
    public function clearIrrelevantFields(): void
    {
        // Guard rather than throw: a row read back with a type that is no longer
        // in the enum would otherwise fail here with an unhelpful null error.
        if ($this->type === null) {
            return;
        }

        foreach ($this->type->irrelevantFields() as $field) {
            $this->setAttribute($field, null);
        }
    }

    // ------------------------------------------------------------ snapshot

    /**
     * The details as a poster should see them, frozen at this moment.
     *
     * Stored on the deposit request (FR-039d) so later edits to the method do not
     * change the record of what that poster was instructed to do.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $base = [
            'method_id' => $this->id,
            'label' => $this->label,
            'type' => $this->type->value,
        ];

        return $base + match ($this->type) {
            PaymentMethodType::BANK_TRANSFER => [
                'bank_name' => $this->bank_name,
                'account_name' => $this->account_name,
                'account_number' => $this->account_number,
                'routing_detail' => $this->routing_detail,
            ],
            PaymentMethodType::CRYPTO => [
                'crypto_asset' => $this->crypto_asset,
                'crypto_network' => $this->crypto_network,
                'crypto_address' => $this->crypto_address,
            ],
        };
    }

    // ------------------------------------------------------------ display

    /**
     * One-line summary for the admin list and the funding page.
     *
     * Never a credential: an account number and a public wallet address are the
     * things a payer needs. Nothing secret belongs on this table.
     */
    public function summary(): string
    {
        return match ($this->type) {
            PaymentMethodType::BANK_TRANSFER => trim(sprintf(
                '%s · %s',
                (string) $this->bank_name,
                $this->account_number !== null ? '···'.substr((string) $this->account_number, -4) : '',
            )),
            PaymentMethodType::CRYPTO => trim(sprintf(
                '%s on %s',
                (string) $this->crypto_asset,
                (string) $this->crypto_network,
            )),
        };
    }
}
