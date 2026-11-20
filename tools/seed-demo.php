<?php

declare(strict_types=1);

/**
 * Local-only demo data for browser verification.
 *
 * Deliberately kept OUT of DatabaseSeeder: this creates a known-password
 * account, which must never be reachable from `php artisan db:seed` on a
 * production host. Run explicitly: php tools/seed-demo.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreelancerProfile;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
 * HARD GUARD.
 *
 * This script creates accounts that are already APPROVED and share one known
 * password. Run against a live host it would hand out a working freelancer and
 * poster login to anyone who reads this file. It must be impossible to reach
 * outside a local environment — a comment saying "local only" is not a control.
 */
$env = $app->environment();
if (! in_array($env, ['local', 'testing'], true)) {
    fwrite(STDERR, "Refusing to run: APP_ENV is '{$env}'. Demo accounts have a known password.\n");
    exit(1);
}

$accounts = [
    [
        'email' => 'freelancer@trust-prime.test',
        'name' => 'Avery Lindqvist',
        'type' => App\Domain\UserType::FREELANCER,
        'profile' => [
            'headline' => 'Product designer for data-heavy interfaces',
            'bio' => 'Eight years turning dense financial and operational data into screens people can actually read. I work in close loops with engineers rather than throwing specs over a wall.',
            'skills' => ['Product Design', 'Figma', 'Design Systems', 'Accessibility'],
            'hourly_rate_cents' => 6500,
            'location' => 'Lisbon, Portugal',
            'is_public' => true,
        ],
        'portfolio' => [
            ['title' => 'Settlement dashboard redesign', 'description' => 'Rebuilt a reconciliation view used by 400 operators daily. Task completion time dropped by a third.', 'external_url' => 'https://example.com/case-settlement'],
            ['title' => 'Design system for a logistics platform', 'description' => 'A 60-component library with documented accessibility states.', 'external_url' => 'https://example.com/case-logistics'],
        ],
    ],
    [
        'email' => 'recruiter@trust-prime.test',
        'name' => 'Jordan Okafor',
        'type' => App\Domain\UserType::POSTER,
        'profile' => null,
        'portfolio' => [],
    ],
];

foreach ($accounts as $spec) {
    $user = User::firstOrNew(['email' => $spec['email']]);
    $user->name = $spec['name'];
    $user->password = Hash::make('DemoPass123!');
    $user->type = $spec['type'];
    $user->status = App\Domain\UserStatus::APPROVED;
    $user->email_verified_at = now();
    $user->save();

    if ($spec['profile'] !== null) {
        // user_id is intentionally NOT mass-assignable (it is owned by the
        // relation), so the profile must be created through the relation.
        $profile = $user->freelancerProfile()->firstOrNew([]);
        $profile->fill($spec['profile'])->save();

        foreach ($spec['portfolio'] as $index => $item) {
            $existing = $user->portfolios()->where('title', $item['title'])->first();

            if ($existing === null) {
                $user->portfolios()->create($item + ['sort_order' => $index]);
            } else {
                $existing->fill($item + ['sort_order' => $index])->save();
            }
        }
    }

    echo "  {$spec['email']}  (id {$user->id}, {$spec['type']->value})\n";
}

echo "Password for all demo accounts: DemoPass123!\n";
echo "Public profile: /freelancers/" . User::where('email', 'freelancer@trust-prime.test')->value('id') . "\n";
