<?php

declare(strict_types=1);

/**
 * Repair cp1252 mojibake in evidence files.
 *
 * Several evidence files were written on Windows and picked up cp1252 byte
 * sequences where UTF-8 punctuation was intended: "â€”" instead of "—",
 * "Â·" instead of "·". It is cosmetic until someone greps an evidence file for a
 * character that is not actually there.
 *
 * Run: php tools/fix-evidence-encoding.php
 */

$dir = __DIR__ . '/../.aidev/evidence';

$map = [
    'â€”' => '—',
    'â€“' => '–',
    'â€™' => '’',
    'â€œ' => '“',
    'â€\x9d' => '”',
    'â€¦' => '…',
    'Â·' => '·',
    'Â©' => '©',
    'Ã©' => 'é',
    'Ã¨' => 'è',
    'Ã¡' => 'á',
];

$fixed = 0;
$files = glob($dir.'/*.md') ?: [];

foreach ($files as $file) {
    $original = file_get_contents($file);

    if ($original === false) {
        continue;
    }

    $cleaned = str_replace(array_keys($map), array_values($map), $original);

    if ($cleaned !== $original) {
        file_put_contents($file, $cleaned);
        $fixed++;
        echo 'fixed: '.basename($file)."\n";
    }
}

echo "\n{$fixed} file(s) repaired out of ".count($files)."\n";

// Report anything still suspicious so the mapping can be extended.
$remaining = 0;

foreach ($files as $file) {
    $contents = (string) file_get_contents($file);

    if (preg_match_all('/â€|Â[\x{80}-\x{BF}]|Ã[\x{80}-\x{BF}]/u', $contents, $m)) {
        $remaining += count($m[0]);
        echo 'still suspicious: '.basename($file).' ('.count($m[0]).")\n";
    }
}

echo "remaining suspicious sequences: {$remaining}\n";
