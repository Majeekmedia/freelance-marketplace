<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * Every Blade view must compile to valid PHP.
 *
 * Why this exists
 * ---------------
 * A view with `@php($x = $y)` on its own line compiled to:
 *
 *     <?php($x = $y)
 *
 * with no closing `?>` and no semicolon. The rest of the template was swallowed
 * into one broken PHP expression and the page returned a 500 — while the test
 * suite stayed green, because no test rendered that view.
 *
 * Blade does not report this at build time, and `php -l` only helps once you
 * know which compiled file to lint. A test that compiles every view and lints
 * the result turns a runtime 500 into a build failure.
 */
it('compiles every blade view to valid PHP', function (): void {
    $views = collect(File::allFiles(resource_path('views')))
        ->filter(fn ($f): bool => str_ends_with($f->getFilename(), '.blade.php'));

    expect($views)->not->toBeEmpty('No Blade views found — check the path.');

    $compiler = app('blade.compiler');
    $broken = [];

    foreach ($views as $file) {
        $relative = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
        $php = $compiler->compileString(File::get($file->getPathname()));

        // Lint the generated PHP without writing to the view cache.
        $tmp = tempnam(sys_get_temp_dir(), 'blade_').'.php';
        file_put_contents($tmp, $php);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp).' 2>&1', $output, $status);

        if ($status !== 0) {
            $message = implode(' ', array_slice($output, 0, 3));
            $broken[] = $relative.' → '.trim($message);
        }

        @unlink($tmp);
        $output = [];
    }

    expect($broken)->toBe([], "These views compile to invalid PHP:\n".implode("\n", $broken));
});

it('has no inline @php(...) directive that swallows the rest of a file', function (): void {
    // The inline form is the specific construct that broke. It is legal in
    // Blade but easy to misplace on its own line, so it is banned in favour of
    // the block form, which fails loudly instead.
    $offenders = collect(File::allFiles(resource_path('views')))
        ->filter(fn ($f): bool => str_ends_with($f->getFilename(), '.blade.php'))
        ->filter(fn ($f): bool => preg_match('/^\s*@php\s*\(/m', File::get($f->getPathname())) === 1)
        ->map(fn ($f): string => str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $f->getPathname()))
        ->values()
        ->all();

    expect($offenders)->toBe([], 'Use @php ... @endphp instead of @php(...): '.implode(', ', $offenders));
});

it('renders the public job detail page with a well-formed milestone table', function (): void {
    // The milestone table once declared three headers but emitted two cells per
    // row, so every amount sat under the wrong column.
    // The milestone amounts must actually sum to the budget, otherwise the
    // total row legitimately reads "does not match the budget" and this
    // assertion would be testing the wrong branch.
    $job = \App\Models\Job::factory()->funded()->create(['budget_cents' => 250000]);
    $job->milestones()->createMany([
        ['sequence' => 1, 'title' => 'Discovery', 'amount_cents' => 60000],
        ['sequence' => 2, 'title' => 'Build', 'amount_cents' => 140000],
        ['sequence' => 3, 'title' => 'Handover', 'amount_cents' => 50000],
    ]);

    $html = $this->get("/jobs/{$job->id}")->assertOk()->getContent();

    // Anchor on the element, not the class name: `ms-table` also appears in the
    // stylesheet pushed into <head>, which would match first.
    $tableStart = strpos($html, '<table class="responsive ms-table"');
    expect($tableStart)->not->toBeFalse();

    $tableHtml = substr($html, $tableStart);
    $tableHtml = substr($tableHtml, 0, strpos($tableHtml, '</table>'));

    $headers = preg_match_all('/<th[\s>]/', $tableHtml);
    $rows = preg_match_all('/<tr[\s>]/', $tableHtml);
    $cells = preg_match_all('/<td[\s>]/', $tableHtml);

    // 2 header columns; header row + 3 body rows + 1 total row = 5 rows;
    // 4 data rows × 2 cells = 8 cells.
    expect($headers)->toBe(2)
        ->and($rows)->toBe(5)
        ->and($cells)->toBe(8)
        ->and($tableHtml)->toContain('matches the escrowed budget');
});
