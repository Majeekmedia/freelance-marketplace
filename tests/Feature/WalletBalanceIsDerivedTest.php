<?php

declare(strict_types=1);

use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| FR-041 — no stored balance anywhere
|--------------------------------------------------------------------------
| This is the requirement that keeps every other money figure honest. If a
| `balance` column ever appears, the ledger stops being the single source of
| truth and "why is my balance wrong?" becomes unanswerable.
|
| The rule is enforced three ways: this test, a grep guard over migrations, and
| the absence of any update path in the service.
*/

it('has no balance column on any table', function (): void {
    $offenders = [];

    foreach (Schema::getTableListing() as $table) {
        // SQLite exposes internal tables prefixed with sqlite_.
        if (str_starts_with($table, 'sqlite_')) {
            continue;
        }

        foreach (Schema::getColumnListing($table) as $column) {
            if (str_contains(strtolower($column), 'balance')) {
                $offenders[] = "{$table}.{$column}";
            }
        }
    }

    expect($offenders)->toBe([], 'A stored balance defeats FR-041: '.implode(', ', $offenders));
});

it('has no balance column in any migration file', function (): void {
    // A column could be added in a migration that has not been run here, so the
    // source is checked as well as the live schema.
    $offenders = [];

    foreach (glob(database_path('migrations/*.php')) as $file) {
        $contents = file_get_contents($file);

        // Look for column declarations containing "balance".
        if (preg_match_all('/(?:table->|Schema::)\w*\(?[\'"]([\w]*balance[\w]*)[\'"]/i', $contents, $matches)) {
            foreach ($matches[1] as $column) {
                $offenders[] = basename($file)." → {$column}";
            }
        }
    }

    expect($offenders)->toBe([], 'Balance columns found in migrations: '.implode(', ', $offenders));
});

it('derives every wallet figure rather than reading a stored one', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('420.00'), 'Derivation proof credit');

    // The wallets table must hold identity only.
    $walletColumns = Schema::getColumnListing('wallets');

    expect($walletColumns)->not->toContain('balance')
        ->and($walletColumns)->not->toContain('available_balance')
        ->and($walletColumns)->not->toContain('amount')
        ->and($ledger->available($user)->toDecimal())->toBe('420.00');
});

/*
|--------------------------------------------------------------------------
| The database refuses mutation, not just the model
|--------------------------------------------------------------------------
| The model guard is bypassable by raw SQL. These prove the database itself
| holds the line for the two facts that must never be wrong: a row cannot be
| changed, and an amount cannot be zero or negative.
*/

it('refuses a raw SQL update of a ledger row', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $entry = app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('100.00'),
        'Raw update refusal test',
    );

    expect(fn () => DB::table('ledger_entries')
        ->where('id', $entry->id)
        ->update(['amount_cents' => 999999]))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect($entry->fresh()->amount_cents)->toBe(10000);
});

it('refuses a raw SQL delete of a ledger row', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $entry = app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('100.00'),
        'Raw delete refusal test',
    );

    expect(fn () => DB::table('ledger_entries')->where('id', $entry->id)->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(DB::table('ledger_entries')->where('id', $entry->id)->exists())->toBeTrue();
});

it('refuses a raw SQL insert with a zero amount', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $wallet = app(LedgerService::class)->walletFor($user);

    expect(fn () => DB::table('ledger_entries')->insert([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'kind' => 'admin_credit',
        'direction' => 'credit',
        'amount_cents' => 0,
        'currency' => 'USD',
        'reason' => 'Zero amount via raw SQL',
        'created_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('refuses a raw SQL insert with an unknown direction', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $wallet = app(LedgerService::class)->walletFor($user);

    // A direction typo would be silently excluded from every balance sum, which
    // would look like money vanishing. The database refuses it.
    expect(fn () => DB::table('ledger_entries')->insert([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'kind' => 'admin_credit',
        'direction' => 'crdeit',
        'amount_cents' => 500,
        'currency' => 'USD',
        'reason' => 'Typo direction via raw SQL',
        'created_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
