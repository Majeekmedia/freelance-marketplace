<?php

declare(strict_types=1);

use App\Domain\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Creating methods per type (FR-038a)
|--------------------------------------------------------------------------
*/

it('creates a bank transfer method', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'GTBank — Trust-Prime',
            'type' => 'bank_transfer',
            'bank_name' => 'GTBank',
            'account_name' => 'Trust-Prime Ltd',
            'account_number' => '0123456789',
            'routing_detail' => 'SWIFT GTBINGLA',
            'is_enabled' => '1',
        ])
        ->assertRedirect(route('admin.payment-methods.index'));

    $method = PaymentMethod::first();

    expect($method->type)->toBe(PaymentMethodType::BANK_TRANSFER)
        ->and($method->is_enabled)->toBeTrue()
        ->and($method->isComplete())->toBeTrue()
        // Bank methods must not retain crypto fields.
        ->and($method->crypto_address)->toBeNull();
});

it('creates multiple crypto methods on different networks', function (): void {
    $admin = User::factory()->admin()->create();

    $payloads = [
        ['USDT', 'TRC-20', 'TXyz1234567890abcdef'],
        ['USDT', 'ERC-20', '0xabcdef1234567890abcd'],
        ['BTC', 'Bitcoin', 'bc1qxyz1234567890abcd'],
    ];

    foreach ($payloads as [$asset, $network, $address]) {
        $this->actingAs($admin)
            ->post('/admin/payment-methods', [
                'label' => "{$asset} — {$network}",
                'type' => 'crypto',
                'crypto_asset' => $asset,
                'crypto_network' => $network,
                'crypto_address' => $address,
                'is_enabled' => '1',
            ])
            ->assertRedirect();
    }

    expect(PaymentMethod::count())->toBe(3)
        ->and(PaymentMethod::where('crypto_network', 'TRC-20')->exists())->toBeTrue()
        ->and(PaymentMethod::where('crypto_network', 'ERC-20')->exists())->toBeTrue()
        // Crypto methods must not retain bank fields.
        ->and(PaymentMethod::whereNotNull('bank_name')->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Type-specific required fields (FR-038d)
|--------------------------------------------------------------------------
*/

it('rejects a crypto method with no network', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'Broken USDT',
            'type' => 'crypto',
            'crypto_asset' => 'USDT',
            'crypto_network' => '',
            'crypto_address' => 'TXyz1234567890abcdef',
        ])
        ->assertSessionHasErrors('crypto_network');

    expect(PaymentMethod::count())->toBe(0);
});

it('rejects a crypto method with no address', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'No address',
            'type' => 'crypto',
            'crypto_asset' => 'USDT',
            'crypto_network' => 'TRC-20',
            'crypto_address' => '',
        ])
        ->assertSessionHasErrors('crypto_address');

    expect(PaymentMethod::count())->toBe(0);
});

it('rejects a bank method with no account number', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'No account',
            'type' => 'bank_transfer',
            'bank_name' => 'GTBank',
            'account_name' => 'Trust-Prime Ltd',
            'account_number' => '',
        ])
        ->assertSessionHasErrors('account_number');

    expect(PaymentMethod::count())->toBe(0);
});

it('rejects a wallet address containing quotes or whitespace', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'Injection attempt',
            'type' => 'crypto',
            'crypto_asset' => 'USDT',
            'crypto_network' => 'TRC-20',
            'crypto_address' => 'TXyz" onmouseover="alert(1)',
        ])
        ->assertSessionHasErrors('crypto_address');

    expect(PaymentMethod::count())->toBe(0);
});

it('names the missing field in the error message', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/payment-methods', [
            'label' => 'No network here',
            'type' => 'crypto',
            'crypto_asset' => 'USDT',
            'crypto_network' => '',
            'crypto_address' => 'TXyz1234567890abcdef',
        ]);

    $message = session('errors')->get('crypto_network')[0];

    expect($message)->toContain('network');
});

/*
|--------------------------------------------------------------------------
| Enable / disable (FR-038b, FR-038c)
|--------------------------------------------------------------------------
*/

it('disables a method and hides it from the enabled scope', function (): void {
    $admin = User::factory()->admin()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($admin)
        ->post("/admin/payment-methods/{$method->id}/toggle")
        ->assertRedirect();

    expect($method->fresh()->is_enabled)->toBeFalse()
        ->and(PaymentMethod::enabled()->count())->toBe(0);
});

it('re-enables a disabled method', function (): void {
    $admin = User::factory()->admin()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => false]);

    $this->actingAs($admin)
        ->post("/admin/payment-methods/{$method->id}/toggle")
        ->assertRedirect();

    expect($method->fresh()->is_enabled)->toBeTrue()
        ->and(PaymentMethod::enabled()->count())->toBe(1);
});

it('orders enabled methods by the admin sort order', function (): void {
    $first = PaymentMethod::factory()->crypto()->create(['sort_order' => 5, 'is_enabled' => true, 'label' => 'Second']);
    $second = PaymentMethod::factory()->crypto()->create(['sort_order' => 1, 'is_enabled' => true, 'label' => 'First']);

    $ordered = PaymentMethod::enabled()->pluck('label')->all();

    expect($ordered)->toBe(['First', 'Second']);
});

