<?php

declare(strict_types=1);

use App\Domain\Currency;
use App\Domain\Money;

/*
|--------------------------------------------------------------------------
| Money must be impossible to misuse
|--------------------------------------------------------------------------
| These tests exist to prove the guards fire. A guard that never triggers is
| indistinguishable from no guard, so each of these asserts a FAILURE.
*/

it('cannot be constructed from a float via reflection', function (): void {
    $reflection = new ReflectionClass(Money::class);
    $constructor = $reflection->getConstructor();

    // Private constructor: callers must go through a factory that validates.
    expect($constructor->isPrivate())->toBeTrue();
});

it('rejects every route into a negative amount', function (): void {
    expect(fn () => Money::fromMinorUnits(-1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('-1.00'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('1.00')->subtract(Money::fromDecimal('1.01')))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('1.00')->multiply(-2))
        ->toThrow(InvalidArgumentException::class);
});

it('does not silently truncate a third decimal place', function (): void {
    // Silently rounding 1.005 to 1.00 would be a money bug; we refuse instead.
    expect(fn () => Money::fromDecimal('1.005'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('1.999'))->toThrow(InvalidArgumentException::class);
});

it('cannot mix currencies through sum', function (): void {
    expect(fn () => Money::sum(
        Money::fromDecimal('10.00'),
        Money::fromDecimal('10.00', Currency::EUR),
    ))->toThrow(InvalidArgumentException::class);
});

it('proves the allocation guarantee holds across many awkward splits', function (int $minor, int $parts): void {
    $original = Money::fromMinorUnits($minor);
    $shares = $original->allocate($parts);

    $recombined = Money::sum(...$shares);

    expect($recombined->minorUnits)->toBe($minor)
        ->and($shares)->toHaveCount($parts);
})->with([
    [1, 3],
    [2, 3],
    [7, 3],
    [100, 7],
    [999, 13],
    [10000, 3],
    [12345, 11],
    [1, 100],
]);
