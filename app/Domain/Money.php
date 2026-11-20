<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * An amount of money, stored as an integer number of minor units (cents).
 *
 * Why this class exists
 * ---------------------
 * Floating point cannot represent 0.10 exactly. Summing 0.10 ten times in
 * `float` yields 0.9999999999999999, and money that drifts by fractions of a
 * cent cannot be reconciled. Every balance in this application is derived from
 * ledger rows expressed in this type, so if this class is wrong, every figure
 * in the product is wrong.
 *
 * Rules enforced here:
 *  - the amount is always an integer (never float, never a numeric string)
 *  - it is non-negative; direction is carried by the ledger row, not the sign
 *  - arithmetic between different currencies throws
 *  - division allocates remainder explicitly rather than silently losing it
 *
 * This class has no framework dependencies and no database awareness.
 */
final readonly class Money implements \JsonSerializable, \Stringable
{
    private function __construct(
        public int $minorUnits,
        public Currency $currency,
    ) {
    }

    // ---------------------------------------------------------------- factory

    /** Zero in the given currency. */
    public static function zero(Currency $currency = Currency::USD): self
    {
        return new self(0, $currency);
    }

    /** Direct construction from minor units (cents). */
    public static function fromMinorUnits(int $minorUnits, Currency $currency = Currency::USD): self
    {
        if ($minorUnits < 0) {
            throw new InvalidArgumentException(
                'Money cannot be negative. Use a ledger direction (credit/debit) to express sign.'
            );
        }

        return new self($minorUnits, $currency);
    }

    /**
     * Construct from a decimal string such as "12.34" or "12".
     *
     * Deliberately accepts ONLY string|int. A float is rejected: PHP would
     * already have lost precision by the time it arrived here, so accepting one
     * would defeat the purpose of the class.
     */
    public static function fromDecimal(string|int $amount, Currency $currency = Currency::USD): self
    {
        if (is_int($amount)) {
            $amount = (string) $amount;
        }

        $value = trim($amount);

        if ($value === '' || ! preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid money amount "%s". Expected a non-negative decimal with at most 2 places.',
                $amount
            ));
        }

        [$whole, $fraction] = array_pad(explode('.', $value), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        $minor = ((int) $whole * 100) + (int) $fraction;

        return new self($minor, $currency);
    }

    /**
     * Parse a user-submitted amount, tolerating thousands separators and a
     * leading currency symbol, because humans type "$1,200.00".
     *
     * This is the only method that should be used on raw request input.
     */
    public static function parse(string $input, Currency $currency = Currency::USD): self
    {
        $clean = preg_replace('/[^0-9.]/', '', $input) ?? '';

        if (substr_count($clean, '.') > 1) {
            throw new InvalidArgumentException(sprintf('Ambiguous money input "%s".', $input));
        }

        if ($clean === '' || $clean === '.') {
            throw new InvalidArgumentException(sprintf('Empty money input "%s".', $input));
        }

        return self::fromDecimal($clean, $currency);
    }

    // ------------------------------------------------------------- accessors

    /** Whole major units, truncated (display only — never for arithmetic). */
    public function wholeUnits(): int
    {
        return intdiv($this->minorUnits, 100);
    }

    public function minorRemainder(): int
    {
        return $this->minorUnits % 100;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits > $other->minorUnits;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits >= $other->minorUnits;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits < $other->minorUnits;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && $this->minorUnits === $other->minorUnits;
    }

    // ------------------------------------------------------------ arithmetic

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    /** Subtract, refusing to go negative — an invariant violation, not a state. */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->minorUnits > $this->minorUnits) {
            throw new InvalidArgumentException(sprintf(
                'Cannot subtract %s from %s: result would be negative.',
                $other->toDecimal(),
                $this->toDecimal()
            ));
        }

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /** Multiply by a whole number of times (quantities, milestone counts). */
    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Cannot multiply money by a negative factor.');
        }

        return new self($this->minorUnits * $factor, $this->currency);
    }

    /**
     * Split into $parts shares, distributing any remainder one cent at a time.
     *
     * The sum of the parts always equals the original exactly. This is what
     * prevents a milestone split from losing or inventing a cent.
     *
     * @return list<self>
     */
    public function allocate(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot allocate money into fewer than one part.');
        }

        $base = intdiv($this->minorUnits, $parts);
        $remainder = $this->minorUnits % $parts;

        $shares = [];
        for ($i = 0; $i < $parts; $i++) {
            $shares[] = new self($base + ($i < $remainder ? 1 : 0), $this->currency);
        }

        return $shares;
    }

    /**
     * Split by explicit ratios (e.g. milestone weights), guaranteeing the sum
     * equals the original. Remainder goes to the earliest shares.
     *
     * @param  list<int>  $ratios
     * @return list<self>
     */
    public function allocateByRatio(array $ratios): array
    {
        if ($ratios === []) {
            throw new InvalidArgumentException('Ratios cannot be empty.');
        }

        $total = array_sum($ratios);

        if ($total <= 0) {
            throw new InvalidArgumentException('Ratios must sum to a positive number.');
        }

        $shares = [];
        $allocated = 0;

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Ratios cannot be negative.');
            }

            $share = intdiv($this->minorUnits * $ratio, $total);
            $shares[] = $share;
            $allocated += $share;
        }

        // Distribute any remaining minor units one at a time.
        $remainder = $this->minorUnits - $allocated;
        for ($i = 0; $remainder > 0; $i = ($i + 1) % count($shares)) {
            $shares[$i]++;
            $remainder--;
        }

        return array_map(
            fn (int $minor): self => new self($minor, $this->currency),
            $shares
        );
    }

    /** Sum a list. All must share a currency. */
    public static function sum(self ...$amounts): self
    {
        if ($amounts === []) {
            return self::zero();
        }

        $total = array_shift($amounts);

        foreach ($amounts as $amount) {
            $total = $total->add($amount);
        }

        return $total;
    }

    // --------------------------------------------------------------- display

    /** Canonical decimal string, e.g. "1234.05". Never for arithmetic. */
    public function toDecimal(): string
    {
        return sprintf(
            '%d.%02d',
            $this->wholeUnits(),
            $this->minorRemainder()
        );
    }

    /** Human string such as "$1,234.05". */
    public function format(): string
    {
        return $this->currency->symbol().number_format(
            $this->minorUnits / 100,
            2,
            '.',
            ','
        );
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }

    /** Serialised as minor units plus currency, so no precision is lost. */
    public function jsonSerialize(): array
    {
        return [
            'minor_units' => $this->minorUnits,
            'currency' => $this->currency->value,
            'decimal' => $this->toDecimal(),
        ];
    }

    // ---------------------------------------------------------------- guards

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Cannot combine %s with %s without an explicit conversion rate.',
                $this->currency->value,
                $other->currency->value
            ));
        }
    }
}
