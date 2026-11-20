<?php

declare(strict_types=1);

use App\Domain\Currency;

/*
|--------------------------------------------------------------------------
| The currency whitelist (FR-036, AC-021)
|--------------------------------------------------------------------------
| NGN is excluded from the entire application: not a wallet currency, not a
| pricing currency, not a display currency. These tests are the enforcement.
*/

it('supports exactly the four whitelisted currencies', function (): void {
    expect(Currency::displayWhitelist())->toBe(['USD', 'EUR', 'GBP', 'CAD']);
});

it('uses USD as the only base wallet currency', function (): void {
    expect(Currency::base())->toBe(Currency::USD);
});

it('parses whitelisted codes case-insensitively', function (string $input, Currency $expected): void {
    expect(Currency::fromCode($input))->toBe($expected);
})->with([
    ['USD', Currency::USD],
    ['usd', Currency::USD],
    ['EUR', Currency::EUR],
    ['gbp', Currency::GBP],
    ['CAD', Currency::CAD],
    ['  USD  ', Currency::USD],
]);

/*
|--------------------------------------------------------------------------
| NGN is rejected everywhere
|--------------------------------------------------------------------------
*/

it('rejects NGN with an explicit explanation', function (): void {
    expect(fn () => Currency::fromCode('NGN'))
        ->toThrow(InvalidArgumentException::class, 'NGN is not a supported currency');
});

it('rejects NGN regardless of case or whitespace', function (string $input): void {
    expect(fn () => Currency::fromCode($input))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['NGN'],
    ['ngn'],
    ['Ngn'],
    [' NGN '],
]);

it('names the supported currencies in the rejection message', function (): void {
    try {
        Currency::fromCode('NGN');
        $this->fail('NGN should have been rejected.');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())
            ->toContain('USD')
            ->toContain('EUR')
            ->toContain('GBP')
            ->toContain('CAD');
    }
});

it('rejects currencies that are not in the whitelist', function (string $code): void {
    expect(fn () => Currency::fromCode($code))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['JPY'],
    ['ZAR'],
    ['GHS'],
    ['KES'],
    ['XXX'],
    [''],
]);

it('has no NGN case on the enum at all', function (): void {
    $names = array_map(fn (Currency $c): string => $c->name, Currency::cases());

    expect($names)->not->toContain('NGN');
});

it('provides a symbol for every supported currency', function (Currency $currency): void {
    expect($currency->symbol())->not->toBeEmpty();
})->with(Currency::cases());

it('uses two decimal places for every supported currency', function (Currency $currency): void {
    expect($currency->minorUnitScale())->toBe(2);
})->with(Currency::cases());