/*
|--------------------------------------------------------------------------
| Deletion preserves history (FR-038e)
|--------------------------------------------------------------------------
*/

it('soft deletes a method so history survives', function (): void {
    $admin = User::factory()->admin()->create();
    $method = PaymentMethod::factory()->crypto()->create();

    $this->actingAs($admin)
        ->delete("/admin/payment-methods/{$method->id}")
        ->assertRedirect();

    // Gone from normal queries...
    expect(PaymentMethod::find($method->id))->toBeNull()
        // ...but the row and its details still exist for history.
        ->and(PaymentMethod::withTrashed()->find($method->id))->not->toBeNull()
        ->and(PaymentMethod::withTrashed()->find($method->id)->crypto_address)->toBe($method->crypto_address);
});

it('never shows a soft-deleted method to posters', function (): void {
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);
    $method->delete();

    expect(PaymentMethod::enabled()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

it('blocks a poster from managing payment methods', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)->get('/admin/payment-methods')->assertNotFound();
    $this->actingAs($poster)->post('/admin/payment-methods', [])->assertNotFound();
});

it('blocks a guest from managing payment methods', function (): void {
    $this->get('/admin/payment-methods')->assertRedirect('/login');
});

it('blocks a freelancer from toggling a method', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($freelancer)
        ->post("/admin/payment-methods/{$method->id}/toggle")
        ->assertNotFound();

    expect($method->fresh()->is_enabled)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Snapshot (FR-039d)
|--------------------------------------------------------------------------
| Editing a method must not rewrite what a poster was already told to pay.
| The snapshot is the mechanism, so it is tested here where it is produced.
*/

it('captures a bank snapshot of the details as shown', function (): void {
    $method = PaymentMethod::factory()->bank()->create();

    $snapshot = $method->snapshot();

    expect($snapshot)
        ->toMatchArray([
            'method_id' => $method->id,
            'type' => 'bank_transfer',
            'label' => $method->label,
        ])
        ->toHaveKeys(['bank_name', 'account_name', 'account_number', 'routing_detail'])
        ->not->toHaveKey('crypto_address');
});

it('captures a crypto snapshot without bank fields', function (): void {
    $method = PaymentMethod::factory()->crypto()->create();

    $snapshot = $method->snapshot();

    expect($snapshot)
        ->toHaveKeys(['crypto_asset', 'crypto_network', 'crypto_address'])
        ->not->toHaveKey('bank_name');
});

it('a snapshot is unaffected by a later edit to the method', function (): void {
    $method = PaymentMethod::factory()->crypto()->create([
        'crypto_address' => 'TXyz1234567890abcdef',
    ]);

    $frozen = $method->snapshot();

    $method->update(['crypto_address' => '0xNEWADDRESS123456789']);

    expect($frozen['crypto_address'])->toBe('TXyz1234567890abcdef')
        ->and($method->fresh()->crypto_address)->toBe('0xNEWADDRESS123456789');
});

/*
|--------------------------------------------------------------------------
| Type switching
|--------------------------------------------------------------------------
*/

it('clears irrelevant fields when a method changes type', function (): void {
    $admin = User::factory()->admin()->create();

    $method = PaymentMethod::factory()->bank()->create([
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
    ]);

    // Switch it to crypto.
    $this->actingAs($admin)
        ->put("/admin/payment-methods/{$method->id}", [
            'label' => 'Now crypto',
            'type' => 'crypto',
            'crypto_asset' => 'USDT',
            'crypto_network' => 'TRC-20',
            'crypto_address' => 'TXyz1234567890abcdef',
        ])
        ->assertRedirect();

    $method->refresh();

    expect($method->type)->toBe(PaymentMethodType::CRYPTO)
        ->and($method->crypto_address)->toBe('TXyz1234567890abcdef')
        // The stale bank details must be gone, or a poster sees two sets of
        // contradictory instructions.
        ->and($method->bank_name)->toBeNull()
        ->and($method->account_number)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Reachability
|--------------------------------------------------------------------------
*/

it('lists payment methods for an admin', function (): void {
    $admin = User::factory()->admin()->create();
    PaymentMethod::factory()->crypto()->create(['label' => 'USDT — TRC-20']);

    $this->actingAs($admin)
        ->get('/admin/payment-methods')
        ->assertOk()
        ->assertSee('USDT — TRC-20');
});

it('warns when a method is incomplete rather than hiding it', function (): void {
    $admin = User::factory()->admin()->create();

    // Bypass validation to simulate a row that predates a rule change.
    PaymentMethod::factory()->crypto()->create([
        'label' => 'Legacy method',
        'crypto_network' => null,
    ]);

    $this->actingAs($admin)
        ->get('/admin/payment-methods')
        ->assertOk()
        ->assertSee('Legacy method')
        ->assertSee('Incomplete');
});
