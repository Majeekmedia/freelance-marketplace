<?php

declare(strict_types=1);

use App\Support\LogoUploader;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Brand is data, not copy (FR-035)
|--------------------------------------------------------------------------
| The failure this guards against: the name hardcoded in one template and stale
| in another. Everything reads from SiteSettings; nothing reads a literal.
*/

beforeEach(function (): void {
    SiteSettings::flush();
    Storage::fake(LogoUploader::DISK);
});

it('falls back to sensible defaults when nothing is configured', function (): void {
    expect(SiteSettings::brandName())->toBe('Trust-Prime')
        ->and(SiteSettings::tagline())->toBe('Escrow-backed freelance work')
        ->and(SiteSettings::hasLogo())->toBeFalse();
});

it('persists and returns a changed brand name', function (): void {
    SiteSettings::set('brand_name', 'Acme Marketplace');

    expect(SiteSettings::brandName())->toBe('Acme Marketplace');
});

it('survives a fresh read from the database', function (): void {
    SiteSettings::set('brand_name', 'Acme Marketplace');

    // Drop the per-request cache, exactly as a new request would.
    SiteSettings::flush();

    expect(SiteSettings::brandName())->toBe('Acme Marketplace');
});

it('refuses an unknown setting key', function (): void {
    // The set of settings is deliberate; arbitrary keys would make this an
    // untyped key/value store again.
    expect(fn () => SiteSettings::set('made_up_key', 'x'))
        ->toThrow(InvalidArgumentException::class);
});

it('derives initials from the brand name for the wordmark fallback', function (): void {
    expect(SiteSettings::initials())->toBe('TP');

    SiteSettings::set('brand_name', 'Acme');
    expect(SiteSettings::initials())->toBe('A');

    SiteSettings::set('brand_name', 'North Wind Labs');
    expect(SiteSettings::initials())->toBe('NW');
});

/*
|--------------------------------------------------------------------------
| Logo upload
|--------------------------------------------------------------------------
*/

it('stores an uploaded logo and points the setting at it', function (): void {
    $file = UploadedFile::fake()->image('logo.png', 400, 120);

    $path = LogoUploader::store($file);

    expect($path)->toStartWith('brand/')
        ->and(SiteSettings::hasLogo())->toBeTrue()
        ->and(SiteSettings::logoPath())->toBe($path);

    Storage::disk(LogoUploader::DISK)->assertExists($path);
});

it('randomises the stored filename instead of trusting the client', function (): void {
    $file = UploadedFile::fake()->image('../../evil-logo.png', 200, 100);

    $path = LogoUploader::store($file);

    expect($path)->not->toContain('evil')
        ->and($path)->not->toContain('..');
});

it('deletes the previous logo when a new one is uploaded', function (): void {
    $first = LogoUploader::store(UploadedFile::fake()->image('one.png', 200, 80));

    $second = LogoUploader::store(UploadedFile::fake()->image('two.png', 200, 80));

    Storage::disk(LogoUploader::DISK)->assertMissing($first);
    Storage::disk(LogoUploader::DISK)->assertExists($second);
    expect(SiteSettings::logoPath())->toBe($second);
});

it('restores the text wordmark when the logo is removed', function (): void {
    $path = LogoUploader::store(UploadedFile::fake()->image('logo.png', 200, 80));

    LogoUploader::remove();

    Storage::disk(LogoUploader::DISK)->assertMissing($path);
    expect(SiteSettings::hasLogo())->toBeFalse()
        ->and(LogoUploader::url())->toBeNull();
});

it('rejects an SVG logo', function (): void {
    // An SVG can carry <script> and would be served from our own origin — a
    // stored-XSS vector on every page that renders the logo.
    $svg = UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    );

    expect(fn () => LogoUploader::store($svg))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a non-image file', function (): void {
    $pdf = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    expect(fn () => LogoUploader::store($pdf))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an oversized image', function (): void {
    $huge = UploadedFile::fake()->image('huge.png')->size(LogoUploader::MAX_KILOBYTES + 1);

    expect(fn () => LogoUploader::store($huge))
        ->toThrow(InvalidArgumentException::class);
});

it('exposes validation rules that match the enforced limits', function (): void {
    $rules = implode('|', LogoUploader::rules());

    expect($rules)->toContain('max:'.LogoUploader::MAX_KILOBYTES)
        ->and($rules)->toContain('mimes:png,jpg,jpeg,webp')
        ->and($rules)->not->toContain('svg');
});

it('provides a public url only when a logo exists', function (): void {
    expect(LogoUploader::url())->toBeNull();

    LogoUploader::store(UploadedFile::fake()->image('logo.png', 200, 80));

    expect(LogoUploader::url())->not->toBeNull();
});
