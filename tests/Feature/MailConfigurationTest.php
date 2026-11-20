<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Mail configuration lives in the environment (FR-042, FR-043)
|--------------------------------------------------------------------------
| The important tests here are the negative ones: prove that no mail
| credential can reach the database or a rendered page, because that was the
| rejected design and it must not creep back in.
*/

it('reads mail settings from config, not from the database', function (): void {
    // There must be no table holding SMTP configuration.
    $tables = collect(Schema::getTables())->pluck('name');

    $suspicious = $tables->filter(
        fn (string $table): bool => str_contains($table, 'smtp')
            || str_contains($table, 'mail_settings')
            || str_contains($table, 'mailer')
    );

    expect($suspicious->all())->toBe([],
        'Mail settings must not be stored in the database. Found: '.$suspicious->implode(', '));
});

it('exposes no route that accepts mail credentials', function (): void {
    $routes = collect(app('router')->getRoutes()->getRoutes());

    $offenders = $routes->filter(function ($route): bool {
        $uri = strtolower($route->uri());

        return str_contains($uri, 'smtp') || str_contains($uri, 'mail-settings')
            || str_contains($uri, 'mailer');
    })->map(fn ($route): string => $route->uri());

    expect($offenders->all())->toBe([],
        'No web route may exist for editing mail settings. Found: '.$offenders->implode(', '));
});

it('configures the mailer from environment values', function (): void {
    // config/mail.php reads env(); this asserts the wiring is intact.
    expect(config('mail.default'))->not->toBeNull()
        ->and(config('mail.from.address'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| The test command
|--------------------------------------------------------------------------
*/

it('refuses an invalid recipient address', function (): void {
    $this->artisan('mail:test', ['to' => 'not-an-email'])
        ->expectsOutputToContain('Not a valid email address')
        ->assertFailed();
});

it('sends a message and reports success', function (): void {
    // Mail::raw() does not register as a "mailable" in MailFake, so assert on
    // the command outcome rather than the mailable count.
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'ops@example.test'])
        ->expectsOutputToContain('Sent.')
        ->assertSuccessful();
});

it('warns when not using SMTP', function (): void {
    // In production the mailer must be `smtp`. Quietly using `log` would mean
    // nothing is ever delivered while the application looks healthy.
    config()->set('mail.default', 'log');
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'ops@example.test'])
        ->expectsOutputToContain('Not using SMTP')
        ->assertSuccessful();
});

it('never prints the configured password', function (): void {
    config()->set('mail.mailers.smtp.username', 'smtp-user');
    config()->set('mail.mailers.smtp.password', 'super-secret-smtp-password');
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'ops@example.test'])
        ->doesntExpectOutputToContain('super-secret-smtp-password')
        ->assertSuccessful();
});
