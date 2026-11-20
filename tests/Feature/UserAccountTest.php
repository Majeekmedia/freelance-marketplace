<?php

declare(strict_types=1);

use App\Domain\UserStatus;
use App\Domain\UserType;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Registration can never mint an admin (FR-003)
|--------------------------------------------------------------------------
| The single most dangerous account bug is privilege escalation at signup.
| These tests prove it is structurally impossible, not merely discouraged.
*/

it('cannot create an admin through mass assignment', function (): void {
    $user = User::create([
        'name' => 'Attacker',
        'email' => 'attacker@example.test',
        'password' => 'password',
        'type' => 'admin',
        'status' => 'approved',
    ]);

    // `type` and `status` are not fillable, so the database defaults apply.
    expect($user->fresh()->type)->toBe(UserType::FREELANCER)
        ->and($user->fresh()->status)->toBe(UserStatus::PENDING)
        ->and($user->fresh()->isAdmin())->toBeFalse();
});

it('cannot be self-approved through mass assignment', function (): void {
    $user = User::create([
        'name' => 'Sneaky',
        'email' => 'sneaky@example.test',
        'password' => 'password',
        'status' => 'approved',
    ]);

    expect($user->fresh()->isPending())->toBeTrue()
        ->and($user->fresh()->canAct())->toBeFalse();
});

it('offers only freelancer and poster at registration', function (): void {
    expect(UserType::registrable())->toBe(['freelancer', 'poster'])
        ->and(UserType::registrable())->not->toContain('admin');
});

/*
|--------------------------------------------------------------------------
| Status gates actions, not login (FR-004, assumption A7)
|--------------------------------------------------------------------------
*/

it('lets a pending account log in but not act', function (): void {
    $user = User::factory()->pending()->create();

    expect($user->canAuthenticate())->toBeTrue()
        ->and($user->canAct())->toBeFalse();
});

it('lets an approved account act', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    expect($user->canAuthenticate())->toBeTrue()
        ->and($user->canAct())->toBeTrue();
});

it('blocks a suspended account from logging in', function (): void {
    $user = User::factory()->suspended()->create();

    expect($user->canAuthenticate())->toBeFalse()
        ->and($user->canAct())->toBeFalse();
});

it('blocks a rejected account from logging in', function (): void {
    $user = User::factory()->rejected()->create();

    expect($user->canAuthenticate())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Status transitions
|--------------------------------------------------------------------------
| Approval and suspension are the levers admin has over accounts, so the
| transitions must not be arbitrary.
*/

it('allows admin to approve a pending account', function (): void {
    $user = User::factory()->pending()->create();

    $user->approve();

    expect($user->fresh()->status)->toBe(UserStatus::APPROVED)
        ->and($user->fresh()->canAct())->toBeTrue();
});

it('allows admin to suspend an approved account', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $user->suspend();

    expect($user->fresh()->status)->toBe(UserStatus::SUSPENDED)
        ->and($user->fresh()->canAuthenticate())->toBeFalse();
});

it('refuses an illegal transition', function (): void {
    $user = User::factory()->pending()->create();

    // pending → suspended is not a valid path; a new account is approved or rejected.
    expect(fn () => $user->transitionTo(UserStatus::SUSPENDED))
        ->toThrow(DomainException::class);
});

it('treats deleted as terminal', function (): void {
    expect(UserStatus::DELETED->allowedTransitions())->toBe([]);
});

it('marks deleted_at when soft deleted by transition', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $user->transitionTo(UserStatus::DELETED);

    expect($user->fresh()->trashed())->toBeTrue()
        ->and($user->fresh()->deleted_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Type predicates
|--------------------------------------------------------------------------
*/

it('identifies each account type', function (): void {
    $freelancer = User::factory()->freelancer()->create();
    $poster = User::factory()->poster()->create();
    $admin = User::factory()->admin()->create();

    expect($freelancer->isFreelancer())->toBeTrue()
        ->and($freelancer->isPoster())->toBeFalse()
        ->and($poster->isPoster())->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue();
});

it('always creates an approved admin', function (): void {
    // An admin who cannot act is useless; the state is set deliberately.
    $admin = User::factory()->admin()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->canAct())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Query scopes
|--------------------------------------------------------------------------
*/

it('scopes to approved and pending accounts', function (): void {
    User::factory()->approvedFreelancer()->create();
    User::factory()->approvedPoster()->create();
    User::factory()->pending()->create();

    expect(User::approved()->count())->toBe(2)
        ->and(User::pending()->count())->toBe(1);
});

it('scopes by account type', function (): void {
    User::factory()->approvedFreelancer()->create();
    User::factory()->approvedFreelancer()->create();
    User::factory()->approvedPoster()->create();

    expect(User::ofType(UserType::FREELANCER)->count())->toBe(2)
        ->and(User::ofType(UserType::POSTER)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Serialisation safety
|--------------------------------------------------------------------------
*/

it('never exposes the password or remember token', function (): void {
    $user = User::factory()->create();

    $json = $user->toArray();

    expect($json)->not->toHaveKey('password')
        ->and($json)->not->toHaveKey('remember_token');
});
