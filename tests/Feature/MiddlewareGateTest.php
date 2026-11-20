<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Middleware gate tests (FR-004, FR-024)
|--------------------------------------------------------------------------
| These routes are registered at runtime so the test proves the middleware
| itself, independently of any controller that will use it later.
*/

beforeEach(function (): void {
    Route::middleware(['web', 'auth', 'account'])
        ->get('/_test/gated', fn () => response('allowed'));

    Route::middleware(['web', 'auth', 'admin'])
        ->get('/_test/admin', fn () => response('admin area'));
});

/*
|--------------------------------------------------------------------------
| AccountGate — approval gates actions, not login
|--------------------------------------------------------------------------
*/

it('lets an approved account through the gate', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->get('/_test/gated')
        ->assertOk()
        ->assertSee('allowed');
});

it('blocks a pending account from a gated action', function (): void {
    $user = User::factory()->pending()->create();

    $this->actingAs($user)
        ->get('/_test/gated')
        ->assertForbidden()
        ->assertSee('awaiting approval', false);
});

it('blocks a suspended account and ends its session', function (): void {
    $user = User::factory()->suspended()->create();

    $this->actingAs($user)
        ->get('/_test/gated')
        ->assertForbidden();

    $this->assertGuest();
});

it('blocks a rejected account from a gated action', function (): void {
    $user = User::factory()->rejected()->create();

    $this->actingAs($user)
        ->get('/_test/gated')
        ->assertForbidden();
});

it('requires authentication before the gate', function (): void {
    // The login route does not exist yet (that is T-005). What matters here is
    // that an unauthenticated request never reaches the protected handler.
    // Without a `login` route the auth middleware aborts, so assert non-success
    // rather than a specific redirect target.
    $response = $this->get('/_test/gated');

    expect($response->status())->toBeIn([302, 401])
        ->and($response->getContent())->not->toContain('allowed');
});

/*
|--------------------------------------------------------------------------
| AdminGate — role, not visibility
|--------------------------------------------------------------------------
*/

it('lets an admin reach the admin area', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/_test/admin')
        ->assertOk()
        ->assertSee('admin area');
});

it('hides the admin area from a freelancer', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    // 404 not 403 — do not confirm the admin area exists.
    $this->actingAs($user)
        ->get('/_test/admin')
        ->assertNotFound();
});

it('hides the admin area from a poster', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $this->actingAs($user)
        ->get('/_test/admin')
        ->assertNotFound();
});

it('requires authentication for the admin area', function (): void {
    // Same reasoning as the gated route: assert non-success, not a redirect
    // target that does not exist yet.
    $response = $this->get('/_test/admin');

    expect($response->status())->toBeIn([302, 401])
        ->and($response->getContent())->not->toContain('admin area');
});
