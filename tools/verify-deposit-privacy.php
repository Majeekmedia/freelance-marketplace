<?php

declare(strict_types=1);

/**
 * HTTP verification of deposit request privacy (FR-039e).
 *
 * Proves with real sessions and CSRF that a proof image is readable by its owner
 * and by an admin, and by nobody else — not another poster, not a freelancer.
 *
 * Usage: php tools/verify-deposit-privacy.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$base = 'http://127.0.0.1:8199';

/** Log in and return a cookie jar for that identity. */
function sessionFor(string $email, string $password): string
{
    global $base;

    $jar = tempnam(sys_get_temp_dir(), 'cookies');

    $get = function (string $url) use ($jar): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $jar,
            CURLOPT_COOKIEFILE => $jar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = (string) curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body];
    };

    [, $html] = $get($base.'/login');
    $token = preg_match('/name="_token" value="([^"]+)"/', $html, $m) ? $m[1] : null;

    $ch = curl_init($base.'/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $token,
            'email' => $email,
            'password' => $password,
        ]),
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    curl_close($ch);

    return $jar;
}

function fetch(string $jar, string $url): int
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status;
}

// ---------------------------------------------------------------- fixtures

$deposit = DepositRequest::with('user')->first();

if ($deposit === null) {
    echo "No deposit request exists. Submit one first.\n";
    exit(0);
}

$owner = $deposit->user;

$admin = User::where('email', 'admin@trust-prime.test')->first();
if ($admin === null) {
    $admin = User::factory()->admin()->create(['email' => 'admin@trust-prime.test']);
}
$admin->password = Hash::make('DemoPass123!');
$admin->save();

$otherPoster = User::where('email', 'recruiter2@trust-prime.test')->first()
    ?? User::factory()->approvedPoster()->create([
        'email' => 'recruiter2@trust-prime.test',
        'name' => 'Second Poster',
    ]);
$otherPoster->password = Hash::make('DemoPass123!');
$otherPoster->save();

$freelancer = User::where('email', 'freelancer@trust-prime.test')->first();
if ($freelancer !== null) {
    $freelancer->password = Hash::make('DemoPass123!');
    $freelancer->save();
}

echo "deposit #{$deposit->id} owned by {$owner->email}, status={$deposit->status->value}\n";
echo "proof_path={$deposit->proof_path}\n\n";

$proofUrl = $base."/wallet/deposits/{$deposit->id}/proof";
$showUrl = $base."/wallet/deposits/{$deposit->id}";

// ------------------------------------------------------------------- checks

$results = [];

// Anonymous
$anon = curl_init($proofUrl);
curl_setopt_array($anon, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
curl_exec($anon);
$results['anonymous proof'] = curl_getinfo($anon, CURLINFO_HTTP_CODE);
curl_close($anon);

// Owner
$ownerJar = sessionFor($owner->email, 'DemoPass123!');
$results['owner proof'] = fetch($ownerJar, $proofUrl);
$results['owner show'] = fetch($ownerJar, $showUrl);

// Admin
$adminJar = sessionFor('admin@trust-prime.test', 'DemoPass123!');
$results['admin proof'] = fetch($adminJar, $proofUrl);

// Another poster
$otherJar = sessionFor('recruiter2@trust-prime.test', 'DemoPass123!');
$results['other poster proof'] = fetch($otherJar, $proofUrl);
$results['other poster show'] = fetch($otherJar, $showUrl);

// A freelancer
if ($freelancer !== null) {
    $freeJar = sessionFor($freelancer->email, 'DemoPass123!');
    $results['freelancer proof'] = fetch($freeJar, $proofUrl);
}

// ---------------------------------------------------------------- expected

$expect = [
    'anonymous proof' => 302,       // bounced to login
    'owner proof' => 200,
    'owner show' => 200,
    'admin proof' => 200,
    'other poster proof' => 404,    // 404, not 403: no confirmation it exists
    'other poster show' => 404,
    'freelancer proof' => 404,
];

echo str_pad('case', 24).str_pad('got', 8).str_pad('expected', 10)."verdict\n";
echo str_repeat('-', 55)."\n";

$fail = 0;

foreach ($results as $case => $got) {
    $want = $expect[$case] ?? null;
    $ok = $want === null || $got === $want;
    if (! $ok) {
        $fail++;
    }
    echo str_pad($case, 24).str_pad((string) $got, 8).str_pad((string) $want, 10).($ok ? 'ok' : 'FAIL')."\n";
}

echo "\n".($fail === 0 ? 'All privacy checks passed.' : "{$fail} FAILED")."\n";
