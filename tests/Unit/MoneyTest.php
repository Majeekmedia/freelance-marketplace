<?php

declare(strict_types=1);

use App\Domain\Currency;
use App\Domain\Money;

/*
|--------------------------------------------------------------------------
| The float trap
|--------------------------------------------------------------------------
| These tests exist because PHP's float arithmetic cannot represent 0.10.
| If any of them fail, every balance in the product is untrustworthy.
*/

it('adds ten dimes to exactly one unit', function (): void {
    $dime = Money::fromDecimal('0.10');

    $total = Money::zero();
    for ($i = 0; $i < 10; $i++) {
        $total = $total->add($dime);
    }

    expect($total->minorUnits)->toBe(100)
        ->and($total->toDecimal())->toBe('1.00');

    // Prove the float equivalent is broken, so the point is on the record.
    $naiveFloat = 0.0;
    for ($i = 0; $i < 10; $i++) {
        $naiveFloat += 0.1;
    }
    expect($naiveFloat === 1.0)->toBeFalse();
});

it('rejects a float amount outright', function (): void {
    // A float has already lost precision by the time it reaches us, so
    // accepting one would defeat the entire class.
    expect(fn () => Money::fromDecimal(0.1 + 0.2))
        ->toThrow(TypeError::class);
});

it('rejects more than two decimal places', function (): void {
    expect(fn () => Money::fromDecimal('1.005'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a negative amount', function (): void {
    expect(fn () => Money::fromMinorUnits(-1))
        ->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Construction and display
|--------------------------------------------------------------------------
*/

it('round-trips every amount exactly', function (string $decimal, int $minor): void {
    $money = Money::fromDecimal($decimal);

    expect($money->minorUnits)->toBe($minor)
        ->and($money->toDecimal())->toBe(number_format($minor / 100, 2, '.', ''));
})->with([
    ['0', 0],
    ['0.00', 0],
    ['0.01', 1],
    ['0.10', 10],
    ['1.00', 100],
    ['12.34', 1234],
    ['999999.99', 99999999],
    ['1200', 120000],
]);

it('parses human input with symbols and separators', function (string $input, int $minor): void {
    expect(Money::parse($input)->minorUnits)->toBe($minor);
})->with([
    ['$1,200.00', 120000],
    ['1,234.05', 123405],
    ['  950 ', 95000],
    ['€42.50', 4250],
]);

it('rejects ambiguous human input', function (string $input): void {
    expect(fn () => Money::parse($input))->toThrow(InvalidArgumentException::class);
})->with([
    ['1.2.3'],
    [''],
    ['.'],
    ['abc'],
]);

it('formats with the currency symbol and grouping', function (): void {
    expect(Money::fromDecimal('1234.05')->format())->toBe('$1,234.05')
        ->and(Money::fromDecimal('1234.05', Currency::EUR)->format())->toBe('€1,234.05')
        ->and(Money::zero()->format())->toBe('$0.00');
});

/*
|--------------------------------------------------------------------------
| Arithmetic
|--------------------------------------------------------------------------
*/

it('subtracts without going negative', function (): void {
    $ten = Money::fromDecimal('10.00');

    expect($ten->subtract(Money::fromDecimal('4.00'))->toDecimal())->toBe('6.00')
        ->and(fn () => $ten->subtract(Money::fromDecimal('10.01')))
        ->toThrow(InvalidArgumentException::class);
});

it('multiplies by a whole quantity', function (): void {
    expect(Money::fromDecimal('19.99')->multiply(3)->toDecimal())->toBe('59.97');
});

it('refuses to combine different currencies', function (): void {
    $usd = Money::fromDecimal('10.00');
    $eur = Money::fromDecimal('10.00', Currency::EUR);

    expect(fn () => $usd->add($eur))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $usd->subtract($eur))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $usd->isGreaterThan($eur))->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Allocation — the milestone split
|--------------------------------------------------------------------------
| A milestone split must never lose or invent a cent. These are the tests that
| make FR-032 (milestone totals equal the budget) enforceable.
*/

it('allocates a sum into equal parts without losing a cent', function (): void {
    $shares = Money::fromDecimal('100.00')->allocate(3);

    expect($shares)->toHaveCount(3)
        ->and(Money::sum(...$shares)->toDecimal())->toBe('100.00');
});

it('does not divide evenly and still sums exactly', function (): void {
    // 0.05 across 3 = 0.02, 0.02, 0.01
    $shares = Money::fromDecimal('0.05')->allocate(3);

    expect(array_map(fn (Money $m): string => $m->toDecimal(), $shares))
        ->toBe(['0.02', '0.02', '0.01'])
        ->and(Money::sum(...$shares)->toDecimal())->toBe('0.05');
});

it('allocates by ratio and still sums exactly', function (): void {
    // A 500.00 budget split 1:2:2 across three milestones.
    $shares = Money::fromDecimal('500.00')->allocateByRatio([1, 2, 2]);

    expect(array_map(fn (Money $m): string => $m->toDecimal(), $shares))
        ->toBe(['100.00', '200.00', '200.00'])
        ->and(Money::sum(...$shares)->toDecimal())->toBe('500.00');
});

it('allocates an awkward ratio without losing a cent', function (): void {
    // 100.00 split 1:1:1 cannot be exact — the sum must still be 100.00.
    $shares = Money::fromDecimal('100.00')->allocateByRatio([1, 1, 1]);

    expect(Money::sum(...$shares)->toDecimal())->toBe('100.00')
        ->and(array_sum(array_map(fn (Money $m): int => $m->minorUnits, $shares)))
        ->toBe(10000);
});

it('rejects nonsensical allocations', function (): void {
    expect(fn () => Money::fromDecimal('10.00')->allocate(0))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('10.00')->allocateByRatio([]))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('10.00')->allocateByRatio([0, 0]))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::fromDecimal('10.00')->allocateByRatio([1, -1]))
        ->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Comparison and equality
|--------------------------------------------------------------------------
*/

it('compares amounts correctly', function (): void {
    $ten = Money::fromDecimal('10.00');
    $twenty = Money::fromDecimal('20.00');

    expect($twenty->isGreaterThan($ten))->toBeTrue()
        ->and($ten->isLessThan($twenty))->toBeTrue()
        ->and($ten->isGreaterThanOrEqualTo($ten))->toBeTrue()
        ->and($ten->equals(Money::fromDecimal('10.00')))->toBeTrue()
        ->and($ten->equals($twenty))->toBeFalse();
});

it('treats zero as not positive', function (): void {
    expect(Money::zero()->isZero())->toBeTrue()
        ->and(Money::zero()->isPositive())->toBeFalse()
        ->and(Money::fromDecimal('0.01')->isPositive())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Serialisation
|--------------------------------------------------------------------------
*/

it('serialises to minor units so precision survives json', function (): void {
    expect(Money::fromDecimal('12.34')->jsonSerialize())->toBe([
        'minor_units' => 1234,
        'currency' => 'USD',
        'decimal' => '12.34',
    ]);

    $roundTrip = json_decode(json_encode(Money::fromDecimal('12.34')), true);
    expect(Money::fromMinorUnits($roundTrip['minor_units'])->toDecimal())->toBe('12.34');
});

it('casts to a decimal string', function (): void {
    expect((string) Money::fromDecimal('7.5'))->toBe('7.50');
});
