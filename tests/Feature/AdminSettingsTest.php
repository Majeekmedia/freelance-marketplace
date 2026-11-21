<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Support\LogoUploader;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    SiteSettings::flush();
});

/*
|--------------------------------------------------------------------------
| Header: logo and hamburger
|--------------------------------------------------------------------------
| The console header must show the site logo, and that logo must be the one the
| admin uploaded — not a hardcoded wordmark. These are the tests that would have
| caught the header rendering a static mark while settings showed a logo.
*/

it('shows the uploaded logo in the admin header', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'ops@example.test',
        'logo' => UploadedFile::fake()->image('brand.png', 200, 60),
    ])->assertRedirect();

    $logoUrl = LogoUploader::url();
    expect($logoUrl)->not->toBeNull();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee($logoUrl, false);
});

it('falls back to the initials wordmark when no logo is uploaded', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('topbar-mark', false);
});

it('renders the brand name in the admin header from settings', function (): void {
    $admin = User::factory()->admin()->create();

    SiteSettings::set('brand_name', 'Northwind Ops');
    SiteSettings::flush();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Northwind Ops');
});

it('has a hamburger menu in the admin header', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('class="hmenu"', false)
        ->assertSee('aria-label="Menu"', false);
});

it('puts settings and sign-out in the hamburger menu, not the tab bar', function (): void {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();

    // The panel must contain these destinations.
    $panelStart = strpos($html, 'class="hmenu-panel"');
    expect($panelStart)->not->toBeFalse();

    $panel = substr($html, $panelStart, 4000);

    expect($panel)->toContain(route('admin.settings.site'))
        ->and($panel)->toContain(route('admin.settings.password'))
        ->and($panel)->toContain(route('admin.logout'));
});

it('does not put settings in the mobile tab bar', function (): void {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();

    $tabsStart = strpos($html, 'class="mobile-tabs"');
    expect($tabsStart)->not->toBeFalse();

    $tabs = substr($html, $tabsStart);

    // Four tabs, none of them settings.
    expect($tabs)->not->toContain(route('admin.settings.site'))
        ->and($tabs)->not->toContain(route('admin.settings.password'));
});

/*
|--------------------------------------------------------------------------
| Site settings (FR-027, FR-035)
|--------------------------------------------------------------------------
*/

it('saves the brand name and it appears on the customer site', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Ember Marketplace',
        'contact_email' => 'hello@ember.test',
    ])->assertRedirect(route('admin.settings.site'));

    SiteSettings::flush();

    // The customer landing page must reflect it, because no template hardcodes
    // the brand.
    $this->get('/')->assertOk()->assertSee('Ember Marketplace');
});

it('stores a logo and serves it from the public disk', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'hello@example.test',
        'logo' => UploadedFile::fake()->image('brand.png', 200, 60),
    ])->assertRedirect();

    $path = SiteSettings::logoPath();

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('rejects an svg logo', function (): void {
    $admin = User::factory()->admin()->create();

    // An SVG can carry script and would run inside an admin session.
    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'hello@example.test',
        'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    ])->assertSessionHasErrors('logo');

    expect(SiteSettings::logoPath())->toBeNull();
});

it('rejects an oversized logo', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'hello@example.test',
        'logo' => UploadedFile::fake()->image('big.png')->size(LogoUploader::MAX_KILOBYTES + 1),
    ])->assertSessionHasErrors('logo');
});

it('removes the logo and restores the wordmark', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'hello@example.test',
        'logo' => UploadedFile::fake()->image('brand.png', 200, 60),
    ]);

    expect(SiteSettings::logoPath())->not->toBeNull();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'hello@example.test',
        'remove_logo' => '1',
    ]);

    expect(SiteSettings::logoPath())->toBeNull();
});

it('rejects an invalid contact email', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Trust-Prime',
        'contact_email' => 'not-an-email',
    ])->assertSessionHasErrors('contact_email');
});

it('audits a settings change with before and after', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/settings/site', [
        'brand_name' => 'Renamed Co',
        'contact_email' => 'new@example.test',
    ]);

    $log = \App\Models\AuditLog::where('action', 'settings.site_updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->context['after']['brand_name'])->toBe('Renamed Co');
});

