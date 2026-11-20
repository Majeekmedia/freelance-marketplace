<?php

declare(strict_types=1);

/**
 * Print the compiled PHP for a Blade view so a syntax error can be located.
 *
 * `php -l` on the compiled file gives the exact line, which the browser error
 * page does not.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$view = $argv[1] ?? 'jobs.public.show';
$out = $argv[2] ?? null;

$compiler = app('blade.compiler');
$source = resource_path('views/'.str_replace('.', '/', $view).'.blade.php');

$php = $compiler->compileString(file_get_contents($source));

$target = $out ?? storage_path('framework/views/_debug_compiled.php');
file_put_contents($target, $php);

echo "compiled to: {$target}\n";
echo 'lines: '.substr_count($php, "\n")."\n";

// Balance check on the generated PHP.
$ifs = preg_match_all('/\bif\s*\(/', $php);
$endifs = preg_match_all('/\bendif;/', $php);
echo "if( = {$ifs}   endif; = {$endifs}\n";
