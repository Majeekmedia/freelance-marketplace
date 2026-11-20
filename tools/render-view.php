<?php

declare(strict_types=1);

/**
 * Render a view in isolation and print the exception message.
 *
 * The browser shows a generic error page and the log tail is mostly stack
 * frames; this surfaces the actual message and the line in the view that threw.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$jobId = (int) ($argv[1] ?? 1);

try {
    $job = App\Models\Job::findOrFail($jobId);
    $job->load(['category', 'poster', 'milestones', 'escrow']);

    $html = view('jobs.public.show', ['job' => $job])->render();

    echo 'OK: rendered ' . strlen($html) . " bytes\n";
} catch (Throwable $e) {
    echo get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo 'at ' . $e->getFile() . ':' . $e->getLine() . "\n";

    foreach (array_slice($e->getTrace(), 0, 6) as $i => $frame) {
        $where = ($frame['file'] ?? '?') . ':' . ($frame['line'] ?? '?');
        echo "  #{$i} {$where} " . ($frame['function'] ?? '') . "\n";
    }
}
