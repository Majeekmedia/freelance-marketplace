<?php

declare(strict_types=1);

use App\Domain\UserStatus;
use App\Domain\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| Registration (FR-003, FR-005)
|--------------------------------------------------------------------------
*/

it('registers a freelancer as pending', function (): void {
    $response = $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'type' => 'freelancer',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user = User::where('email', 'ada@example.test')->firstOrFail();

    expect($user->type)->toBe(UserType::FREELANCER)
        ->and($user->status)->toBe(UserStatus::PENDING)
        ->and($user->canAct())->toBeFalse();

    $this->assertAuthenticatedAs($user);
});

it('registers a poster as pending', function (): void {
    $this->post('/register', [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'type' => 'poster',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'grace@example.test')->firstOrFail();

    expect($user->type)->toBe(UserType::POSTER)
        ->and($user->status)->toBe(UserStatus::PENDING);
});

it('refuses registration with the admin type', function (): void {
    $response = $this->post('/register', [
        'name' => 'Attacker',
        'email' => 'attacker@example.test',
        'type' => 'admin',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertSessionHasErrors('type');

    expect(User::where('email', 'attacker@example.test')->exists())->toBeFalse();
});

it('requires a confirmed password of adequate strength', function (): void {
    $this->post('/register', [
        'name' => 'Weak',
        'email' => 'weak@example.test',
        'type' => 'freelancer',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'weak@example.test')->exists())->toBeFalse();
});

it('refuses a duplicate email', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->post('/register', [
        'name' => 'Second',
        'email' => 'taken@example.test',
        'type' => 'freelancer',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertSessionHasErrors('email');
});

it('hashes the stored password', function (): void {
    $this->post('/register', [
        'name' => 'Hash Check',
        'email' => 'hash@example.test',
        'type' => 'freelancer',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'hash@example.test')->firstOrFail();

    expect($user->password)->not->toBe('secret123')
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Login (FR-005, FR-006)
|--------------------------------------------------------------------------
*/

it('signs an approved user in', function (): void {
    $user = User::factory()->approvedFreelancer()->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('signs a pending user in so they can complete their profile', function (): void {
    $user = User::factory()->pending()->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('gives the same error for a wrong password and an unknown email', function (): void {
    $user = User::factory()->approvedFreelancer()->create([
        'password' => Hash::make('secret123'),
    ]);

    $wrongPassword = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $unknownEmail = $this->post('/login', [
        'email' => 'nobody@example.test',
        'password' => 'secret123',
    ]);

    expect($wrongPassword->getSession()->get('errors')->first('email'))
        ->toBe($unknownEmail->getSession()->get('errors')->first('email'));
});

it('refuses a suspended account without revealing why', function (): void {
    $user = User::factory()->suspended()->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated failures', function (): void {
    RateLimiter::clear('');

    $user = User::factory()->approvedFreelancer()->create([
        'password' => Hash::make('secret123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    // Even the correct password is refused while throttled.
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out and invalidates the session', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

/*
|--------------------------------------------------------------------------
| Password reset (FR-005)
|--------------------------------------------------------------------------
*/

it('returns the same response whether or not the email exists', function (): void {
    User::factory()->create(['email' => 'known@example.test']);

    $known = $this->post('/forgot-password', ['email' => 'known@example.test']);
    $unknown = $this->post('/forgot-password', ['email' => 'unknown@example.test']);

    // Both redirect back with the same neutral status message.
    expect($known->getSession()->get('status'))
        ->toBe($unknown->getSession()->get('status'))
        ->and($known->getSession()->get('status'))
        ->toContain('If that email address is registered');
});

it('rejects an invalid reset token', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->post('/reset-password', [
        'token' => 'totally-invalid-token',
        'email' => $user->email,
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ])->assertSessionHasErrors('email');

    // The password is unchanged.
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('resets the password with a valid token exactly once', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $token = \Illuminate\Support\Facades\Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'brandnew123',
        'password_confirmation' => 'brandnew123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('brandnew123', $user->fresh()->password))->toBeTrue();

    // The same token must not work a second time.
    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'thirdpass123',
        'password_confirmation' => 'thirdpass123',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('brandnew123', $user->fresh()->password))->toBeTrue();
});
