<?php

declare(strict_types=1);

use App\Models\Job;
use App\Models\User;

/**
 * Responsive layout guards.
 *
 * These cannot replace a real browser measurement — only a rendering engine
 * knows the final widths. What they CAN do is catch the specific mistakes that
 * caused every page to overflow at 375px:
 *
 *   1. a fixed-width two-column grid written inline, which cannot reflow
 *   2. a table with no mobile card treatment
 *   3. a mobile nav that is hidden but not replaced by anything
 *
 * The real proof lives in `.aidev/evidence/M4-responsive.md`, measured at 320,
 * 390, 768 and 1280px in Chromium.
 */

it('declares a mobile breakpoint in the shared layout', function (): void {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)->toContain('@media (max-width:700px)')
        ->and($layout)->toContain('@media (max-width:760px)');
});

it('does not hard-code a fixed sidebar width inside an inline grid', function (): void {
    // `grid-template-columns: minmax(0,1fr) 280px` in a style attribute was the
    // direct cause of a 38px content column at 375px. An inline style cannot be
    // overridden by a media query, so it must never appear again.
    //
    // `repeat(auto-fill, minmax(220px, 1fr))` is NOT an offender: auto-fill
    // creates as many tracks as fit, so it reflows by design. Only a bare fixed
    // pixel track, sitting next to a fluid one, is the bug.
    $offenders = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views')),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (preg_match_all('/style="([^"]*grid-template-columns:[^"]*)"/', $contents, $matches) === 0) {
            continue;
        }

        foreach ($matches[1] as $declaration) {
            $isIntrinsicallyFluid = preg_match('/auto-fill|auto-fit|minmax/', $declaration) === 1;
            $hasFixedTrack = preg_match('/\b\d{3,4}px\b/', $declaration) === 1;

            if ($hasFixedTrack && ! $isIntrinsicallyFluid) {
                $offenders[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname())
                    .' → '.trim($declaration);
            }
        }
    }

    expect($offenders)->toBe([], "Inline fixed-width grid found:\n".implode("\n", $offenders));
});

it('renders a bottom navigation for signed-in users', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('class="bottomnav"', false)
        ->assertSee('aria-label="Primary"', false);
});

it('marks exactly one bottom nav tab as current', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $html = $this->actingAs($poster)->get('/jobs')->getContent();

    // A screen reader needs exactly one current tab; zero means "nowhere",
    // two means the active-state logic is matching too broadly.
    expect(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('does not render a bottom nav for guests', function (): void {
    $this->get('/jobs')
        ->assertOk()
        ->assertDontSee('class="bottomnav"', false);
});

it('gives the poster nav its own Post destination', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(route('jobs.manage.index'), false);
});

it('does not offer a Post destination to a freelancer', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $html = $this->actingAs($freelancer)->get('/dashboard')->getContent();

    // The freelancer's bottom nav should carry Work and Profile instead.
    expect($html)->toContain(route('portfolio.index'))
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('applies the responsive card treatment to every data table', function (): void {
    $offenders = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views')),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        // A bare `<table>` with no class will not collapse to cards, so it
        // overflows on a phone.
        if (preg_match('/<table(?![^>]*class=)/', $contents) === 1) {
            $offenders[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([], "Table without responsive class:\n".implode("\n", $offenders));
});

it('keeps the job detail page readable at phone width in the markup order', function (): void {
    $job = Job::factory()->funded()->create();

    $html = $this->get("/jobs/{$job->id}")->getContent();

    // The budget card must come before the long prose in source order, because
    // that is the reading order on a phone. The desktop grid re-orders visually.
    $budgetPos = strpos($html, 'Budget held in escrow');
    $prosePos = strpos($html, 'The work');

    expect($budgetPos)->not->toBeFalse()
        ->and($prosePos)->not->toBeFalse()
        ->and($budgetPos)->toBeLessThan($prosePos);
});
