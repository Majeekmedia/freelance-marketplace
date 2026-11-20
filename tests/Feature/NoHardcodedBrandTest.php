<?php

declare(strict_types=1);

use App\Support\SiteSettings;

/*
|--------------------------------------------------------------------------
| No template may hardcode the brand name (FR-035, AC-020)
|--------------------------------------------------------------------------
| This is the test that makes "editable everywhere" true. A single literal
| "Trust-Prime" in a Blade file means that surface ignores the admin setting,
| and the failure is invisible until someone renames the site.
|
| The check is a static scan of resources/views because a runtime test can only
| observe the pages that happen to render during the test run.
*/

it('contains no hardcoded brand name in any blade template', function (): void {
    $brandName = 'Trust-Prime';
    $viewsPath = dirname(__DIR__, 2).'/resources/views';

    if (! is_dir($viewsPath)) {
        expect(true)->toBeTrue();

        return;
    }

    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));

    foreach ($files as $file) {
        if (! in_array($file->getExtension(), ['php', 'blade'], true)) {
            continue;
        }

        $contents = file_get_contents($file->getPathname()) ?: '';

        // Strip blade comments: an explanatory comment is not a violation.
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;

        if (str_contains($code, $brandName)) {
            $offenders[] = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([],
        "Hardcoded brand name found. Use SiteSettings::brandName() instead:\n"
        .implode("\n", $offenders));
});

it('contains no hardcoded contact details in any blade template', function (): void {
    $viewsPath = dirname(__DIR__, 2).'/resources/views';

    if (! is_dir($viewsPath)) {
        expect(true)->toBeTrue();

        return;
    }

    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));

    foreach ($files as $file) {
        if (! in_array($file->getExtension(), ['php', 'blade'], true)) {
            continue;
        }

        $contents = file_get_contents($file->getPathname()) ?: '';
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;

        // A literal email address in a view is a support detail that will go stale.
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $code)) {
            $offenders[] = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([],
        "Hardcoded email found. Use SiteSettings::contactEmail() instead:\n"
        .implode("\n", $offenders));
});

/*
|--------------------------------------------------------------------------
| The settings surface is deliberate
|--------------------------------------------------------------------------
*/

it('exposes exactly the settings the application knows about', function (): void {
    // Adding a setting is a deliberate act. This test makes that visible in a
    // diff rather than letting keys accumulate.
    expect(array_keys(SiteSettings::DEFAULTS))->toBe([
        'brand_name',
        'brand_tagline',
        'brand_logo_path',
        'contact_email',
        'contact_phone',
        'support_hours',
    ]);
});
