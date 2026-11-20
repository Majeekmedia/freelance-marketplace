<?php

declare(strict_types=1);

use App\Domain\Currency;
use App\Domain\Money;

/**
 * Conversion must be exact, not merely close.
 *
 * These cases are chosen because float arithmetic gets them wrong. If any of
 * them regress, the cause is almost certainly a reintroduced `(float)` cast.
 */

it('converts with a rate that float gets wrong', function (): void {
    // 10_000_000.00 USD at 0.1 EUR/USD.
    // (int) round(1000000000 * 0.1) happens to work, but the same expression
    // with 0.07 does not: PHP float gives 70000000.00000001.
    $amount = Money::fromMinorUnits(1_000_000_000, Currency::USD);

    expect($amount->convert('0.1', Currency::EUR)->minorUnits)->toBe(100_000_000)
        ->and($amount->convert('0.07', Currency::EUR)->minorUnits)->toBe(70_000_000);
});

it('rounds half up at the boundary', function (): void {
    // 1 cent at 1.5 should become 2 minor units, not 1.
    expect(Money::fromMinorUnits(1, Currency::USD)->convert('1.5', Currency::EUR)->minorUnits)
        ->toBe(2);

    // 1 cent at 1.4 should become 1.
    expect(Money::fromMinorUnits(1, Currency::USD)->convert('1.4', Currency::EUR)->minorUnits)
        ->toBe(1);
});

it('converts an eight-decimal rate exactly', function (): void {
    // 123.45 USD at 0.87654321. Integer maths: 12345 * 87654321 = 1,082,050,542,  ... /1e8
    $amount = Money::fromMinorUnits(12_345, Currency::USD);

    expect($amount->convert('0.87654321', Currency::EUR)->minorUnits)->toBe(10_821);
});

it('returns the same value when the currency already matches', function (): void {
    $amount = Money::fromMinorUnits(500, Currency::USD);

    expect($amount->convert('1.2345', Currency::USD))->toBe($amount);
});

it('rejects a malformed rate rather than guessing', function (string $rate): void {
    expect(fn () => Money::fromMinorUnits(100, Currency::USD)->convert($rate, Currency::EUR))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => '',
    'letters' => 'abc',
    'negative' => '-1.2',
    'two dots' => '1.2.3',
]);

it('rejects a zero rate', function (): void {
    expect(fn () => Money::fromMinorUnits(100, Currency::USD)->convert('0', Currency::EUR))
        ->toThrow(InvalidArgumentException::class);
});

it('round-trips an amount using a verified reciprocal rate', function (): void {
    // The reciprocal of 0.87654321 to 8 places is 1.14084507 — verified by
    // computing it, not by guessing. Two conversions at 8-place precision
    // cannot be expected to return the exact original, so the honest
    // assertion is a one-cent tolerance on an amount this size.
    $original = Money::fromMinorUnits(999_999_999, Currency::USD);

    $converted = $original->convert('0.87654321', Currency::EUR);
    $back = $converted->convert('1.14084507', Currency::USD);

    expect(abs($back->minorUnits - $original->minorUnits))->toBeLessThanOrEqual(1);
});

it('refuses a conversion that would overflow 64-bit integers', function (): void {
    // Near PHP_INT_MAX, multiplying by an 8-decimal rate would push PHP into a
    // float and silently lose precision. Failing loudly is the only safe answer.
    $huge = Money::fromMinorUnits(PHP_INT_MAX, Currency::USD);

    expect(fn () => $huge->convert('0.87654321', Currency::EUR))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a rate with more precision than the schema stores', function (): void {
    // decimal(18,8) cannot hold 16 places; accepting it in memory would make
    // the object disagree with its own persisted form.
    expect(fn () => Money::fromMinorUnits(100, Currency::USD)->convert('1.1408450702618529', Currency::EUR))
        ->toThrow(InvalidArgumentException::class);
});
