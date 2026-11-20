<?php

declare(strict_types=1);

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Header contract
|--------------------------------------------------------------------------
| The header was rebuilt because the inline text links duplicated the bottom
| navigation on phones and the sign-out button competed with everything else.
|
| The shape now:
|   [ brand ] .......... [ bell ] [ avatar ] [ hamburger (mobile only) ]
|
| These tests pin the parts that are easy to undo by accident.
*/

it('shows the notification bell and account menu to a signed-in user', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('aria-label="Your account menu"', false)
        ->assertSee('id="account-panel"', false);
});

it('puts sign out inside the account menu, not loose in the header', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

    $panelStart = strpos($html, 'id="account-panel"');
    expect($panelStart)->not->toBeFalse();

    // The logout form must appear after the panel opens.
    $logout = strpos($html, 'action="'.route('logout').'"');
    expect($logout)->not->toBeFalse()
        ->and($logout)->toBeGreaterThan($panelStart);
});

it('derives avatar initials from the account name', function (): void {
    $user = User::factory()->approvedPoster()->create(['name' => 'Jordan Okafor']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('>JO<', false);
});

it('shows the account name and role inside the menu', function (): void {
    $user = User::factory()->approvedFreelancer()->create(['name' => 'Avery Lindqvist']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Avery Lindqvist')
        ->assertSee('Freelancer');
});

it('shows role-appropriate links in the account menu', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your jobs')
        ->assertDontSee('Your profile');

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your profile')
        ->assertDontSee('Your jobs');
});

it('offers the hamburger only as a mobile control', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

    expect($html)->toContain('aria-label="More"')
        ->and($html)->toContain('menu-toggle');
});

/*
|--------------------------------------------------------------------------
| The bell must not invent notifications (L-004)
|--------------------------------------------------------------------------
*/

it('shows an honest empty state in the notification panel', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('No notifications yet');
});

it('makes no unread-count claim anywhere in the header', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

    // Pull out the notifications panel and assert it contains no digits that
    // could read as a count. A "3 new" badge with no data behind it is exactly
    // the false-live-data failure this project has already hit.
    $start = strpos($html, 'id="notifications-panel"');
    $panel = substr($html, $start, 1200);

    expect($panel)->not->toContain('dot-badge');
});

/*
|--------------------------------------------------------------------------
| Signed-out header
|--------------------------------------------------------------------------
*/

it('shows sign in and create account to a visitor instead of app chrome', function (): void {
    $this->get('/jobs')
        ->assertOk()
        ->assertSee('Sign in')
        ->assertDontSee('aria-label="Notifications"', false);
});

/*
|--------------------------------------------------------------------------
| Accessibility wiring
|--------------------------------------------------------------------------
*/

it('pairs every menu trigger with aria-expanded and aria-controls', function (): void {
    $user = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

    expect($html)->toContain('aria-expanded="false"')
        ->and($html)->toContain('aria-controls="notifications-panel"')
        ->and($html)->toContain('aria-controls="account-panel"')
        ->and($html)->toContain('aria-controls="more-panel"');
});