it('blocks a poster from the settings pages', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)->get('/admin/settings/site')->assertNotFound();
    $this->actingAs($poster)->post('/admin/settings/site', [])->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Change password (FR-005)
|--------------------------------------------------------------------------
*/

it('changes the password when the current one is correct', function (): void {
    $admin = User::factory()->admin()->create(['password' => Hash::make('OldPass123!')]);
    // Factory sets 'password' as the plain password; reset it deterministically.
    $admin->password = 'OldPass123!';
    $admin->save();

    $this->actingAs($admin)->post('/admin/settings/password', [
        'current_password' => 'OldPass123!',
        'password' => 'NewStrongPass123!',
        'password_confirmation' => 'NewStrongPass123!',
    ])->assertRedirect(route('admin.settings.password'));

    expect(Hash::check('NewStrongPass123!', $admin->fresh()->password))->toBeTrue();
});

it('refuses a password change without the current password', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->password = 'OldPass123!';
    $admin->save();
    $before = $admin->fresh()->password;

    $this->actingAs($admin)->post('/admin/settings/password', [
        'current_password' => 'WrongPass123!',
        'password' => 'NewStrongPass123!',
        'password_confirmation' => 'NewStrongPass123!',
    ])->assertSessionHasErrors('current_password');

    expect($admin->fresh()->password)->toBe($before);
});

it('refuses a weak new password', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->password = 'OldPass123!';
    $admin->save();

    $this->actingAs($admin)->post('/admin/settings/password', [
        'current_password' => 'OldPass123!',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});

it('refuses a mismatched confirmation', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->password = 'OldPass123!';
    $admin->save();

    $this->actingAs($admin)->post('/admin/settings/password', [
        'current_password' => 'OldPass123!',
        'password' => 'NewStrongPass123!',
        'password_confirmation' => 'DifferentPass123!',
    ])->assertSessionHasErrors('password');
});

it('audits a password change', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->password = 'OldPass123!';
    $admin->save();

    $this->actingAs($admin)->post('/admin/settings/password', [
        'current_password' => 'OldPass123!',
        'password' => 'NewStrongPass123!',
        'password_confirmation' => 'NewStrongPass123!',
    ]);

    $log = \App\Models\AuditLog::where('action', 'account.password_changed')->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($admin->id);
});

it('blocks a poster from changing an admin password', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)->get('/admin/settings/password')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Admin password reset by email
|--------------------------------------------------------------------------
*/

it('offers a forgot-password page on the admin login', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee(route('admin.password.request'), false);
});

it('sends a reset link to an admin address', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'admin-reset@example.test']);

    $this->post('/admin/forgot-password', ['email' => $admin->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $admin->email]);
});

it('does NOT send a reset link for a customer address', function (): void {
    $poster = User::factory()->approvedPoster()->create(['email' => 'customer-reset@example.test']);

    $this->post('/admin/forgot-password', ['email' => $poster->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    // Critically: no token was created for a customer from the admin surface.
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $poster->email]);
});

it('returns the same message whether or not the address is an admin', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'is-admin@example.test']);
    User::factory()->approvedPoster()->create(['email' => 'not-admin@example.test']);

    $this->post('/admin/forgot-password', ['email' => $admin->email]);
    $adminMessage = session('status');

    $this->post('/admin/forgot-password', ['email' => 'not-admin@example.test']);
    $posterMessage = session('status');

    $this->post('/admin/forgot-password', ['email' => 'nobody@example.test']);
    $unknownMessage = session('status');

    // Identical, so the form cannot be used to discover administrator addresses.
    expect($adminMessage)->toBe($posterMessage)
        ->and($posterMessage)->toBe($unknownMessage);
});

it('does not send a link to a suspended admin', function (): void {
    $admin = User::factory()->admin()->create(['email' => 'suspended-admin@example.test']);
    $admin->status = \App\Domain\UserStatus::SUSPENDED;
    $admin->save();

    $this->post('/admin/forgot-password', ['email' => $admin->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $admin->email]);
});

it('rate limits the admin reset form', function (): void {
    // Six per minute; the seventh in the same window is refused.
    for ($i = 0; $i < 6; $i++) {
        $this->post('/admin/forgot-password', ['email' => 'x@example.test']);
    }

    $this->post('/admin/forgot-password', ['email' => 'x@example.test'])
        ->assertStatus(429);
});
