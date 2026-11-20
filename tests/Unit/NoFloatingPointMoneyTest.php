<?php

declare(strict_types=1);

/**
 * Guard: no money may ever be stored or computed as floating point.
 *
 * Requirements FR-030 and NFR-003. This is a static check rather than a runtime
 * one because a float bug is silent — it produces a wrong number, not an error.
 * By the time a runtime test catches it, a balance is already wrong.
 *
 * If this test fails, do not "fix the test". Find the float and convert it to
 * integer minor units via App\Domain\Money.
 */
it('contains no floating point money anywhere in app/', function (): void {
    $offenders = [];

    $appPath = dirname(__DIR__, 2).'/app';

    if (! is_dir($appPath)) {
        expect(true)->toBeTrue();

        return;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        // Money.php legitimately mentions float/double in comments explaining
        // why they are banned. Only code is scanned, and comments are stripped.
        $code = strip_php_comments($file->getPathname());

        // Any float cast, float declaration, or float() call.
        foreach (['(float)', '(double)', 'floatval(', 'doubleval('] as $needle) {
            if (str_contains($code, $needle)) {
                $offenders[] = $file->getPathname().' → '.$needle;
            }
        }
    }

    expect($offenders)->toBe([],
        "Floating point money found:\n".implode("\n", $offenders));
});

it('has no balance column in any migration', function (): void {
    $migrationPath = dirname(__DIR__, 2).'/database/migrations';

    if (! is_dir($migrationPath)) {
        expect(true)->toBeTrue(); // no migrations yet — nothing to violate

        return;
    }

    $offenders = [];

    foreach (glob($migrationPath.'/*.php') ?: [] as $migration) {
        $code = strip_php_comments($migration);

        // A column literally named `balance` is the pattern this project forbids.
        if (preg_match('/[\'"`]balance[\'"`]/', $code)) {
            $offenders[] = basename($migration);
        }
    }

    expect($offenders)->toBe([],
        "A `balance` column was found. Balances are derived from ledger rows (FR-041):\n"
        .implode("\n", $offenders));
});

/**
 * Remove PHP comments so a comment explaining a rule is not mistaken for a
 * violation of it.
 */
function strip_php_comments(string $path): string
{
    $code = file_get_contents($path) ?: '';

    $tokens = token_get_all($code);
    $out = '';

    foreach ($tokens as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $out .= $token[1];

            continue;
        }
        $out .= $token;
    }

    return $out;
}
