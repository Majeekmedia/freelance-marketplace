<?php

declare(strict_types=1);

/**
 * HTTP verification of admin payment method routes.
 *
 * Uses a real session and CSRF token so the result reflects what a browser
 * would experience, including the middleware and the `admin` gate — not just
 * the controller in isolation.
 *
 * Usage: php tools/verify-payment-methods.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$base = 'http://127.0.0.1:8199';
$jar = tempnam(sys_get_temp_dir(), 'cookies');

function curl(string $url, array $opts = []): array
{
    global $jar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
    ] + $opts);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = curl_getinfo($ch);
    curl_close($ch);

    return [$status, (string) $body, $headers];
}

function csrf(string $html): ?string
{
    return preg_match('/name="_token" value="([^"]+)"/', $html, $m) ? $m[1] : null;
}

// ---------------------------------------------------------------- log in

$admin = User::where('email', 'admin@trust-prime.test')->first()
    ?? User::factory()->admin()->create(['email' => 'admin@trust-prime.test']);
$admin->password = Hash::make('DemoPass123!');
$admin->save();

[, $loginHtml] = curl($base.'/login');
$token = csrf($loginHtml);
echo "login token: ".($token ? 'ok' : 'MISSING')."\n";

[$status] = curl($base.'/login', [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'email' => 'admin@trust-prime.test',
        'password' => 'DemoPass123!',
    ]),
]);
echo "POST /login → {$status} (expect 302)\n";

// ------------------------------------------------------ payment methods page

[$status, $html] = curl($base.'/admin/payment-methods');
echo "GET /admin/payment-methods → {$status} (expect 200)\n";
$pageToken = csrf($html);
echo "page token: ".($pageToken ? 'ok' : 'MISSING')."\n";

// ------------------------------------------------------------- the toggle

$method = PaymentMethod::first();

if ($method === null) {
    echo "No payment method to toggle.\n";
    exit(0);
}

$before = $method->is_enabled;
echo "\nmethod #{$method->id} '{$method->label}' enabled={$before}\n";

[$status] = curl($base."/admin/payment-methods/{$method->id}/toggle", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $pageToken]),
]);
echo "POST toggle → {$status} (expect 302)\n";

$after = $method->fresh()->is_enabled;
echo "after toggle: enabled=".var_export($after, true)
    .' (expect '.var_export(! $before, true).")\n";
echo "enabled scope count: ".PaymentMethod::enabled()->count()."\n";

// Toggle back so the demo data is unchanged.
[, $html2] = curl($base.'/admin/payment-methods');
$token2 = csrf($html2);
curl($base."/admin/payment-methods/{$method->id}/toggle", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $token2]),
]);
echo "restored: enabled=".var_export($method->fresh()->is_enabled, true)."\n";

// -------------------------------------------------- GET to a POST route is refused

[$status] = curl($base."/admin/payment-methods/{$method->id}/toggle");
echo "\nGET toggle (should be refused) → {$status} (expect 405)\n";

@unlink($jar);
