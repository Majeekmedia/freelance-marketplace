<?php

declare(strict_types=1);

use App\Domain\UserStatus;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| The approval loop, end to end (FR-004, FR-024)
|--------------------------------------------------------------------------
| T-003 proved the gate in isolation with test routes. This proves it on the
| real application: a registered user is blocked, an admin approves them, and
| the same action now works.
*/

it('walks the full approval story', function (): void {
    $admin = User::factory()->admin()->create();

    // 1. A visitor registers.
    $this->post('/register', [
        'name' => 'New Freelancer',
        'email' => 'new@example.test',
        'type' => 'freelancer',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'new@example.test')->firstOrFail();

    // 2. They are pending and cannot act.
    expect($user->status)->toBe(UserStatus::PENDING)
        ->and($user->canAct())->toBeFalse();

    // 3. They can still reach their own dashboard, which explains the state.
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('awaiting approval', false);

    // 4. Admin approves.
    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/approve")
        ->assertRedirect();

    $user->refresh();

    // 5. The account can now act.
    expect($user->status)->toBe(UserStatus::APPROVED)
        ->and($user->canAct())->toBeTrue();

    // 6. And the dashboard no longer shows the pending notice.
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('awaiting approval', false);
});

/*
|--------------------------------------------------------------------------
| Admin area access
|--------------------------------------------------------------------------
*/

it('hides the admin area from non-admins', function (string $factory): void {
    $user = User::factory()->{$factory}()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertNotFound();
})->with([
    'freelancer' => ['approvedFreelancer'],
    'poster' => ['approvedPoster'],
]);

it('lets an admin list accounts', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->pending()->create(['name' => 'Pending Person']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Pending Person');
});

it('filters accounts by status', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->pending()->create(['name' => 'Still Pending']);
    User::factory()->approvedFreelancer()->create(['name' => 'Already Approved']);

    $this->actingAs($admin)
        ->get('/admin/users?status=pending')
        ->assertOk()
        ->assertSee('Still Pending')
        ->assertDontSee('Already Approved');
});

/*
|--------------------------------------------------------------------------
| Transitions through the admin routes
|--------------------------------------------------------------------------
*/

it('suspends an approved account and blocks it afterwards', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/suspend")
        ->assertRedirect();

    expect($user->fresh()->canAuthenticate())->toBeFalse()
        ->and($user->fresh()->canAct())->toBeFalse();
});

it('reinstates a suspended account', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->suspended()->create();

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/reinstate")
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::APPROVED);
});

it('refuses an illegal transition through the route', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->pending()->create();

    // pending → suspended is not a legal path.
    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/suspend")
        ->assertSessionHasErrors('user');

    expect($user->fresh()->status)->toBe(UserStatus::PENDING);
});

/*
|--------------------------------------------------------------------------
| Guard rails on the admin themselves
|--------------------------------------------------------------------------
*/

it('prevents an admin suspending their own account', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/users/{$admin->id}/suspend")
        ->assertSessionHasErrors('user');

    expect($admin->fresh()->status)->toBe(UserStatus::APPROVED);
});

it('prevents deleting the only administrator', function (): void {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    // With two admins, deleting one is fine.
    $this->actingAs($other)
        ->delete("/admin/users/{$admin->id}")
        ->assertRedirect();

    // Now only one remains; deleting them would lock everyone out.
    $remaining = $other->fresh();

    $this->actingAs($remaining)
        ->delete("/admin/users/{$remaining->id}")
        ->assertSessionHasErrors('user');

    expect(User::admins()->count())->toBe(1);
});

it('blocks an unauthenticated delete', function (): void {
    $user = User::factory()->pending()->create();

    $this->delete("/admin/users/{$user->id}")->assertRedirect('/login');

    expect(User::find($user->id))->not->toBeNull();
});
