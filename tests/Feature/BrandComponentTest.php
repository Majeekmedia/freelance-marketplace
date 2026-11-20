<?php

declare(strict_types=1);

use App\Support\LogoUploader;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| The brand component renders what the settings say (AC-020)
|--------------------------------------------------------------------------
| A runtime counterpart to the static scan: prove the rendered output actually
| changes when the setting changes, in both the wordmark and logo states.
*/

beforeEach(function (): void {
    SiteSettings::flush();
    Storage::fake(LogoUploader::DISK);
});

it('renders the configured brand name as a wordmark when no logo exists', function (): void {
    SiteSettings::set('brand_name', 'Acme Marketplace');

    $html = $this->blade('<x-brand />');

    $html->assertSee('Acme Marketplace')
        ->assertSee('AM')            // initials
        ->assertDontSee('<img');     // no logo image
});

it('renders the uploaded logo instead of the wordmark', function (): void {
    SiteSettings::set('brand_name', 'Acme Marketplace');
    LogoUploader::store(UploadedFile::fake()->image('logo.png', 300, 80));

    $html = $this->blade('<x-brand />');

    $html->assertSee('<img', false)
        ->assertSee('alt="Acme Marketplace"', false);
});

it('changes everywhere when the name changes', function (): void {
    SiteSettings::set('brand_name', 'First Name');
    $before = (string) $this->blade('<x-brand />');

    SiteSettings::set('brand_name', 'Second Name');
    $after = (string) $this->blade('<x-brand />');

    expect($before)->toContain('First Name')
        ->and($before)->not->toContain('Second Name')
        ->and($after)->toContain('Second Name')
        ->and($after)->not->toContain('First Name');
});

it('falls back to the wordmark after the logo is removed', function (): void {
    LogoUploader::store(UploadedFile::fake()->image('logo.png', 200, 60));

    $withLogo = (string) $this->blade('<x-brand />');
    expect($withLogo)->toContain('<img');

    LogoUploader::remove();

    $withoutLogo = (string) $this->blade('<x-brand />');
    expect($withoutLogo)->not->toContain('<img')
        ->and($withoutLogo)->toContain(SiteSettings::initials());
});
