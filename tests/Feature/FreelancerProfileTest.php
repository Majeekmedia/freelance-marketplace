<?php

declare(strict_types=1);

use App\Models\FreelancerProfile;
use App\Models\Portfolio;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Profile editing (FR-007)
|--------------------------------------------------------------------------
*/

it('lets a freelancer save a profile', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->put('/profile', [
            'headline' => 'Product designer',
            'bio' => 'I design dashboards.',
            'location' => 'Remote',
            'skills' => 'Figma, Laravel, Figma',
            'hourly_rate' => '45.50',
            'is_public' => '1',
        ])
        ->assertRedirect();

    $profile = $user->fresh()->freelancerProfile;

    expect($profile->headline)->toBe('Product designer')
        ->and($profile->skills)->toBe(['Figma', 'Laravel']) // deduplicated
        ->and($profile->hourly_rate_cents)->toBe(4550)      // integer cents
        ->and($profile->is_public)->toBeTrue();
});

it('rejects a non-numeric hourly rate', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->put('/profile', ['hourly_rate' => 'about fifty'])
        ->assertSessionHasErrors('hourly_rate');
});

it('bounds the skills list so a profile cannot become keyword spam', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $skills = implode(', ', array_map(fn (int $i): string => "skill{$i}", range(1, 40)));

    $this->actingAs($user)
        ->put('/profile', ['skills' => $skills, 'headline' => 'Test'])
        ->assertRedirect();

    expect($user->fresh()->freelancerProfile->skills)->toHaveCount(20);
});

it('prevents a pending freelancer publishing publicly', function (): void {
    $user = User::factory()->pending()->create();

    // Publishing is a policy decision; saving a private draft is allowed.
    $this->actingAs($user)
        ->put('/profile', ['headline' => 'Draft', 'is_public' => '1'])
        ->assertForbidden();
});

