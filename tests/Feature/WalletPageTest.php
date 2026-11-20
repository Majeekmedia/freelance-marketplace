<?php

declare(strict_types=1);

use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\User;
use App\Services\LedgerService;

/*
|--------------------------------------------------------------------------
| Wallet privacy (FR-018)
|--------------------------------------------------------------------------
| The route takes no user id, so there is nothing to tamper with. These tests
| prove that property rather than assuming it: they check the route shape and
| that a visitor cannot reach the page at all.
*/

it('shows a signed-in user their own wallet', function (): void {
    $user = User::factory()->approvedPoster()->create();
    app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('250.00'),
        'Wallet page test credit',
    );

    $this->actingAs($user)
        ->get('/wallet')
        ->assertOk()
        ->assertSee('$250.00');
});

it('shows an honest empty state for a user with no ledger rows', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $this->actingAs($user)
        ->get('/wallet')
        ->assertOk()
        ->assertSee('No entries yet')
        ->assertSee('$0.00');
});

it('has no route that takes a user id for the wallet', function (): void {
    // If a `/wallet/{user}` route ever appears, this fails. That route would let
    // someone change an id and read another person's money.
    $walletRoutes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r): bool => str_starts_with($r->uri(), 'wallet'));

    expect($walletRoutes)->not->toBeEmpty();

    foreach ($walletRoutes as $route) {
        expect($route->uri())->not->toContain('{');
    }
});

it('requires authentication for the wallet', function (): void {
    $this->get('/wallet')->assertRedirect('/login');
});

it('never renders another users ledger entries', function (): void {
    $a = User::factory()->approvedPoster()->create();
    $b = User::factory()->approvedPoster()->create();

    $ledger = app(LedgerService::class);
    $ledger->record($a, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('111.11'), 'Only for A');
    $ledger->record($b, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('222.22'), 'Only for B');

    $this->actingAs($a)
        ->get('/wallet')
        ->assertOk()
        ->assertSee('$111.11')
        ->assertDontSee('$222.22')
        ->assertSee('Only for A')
        ->assertDontSee('Only for B');
});

it('lists ledger entries newest first', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('10.00'), 'Older entry here');
    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('20.00'), 'Newer entry here');

    $response = $this->actingAs($user)->get('/wallet')->assertOk();

    $html = $response->getContent();
    expect(strpos($html, 'Newer entry here'))->toBeLessThan(strpos($html, 'Older entry here'));
});

it('shows the reason and actor for each entry so a balance is explainable', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Ops Admin']);
    $user = User::factory()->approvedPoster()->create();

    app(LedgerService::class)->record(
        $user,
        LedgerKind::ADMIN_CREDIT,
        Money::fromDecimal('75.00'),
        'Goodwill credit after support ticket',
        ['actor_id' => $admin->id],
    );

    $this->actingAs($user)
        ->get('/wallet')
        ->assertOk()
        ->assertSee('Goodwill credit after support ticket')
        ->assertSee('Ops Admin');
});

it('renders debit amounts with a minus sign and credit amounts with a plus', function (): void {
    $user = User::factory()->approvedPoster()->create();
    $ledger = app(LedgerService::class);

    $ledger->record($user, LedgerKind::ADMIN_CREDIT, Money::fromDecimal('100.00'), 'Credit for sign test');
    $ledger->record($user, LedgerKind::ESCROW_HOLD, Money::fromDecimal('30.00'), 'Debit for sign test');

    $html = $this->actingAs($user)->get('/wallet')->assertOk()->getContent();

    expect($html)->toContain('+$100.00')
        ->and($html)->toContain('−$30.00');
});
