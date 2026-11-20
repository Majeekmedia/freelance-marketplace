<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * Supported currencies.
 *
 * NGN is deliberately absent (requirements FR-036). It is not a wallet
 * currency, not a pricing currency and not a display currency. Any attempt to
 * use it fails loudly rather than silently rendering an amount in a currency
 * the product does not support.
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case CAD = 'CAD';

    /** The only currency a wallet or ledger row may hold. */
    public static function base(): self
    {
        return self::USD;
    }

    /**
     * Codes that may be shown to a visitor in the display switcher.
     * USD is the base and always first.
     *
     * @return list<string>
     */
    public static function displayWhitelist(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    /**
     * Parse a currency code, throwing on anything unsupported.
     *
     * NGN gets a specific message because it is a deliberate exclusion, not an
     * oversight — a generic "unknown currency" would invite someone to add it.
     */
    public static function fromCode(string $code): self
    {
        $normalised = strtoupper(trim($code));

        $currency = self::tryFrom($normalised);

        if ($currency === null) {
            if ($normalised === 'NGN') {
                throw new InvalidArgumentException(
                    'NGN is not a supported currency. Wallet currency is USD; '
                    .'display currencies are '.implode(', ', self::displayWhitelist()).'.'
                );
            }

            throw new InvalidArgumentException(sprintf(
                'Unsupported currency "%s". Supported: %s.',
                $code,
                implode(', ', self::displayWhitelist())
            ));
        }

        return $currency;
    }

    /** Minor units per major unit. All supported currencies use 2. */
    public function minorUnitScale(): int
    {
        return 2;
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::CAD => 'CA$',
        };
    }
}