it('lets a pending freelancer save a private draft', function (): void {
    $user = User::factory()->pending()->create();

    $this->actingAs($user)
        ->put('/profile', ['headline' => 'Draft headline'])
        ->assertRedirect();

    $profile = $user->fresh()->freelancerProfile;

    expect($profile->headline)->toBe('Draft headline')
        ->and($profile->is_public)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Public profile — what a visitor may and may not see (FR-007, NFR-009)
|--------------------------------------------------------------------------
*/

it('shows a public profile without leaking private fields', function (): void {
    $user = User::factory()->approvedFreelancer()->create([
        'email' => 'private-address@example.test',
    ]);
    FreelancerProfile::factory()->create([
        'user_id' => $user->id,
        'headline' => 'Available for work',
        'is_public' => true,
    ]);

    $response = $this->get("/freelancers/{$user->id}");

    $response->assertOk()
        ->assertSee('Available for work')
        ->assertDontSee('private-address@example.test');
});

it('hides a private profile from visitors', function (): void {
    $user = User::factory()->approvedFreelancer()->create();
    FreelancerProfile::factory()->private()->create(['user_id' => $user->id]);

    $this->get("/freelancers/{$user->id}")->assertForbidden();
});

it('hides a pending freelancer from visitors', function (): void {
    $user = User::factory()->pending()->create();
    FreelancerProfile::factory()->create(['user_id' => $user->id, 'is_public' => true]);

    $this->get("/freelancers/{$user->id}")->assertForbidden();
});

it('lets the owner view their own private profile', function (): void {
    $user = User::factory()->approvedFreelancer()->create();
    FreelancerProfile::factory()->private()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/freelancers/{$user->id}")
        ->assertOk();
});

it('only lists approved public profiles in the directory', function (): void {
    $public = User::factory()->approvedFreelancer()->create(['name' => 'Public Person']);
    FreelancerProfile::factory()->create(['user_id' => $public->id, 'is_public' => true]);

    $private = User::factory()->approvedFreelancer()->create(['name' => 'Private Person']);
    FreelancerProfile::factory()->private()->create(['user_id' => $private->id]);

    $pending = User::factory()->pending()->create(['name' => 'Pending Person']);
    FreelancerProfile::factory()->create(['user_id' => $pending->id, 'is_public' => true]);

    $this->get('/freelancers')
        ->assertOk()
        ->assertSee('Public Person')
        ->assertDontSee('Private Person')
        ->assertDontSee('Pending Person');
});

/*
|--------------------------------------------------------------------------
| Portfolio — object-level access (FR-008, AC-006)
|--------------------------------------------------------------------------
| The central risk: freelancer A must not touch freelancer B's entry, even by
| calling the route directly.
*/

it('lets a freelancer add a portfolio item', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->post('/portfolio', [
            'title' => 'Dashboard redesign',
            'description' => 'A finance dashboard.',
            'external_url' => 'https://example.test/work',
        ])
        ->assertRedirect();

    expect($user->portfolios()->count())->toBe(1);
});

it('rejects a javascript: url in a portfolio item', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->post('/portfolio', [
            'title' => 'Sneaky',
            'external_url' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors('external_url');

    expect($user->portfolios()->count())->toBe(0);
});

it('blocks freelancer A from editing freelancer B portfolio item', function (): void {
    $a = User::factory()->approvedFreelancer()->create();
    $b = User::factory()->approvedFreelancer()->create();
    $item = Portfolio::factory()->create(['user_id' => $b->id, 'title' => 'B work']);

    $this->actingAs($a)
        ->put("/portfolio/{$item->id}", ['title' => 'Hijacked'])
        ->assertForbidden();

    expect($item->fresh()->title)->toBe('B work');
});

it('blocks freelancer A from deleting freelancer B portfolio item', function (): void {
    $a = User::factory()->approvedFreelancer()->create();
    $b = User::factory()->approvedFreelancer()->create();
    $item = Portfolio::factory()->create(['user_id' => $b->id]);

    $this->actingAs($a)
        ->delete("/portfolio/{$item->id}")
        ->assertForbidden();

    expect(Portfolio::find($item->id))->not->toBeNull();
});

it('lets the owner edit and delete their own item', function (): void {
    $user = User::factory()->approvedFreelancer()->create();
    $item = Portfolio::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put("/portfolio/{$item->id}", ['title' => 'Updated'])
        ->assertRedirect();

    expect($item->fresh()->title)->toBe('Updated');

    $this->actingAs($user)
        ->delete("/portfolio/{$item->id}")
        ->assertRedirect();

    expect(Portfolio::find($item->id))->toBeNull();
});

it('blocks a poster from adding portfolio items', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->post('/portfolio', ['title' => 'Not mine to add'])
        ->assertForbidden();
});

it('blocks a pending freelancer from adding portfolio items', function (): void {
    $user = User::factory()->pending()->create();

    $this->actingAs($user)
        ->post('/portfolio', ['title' => 'Too early'])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Reachability — a page nobody can navigate to is not a delivered feature
|--------------------------------------------------------------------------
| Found during browser verification: the routes and views existed but the
| layout had no links, so a real user could never reach /profile or /portfolio.
| These tests fail if that regresses.
*/

it('links a freelancer to their own profile and portfolio from the dashboard', function (): void {
    $user = User::factory()->approvedFreelancer()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(route('profile.edit'), false)
        ->assertSee(route('portfolio.index'), false);
});

it('does not offer freelancer links to a poster', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee(route('portfolio.index'), false);
});

it('exposes an edit control for every owned portfolio item', function (): void {
    $user = User::factory()->approvedFreelancer()->create();
    $item = Portfolio::factory()->create(['user_id' => $user->id]);

    // The route existed but had no form pointing at it, so update could only
    // ever be exercised by a hand-crafted request.
    $this->actingAs($user)
        ->get('/portfolio')
        ->assertOk()
        ->assertSee(route('portfolio.update', $item), false);
});

it('never renders an edit control for another freelancer item', function (): void {
    $a = User::factory()->approvedFreelancer()->create();
    $b = User::factory()->approvedFreelancer()->create();
    $foreign = Portfolio::factory()->create(['user_id' => $b->id]);

    $this->actingAs($a)
        ->get('/portfolio')
        ->assertOk()
        ->assertDontSee(route('portfolio.update', $foreign), false);
});
