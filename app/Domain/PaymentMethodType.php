<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The kinds of payment method a poster can use to fund a wallet (FR-038).
 *
 * Only two, and that is deliberate. Every additional type is another set of
 * instructions an admin can get wrong and another way for a poster to send money
 * to the wrong place. Bank and crypto cover the realistic cases for this product;
 * adding more should be a decision with a reason, not a dropdown.
 */
enum PaymentMethodType: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CRYPTO = 'crypto';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank transfer',
            self::CRYPTO => 'Crypto transfer',
        };
    }

    /**
     * Fields this type requires before it can be saved (FR-038d).
     *
     * Declared here rather than in the FormRequest so the model and any future
     * importer enforce the same set. A crypto method with no network or address
     * is not a method — it is a poster staring at an unusable instruction.
     *
     * @return list<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::BANK_TRANSFER => ['bank_name', 'account_name', 'account_number'],
            self::CRYPTO => ['crypto_asset', 'crypto_network', 'crypto_address'],
        };
    }

    /**
     * Fields that are irrelevant to this type.
     *
     * Cleared on save so a bank method cannot retain a stale crypto address from
     * a previous type change, which would show a poster two contradictory sets
     * of instructions.
     *
     * @return list<string>
     */
    public function irrelevantFields(): array
    {
        return match ($this) {
            self::BANK_TRANSFER => [
                'crypto_asset', 'crypto_network', 'crypto_address',
            ],
            self::CRYPTO => [
                'bank_name', 'account_name', 'account_number', 'routing_detail',
            ],
        };
    }
}
