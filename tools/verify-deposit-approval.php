<?php

declare(strict_types=1);

/**
 * HTTP verification of deposit approval (FR-040).
 *
 * Proves, with a real session and CSRF, that:
 *  - a pending deposit can be approved once and only once
 *  - a second attempt is refused and creates no second credit
 *  - the balance rises by exactly the credited amount
 *  - exactly one audit row exists per decision
 *
 * Usage: php tools/verify-deposit-approval.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AuditLog;
use App\Models\DepositRequest;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Hash;

$base = 'http://127.0.0.1:8199';
$jar = tempnam(sys_get_temp_dir(), 'cookies');

function req(string $url, array $opts = []): array
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
    $body = (string) curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $body];
}

function csrf(string $html): ?string
{
    return preg_match('/name="_token" value="([^"]+)"/', $html, $m) ? $m[1] : null;
}

// ------------------------------------------------------------------ log in

$admin = User::where('email', 'admin@trust-prime.test')->first();
$admin->password = Hash::make('DemoPass123!');
$admin->save();

[, $loginHtml] = req($base.'/login');
[$loginStatus] = req($base.'/login', [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => csrf($loginHtml),
        'email' => 'admin@trust-prime.test',
        'password' => 'DemoPass123!',
    ]),
]);
echo "login → {$loginStatus}\n\n";

// --------------------------------------------------------- create a fixture

$poster = User::where('email', 'recruiter2@trust-prime.test')->first();
if ($poster === null) {
    $poster = User::factory()->approvedPoster()->create([
        'email' => 'recruiter2@trust-prime.test',
        'name' => 'Second Poster',
    ]);
}

$deposit = DepositRequest::factory()->forUser($poster)->pending()->create([
    'amount_requested_cents' => 40000,
    'transfer_reference' => 'VERIFY'.time(),
]);

$ledger = app(LedgerService::class);
$balanceBefore = $ledger->available($poster)->minorUnits;
$ledgerBefore = $poster->ledgerEntries()->count();

echo "deposit #{$deposit->id}: requested 400.00, poster balance ".($balanceBefore / 100)."\n";

// ------------------------------------------------------------- approve once

[, $showHtml] = req($base."/admin/deposits/{$deposit->id}");
$token = csrf($showHtml);

[$status1] = req($base."/admin/deposits/{$deposit->id}/approve", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'credited_amount' => '400.00',
        'reason' => 'Verified against the on-chain hash via HTTP tool',
    ]),
]);
echo "POST approve #1 → {$status1} (expect 302)\n";

$deposit->refresh();
$balanceAfter1 = $ledger->available($poster)->minorUnits;
$ledgerAfter1 = $poster->ledgerEntries()->count();

echo '  status='.$deposit->status->value.' credited='.$deposit->credited_amount_cents."\n";
echo '  balance change: '.$balanceBefore.' → '.$balanceAfter1.' (+'.($balanceAfter1 - $balanceBefore).")\n";
echo '  ledger rows: '.$ledgerBefore.' → '.$ledgerAfter1."\n";

// ----------------------------------------------------- approve a second time

[$status2] = req($base."/admin/deposits/{$deposit->id}/approve", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'credited_amount' => '400.00',
        'reason' => 'Second attempt must be refused',
    ]),
]);
echo "\nPOST approve #2 → {$status2} (expect 302 back with an error, not a 500)\n";

$deposit->refresh();
$balanceAfter2 = $ledger->available($poster)->minorUnits;
$ledgerAfter2 = $poster->ledgerEntries()->count();

echo '  balance: '.$balanceAfter1.' → '.$balanceAfter2.' (must be unchanged)'."\n";
echo '  ledger rows: '.$ledgerAfter1.' → '.$ledgerAfter2.' (must be unchanged)'."\n";

// ------------------------------------------------------------------ audit

$audits = AuditLog::where('target_type', 'deposit_request')->where('target_id', $deposit->id)->get();
echo "\naudit rows for this deposit: ".$audits->count()." (expect 1)\n";
foreach ($audits as $a) {
    echo "  {$a->action} actor={$a->actor_id} amount={$a->amount_cents} reason=\"{$a->reason}\"\n";
}

// -------------------------------------------------------------- the verdict

$pass = $status1 === 302
    && $status2 !== 500
    && $balanceAfter2 === $balanceAfter1
    && $ledgerAfter2 === $ledgerAfter1
    && $audits->count() === 1
    && ($balanceAfter1 - $balanceBefore) === 40000;

echo "\n".($pass ? 'ALL CHECKS PASSED' : 'CHECKS FAILED')."\n";

@unlink($jar);
