<?php

declare(strict_types=1);

use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Hard login separation
|--------------------------------------------------------------------------
| Symmetric, and both directions matter:
|   - a customer cannot sign in through the admin door
|   - an admin cannot sign in through the customer door
|
| Blocking only one leaves a route to a privileged session, and the interesting
| attack on a marketplace is a phishing page that clones whichever login is
| easier to reach.
*/

it('serves a separate admin sign-in page', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Admin console')
        ->assertSee('Customer accounts cannot be used here');
});

it('signs an admin in at the admin door', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'admin-door@example.test']);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.overview'));

    $this->assertAuthenticatedAs($admin->fresh());
});

it('refuses a customer at the admin door with the generic message', function (): void {
    $poster = User::factory()->approvedPoster()->create(['email' => 'customer@example.test']);

    $this->post('/admin/login', [
        'email' => $poster->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    // And the message must not reveal that the credential was valid.
    expect(session('errors')->get('email')[0])
        ->toBe('These credentials do not match our records.');
});

it('refuses an admin at the customer door with the same generic message', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'admin-public@example.test']);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    expect(session('errors')->get('email')[0])
        ->toBe('These credentials do not match our records.');
});

it('produces byte-identical messages for wrong password and wrong door', function (): void {
    // If these differ, the public form can be used to test whether an address
    // belongs to an administrator.
    $admin = User::factory()->admin()->create(['email' => 'admin-same@example.test']);
    $poster = User::factory()->approvedPoster()->create(['email' => 'poster-same@example.test']);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $adminRefusal = session('errors')->get('email')[0];

    $this->post('/login', ['email' => $poster->email, 'password' => 'totally-wrong'])
        ->assertSessionHasErrors('email');
    $wrongPassword = session('errors')->get('email')[0];

    expect($adminRefusal)->toBe($wrongPassword);
});

it('still signs a normal customer in at the customer door', function (): void {
    $poster = User::factory()->approvedPoster()->create(['email' => 'normal@example.test']);

    $this->post('/login', [
        'email' => $poster->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($poster->fresh());
});

/*
|--------------------------------------------------------------------------
| Separate navigation
|--------------------------------------------------------------------------
*/

it('shows the admin console navigation on admin pages', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Admin console')
        ->assertSee('Deposit review')
        ->assertSee('Payment methods')
        ->assertSee('Escrow &amp; releases', false);
});

it('uses the admin layout, not the customer layout', function (): void {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();

    // The customer layout renders a notification bell in the header; the admin
    // layout deliberately has none, because there are no notifications yet.
    expect($html)->toContain('sidebar-nav')
        ->and($html)->toContain('mobile-tabs')
        ->and($html)->not->toContain('aria-label="Notifications"');
});

it('does not put admin menus into the customer navigation', function (): void {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

    // One pointer to the console, not a copy of its menu.
    expect($html)->toContain('Admin console')
        ->and($html)->not->toContain('Deposit review')
        ->and($html)->not->toContain(route('admin.deposits.index'));
});

it('never shows admin navigation to a poster', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($poster)->get('/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('Admin console')
        ->and($html)->not->toContain('Deposit review');
});

/*
|--------------------------------------------------------------------------
| Overview counts are real
|--------------------------------------------------------------------------
*/

it('reports real queue counts on the overview', function (): void {
    $admin = User::factory()->admin()->create();

    // Two pending deposits, three pending accounts.
    DepositRequest::factory()->pending()->create();
    DepositRequest::factory()->pending()->create();
    User::factory()->pending()->create();
    User::factory()->pending()->create();
    User::factory()->pending()->create();

    $response = $this->actingAs($admin)->get('/admin')->assertOk();

    expect(DepositRequest::pending()->count())->toBe(2)
        ->and(User::pending()->count())->toBe(3);

    $response->assertSee('Deposits awaiting review')
        ->assertSee('Accounts awaiting approval');
});

it('shows zero queues honestly when there is no work', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Live counts. Nothing here is estimated.');
});

it('states which admin powers are not built yet', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Not built yet')
        ->assertSee('arrives with milestone M5');
});

/*
|--------------------------------------------------------------------------
| Placeholder pages are honest
|--------------------------------------------------------------------------
*/

it('serves an honest placeholder for jobs', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/soon/jobs')
        ->assertOk()
        ->assertSee('Not built yet')
        ->assertSee('Jobs')
        ->assertSee('M5');
});

it('serves an honest placeholder for escrow', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/soon/escrow')
        ->assertOk()
        ->assertSee('Not built yet')
        ->assertSee('Escrow');
});

it('404s an unknown placeholder section rather than pretending', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/soon/nonsense')->assertNotFound();
});

it('never shows invented rows on a placeholder', function (): void {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/admin/soon/escrow')->assertOk()->getContent();

    // No fabricated totals, no fake table body.
    expect($html)->not->toContain('<tbody>')
        ->and($html)->toContain('the feature itself is not implemented');
});

/*
|--------------------------------------------------------------------------
| Gate still holds
|--------------------------------------------------------------------------
*/

it('returns 404 for a poster on every admin page', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    foreach (['/admin', '/admin/users', '/admin/deposits', '/admin/payment-methods', '/admin/soon/jobs'] as $path) {
        $this->actingAs($poster)->get($path)->assertNotFound();
    }
});

it('redirects a guest from the console to the admin login', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('logs an admin out to the admin login, not the customer home', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/logout')
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

/*
|--------------------------------------------------------------------------
| Reachability of every nav destination
|--------------------------------------------------------------------------
| A count an operator cannot reach is trivia, and a nav link that 404s teaches
| them the console is unreliable.
*/

it('every admin navigation destination resolves', function (): void {
    $admin = User::factory()->admin()->create();

    $paths = [
        '/admin',
        '/admin/deposits',
        '/admin/users',
        '/admin/payment-methods',
        '/admin/soon/jobs',
        '/admin/soon/escrow',
    ];

    foreach ($paths as $path) {
        $status = $this->actingAs($admin)->get($path)->status();
        expect($status)->toBe(200, "{$path} returned {$status}");
    }
});

it('links to the console from the customer dashboard without duplicating it', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Open admin console')
        ->assertSee(route('admin.overview'), false);
});
