<?php

declare(strict_types=1);

use App\Domain\Currency;
use App\Domain\LedgerDirection;
use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\LedgerService;

/*
|--------------------------------------------------------------------------
| The ledger is append-only (FR-029)
|--------------------------------------------------------------------------
*/

it('writes a credit and derives the balance from it', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('100.00'),
        'Opening balance for testing',
    );

    expect($ledger->available($user)->toDecimal())->toBe('100.00');
});

it('refuses to update a ledger entry', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $entry = app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('50.00'),
        'Test credit for immutability',
    );

    expect(fn () => $entry->update(['amount_cents' => 999999]))
        ->toThrow(DomainException::class);

    // And the original value is untouched.
    expect($entry->fresh()->amount_cents)->toBe(5000);
});

it('refuses to delete a ledger entry', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $entry = app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('50.00'),
        'Test credit for deletion refusal',
    );

    expect(fn () => $entry->delete())->toThrow(DomainException::class);

    expect(LedgerEntry::find($entry->id))->not->toBeNull();
});

it('refuses a zero or negative amount', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    expect(fn () => $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::zero(), 'Zero amount test'))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a ledger entry without a real reason', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    expect(fn () => $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('10.00'), ''))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('10.00'), 'hm'))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses to write a non-USD amount to the ledger', function (): void {
    $user = User::factory()->approvedPoster()->create();

    expect(fn () => app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('10.00', Currency::EUR),
        'Euro credit should be refused',
    ))->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| Direction is derived from the kind, not chosen by the caller
|--------------------------------------------------------------------------
| An escrow hold posted as a credit would create money from nothing. The service
| derives direction from the kind so a caller physically cannot get it wrong.
*/

it('forces the correct direction for every ledger kind', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $credit = $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('10.00'), 'Direction check credit');
    $debit = $ledger->record($user, LedgerKind::ESCROW_HOLD, Money::fromDecimal('10.00'), 'Direction check debit');

    expect($credit->direction)->toBe(LedgerDirection::CREDIT)
        ->and($debit->direction)->toBe(LedgerDirection::DEBIT);
});

it('declares a direction for every kind', function (): void {
    // Exhaustive: adding a case to the enum without a direction would be a
    // compile-time error, but this proves the mapping is present at runtime too.
    foreach (LedgerKind::cases() as $kind) {
        expect($kind->direction())->toBeInstanceOf(LedgerDirection::class);
        expect($kind->label())->not->toBe('');
    }
});

/*
|--------------------------------------------------------------------------
| Balance arithmetic (FR-030)
|--------------------------------------------------------------------------
*/

it('derives a balance from credits minus debits', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('500.00'), 'Funding for test');
    $ledger->record($user, LedgerKind::ESCROW_HOLD, Money::fromDecimal('150.00'), 'Escrow hold for test');

    // 500 credited, 150 held -> 350 available.
    expect($ledger->available($user)->toDecimal())->toBe('350.00');
});

it('sums ten rows of 0.10 to exactly 1.00', function (): void {
    // The canonical floating point failure. Ten float additions of 0.1 do not
    // produce 1.0; ten integer-cent additions must.
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    for ($i = 0; $i < 10; $i++) {
        $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('0.10'), 'Ten cents test');
    }

    expect($ledger->available($user)->minorUnits)->toBe(100)
        ->and($ledger->available($user)->toDecimal())->toBe('1.00');
});

it('reports lifetime credited separately from available', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('200.00'), 'Credit for lifetime test');
    $ledger->record($user, LedgerKind::ESCROW_HOLD, Money::fromDecimal('50.00'), 'Hold for lifetime test');

    expect($ledger->lifetimeCredited($user)->toDecimal())->toBe('200.00')
        ->and($ledger->available($user)->toDecimal())->toBe('150.00');
});

it('reports escrow held as a separate figure', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('300.00'), 'Credit for escrow test');
    $ledger->record($user, LedgerKind::ESCROW_HOLD, Money::fromDecimal('120.00'), 'Escrow hold for test');

    expect($ledger->escrowHeldBy($user)->toDecimal())->toBe('120.00')
        ->and($ledger->available($user)->toDecimal())->toBe('180.00');
});

it('answers canAfford without letting the balance go negative', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('100.00'), 'Funding for afford test');

    expect($ledger->canAfford($user, Money::fromDecimal('100.00')))->toBeTrue()
        ->and($ledger->canAfford($user, Money::fromDecimal('100.01')))->toBeFalse();
});

it('creates exactly one wallet per user even when asked repeatedly', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $first = $ledger->walletFor($user);
    $second = $ledger->walletFor($user);

    expect($first->id)->toBe($second->id)
        ->and($user->wallet()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Concurrency (FR-041c)
|--------------------------------------------------------------------------
| Two simultaneous credits must not lose one another. They cannot, because each
| is an independent INSERT against no shared row — the structural advantage over
| a mutable balance column. This test simulates the interleaving deterministically.
*/

it('does not lose a credit when two are recorded before either is read', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    // Both writes happen before any balance read, mimicking two concurrent
    // requests that each insert and then respond.
    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('10.00'), 'Concurrent credit one');
    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('20.00'), 'Concurrent credit two');

    expect($ledger->available($user)->toDecimal())->toBe('30.00');
});

/*
|--------------------------------------------------------------------------
| Reconciliation (FR-041b, FR-041d)
|--------------------------------------------------------------------------
*/

it('reconciles the derived balance to the ledger after a random sequence', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('1000.00'), 'Opening credit');

    // A believable sequence: deposits, holds, a refund, a withdrawal and its return.
    $sequence = [
        [LedgerKind::ADMIN_CREDIT, '250.75', 'Second deposit'],
        [LedgerKind::ESCROW_HOLD, '300.00', 'Funded job A'],
        [LedgerKind::ESCROW_HOLD, '120.50', 'Funded job B'],
        [LedgerKind::ESCROW_REFUND, '120.50', 'Job B cancelled'],
        [LedgerKind::WITHDRAWAL_HOLD, '500.00', 'Withdrawal requested'],
        [LedgerKind::WITHDRAWAL_REJECTED_RETURN, '500.00', 'Withdrawal rejected'],
        [LedgerKind::ESCROW_HOLD, '75.25', 'Funded job C'],
    ];

    foreach ($sequence as [$kind, $amount, $reason]) {
        $ledger->record($user, $kind, Money::fromDecimal($amount), $reason);
    }

    // Independent recomputation straight from the rows, in PHP, with no service.
    $expected = LedgerEntry::where('user_id', $user->id)->get()->reduce(
        fn (int $carry, LedgerEntry $e): int => $carry + $e->direction->apply($e->amount_cents),
        0,
    );

    expect($ledger->available($user)->minorUnits)->toBe($expected);

    // And the arithmetic is checkable by hand:
    // 1000 + 250.75 - 300 - 120.50 + 120.50 - 500 + 500 - 75.25 = 875.50
    expect($ledger->available($user)->toDecimal())->toBe('875.50');
});

it('surfaces a negative balance instead of hiding it', function (): void {
    // A negative derived balance means spending rules and the ledger disagree.
    // Clamping to zero would conceal a real defect, so the service throws.
    $user = User::factory()->approvedPoster()->create();
    app(LedgerService::class)->record(
        $user,
        LedgerKind::ESCROW_HOLD,
        Money::fromDecimal('50.00'),
        'Hold with no prior credit',
    );

    expect(fn () => app(LedgerService::class)->available($user))
        ->toThrow(DomainException::class);
});
