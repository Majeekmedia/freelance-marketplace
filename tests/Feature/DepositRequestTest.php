<?php

declare(strict_types=1);

use App\Domain\DepositStatus;
use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\LedgerService;
use App\Support\DepositProof;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake(DepositProof::DISK);
});

/*
|--------------------------------------------------------------------------
| Submitting a request credits nothing (FR-039a)
|--------------------------------------------------------------------------
| The single most important property of this feature. A self-reported transfer
| is not money; it becomes money only when an admin approves it.
*/

it('creates a pending request and leaves the balance at zero', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '250.00',
            'transfer_reference' => 'TXHASH1234567890',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertRedirect(route('wallet.deposits.index'));

    $deposit = DepositRequest::first();

    expect($deposit->status)->toBe(DepositStatus::PENDING)
        ->and($deposit->amount_requested_cents)->toBe(25000)
        ->and($deposit->credited_amount_cents)->toBeNull()
        ->and($deposit->user_id)->toBe($poster->id);

    // The decisive assertion: no money exists.
    expect(app(LedgerService::class)->available($poster)->toDecimal())->toBe('0.00')
        ->and($deposit->user->ledgerEntries()->count())->toBe(0);
});

it('accepts a human-formatted amount', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '$1,250.50',
            'transfer_reference' => 'REF999',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])
        ->assertRedirect();

    expect(DepositRequest::first()->amount_requested_cents)->toBe(125050);
});

/*
|--------------------------------------------------------------------------
| Disabled methods are refused (FR-039b)
|--------------------------------------------------------------------------
*/

it('rejects a disabled payment method', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => false]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertSessionHasErrors('payment_method_id');

    expect(DepositRequest::count())->toBe(0);
});

it('rejects a soft-deleted payment method', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);
    $method->delete();

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertSessionHasErrors('payment_method_id');

    expect(DepositRequest::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Proof validation (FR-039c)
|--------------------------------------------------------------------------
*/

it('rejects a non-image proof', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('proof');

    expect(DepositRequest::count())->toBe(0);
});

it('rejects an oversized proof', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    // One kilobyte over the limit.
    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('big.png')->size(DepositProof::MAX_KILOBYTES + 1),
        ])
        ->assertSessionHasErrors('proof');

    expect(DepositRequest::count())->toBe(0);
});

it('rejects a proof with no reference', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'ab',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertSessionHasErrors('transfer_reference');
});

/*
|--------------------------------------------------------------------------
| The snapshot is frozen (FR-039d)
|--------------------------------------------------------------------------
*/

it('snapshots the method details at submission time', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create([
        'is_enabled' => true,
        'crypto_address' => 'TXyzORIGINAL123456',
    ]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertRedirect();

    $deposit = DepositRequest::first();

    expect($deposit->method_snapshot)
        ->toMatchArray([
            'method_id' => $method->id,
            'label' => $method->label,
            'type' => 'crypto',
            'crypto_address' => 'TXyzORIGINAL123456',
            'crypto_network' => 'TRC-20',
        ]);
});

it('keeps the snapshot unchanged when the method is edited afterwards', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create([
        'is_enabled' => true,
        'crypto_address' => 'TXyzORIGINAL123456',
    ]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ]);

    $deposit = DepositRequest::first();

    // An admin changes the address afterwards.
    $method->update(['crypto_address' => '0xCHANGED999999']);

    expect($deposit->fresh()->method_snapshot['crypto_address'])->toBe('TXyzORIGINAL123456')
        ->and($method->fresh()->crypto_address)->toBe('0xCHANGED999999');
});

it('keeps the snapshot when the method is deleted', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();
    $label = $deposit->method_snapshot['label'];

    $method->delete();

    // The row keeps its own copy, so history still explains itself.
    expect($deposit->fresh()->method_snapshot['label'])->toBe($label);
});

/*
|--------------------------------------------------------------------------
| Proof privacy (FR-039e)
|--------------------------------------------------------------------------
*/

it('stores the proof on the private disk, not the public one', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    Storage::disk(DepositProof::DISK)->assertExists($deposit->proof_path);
    Storage::disk('public')->assertMissing($deposit->proof_path);
});

it('lets the owner read their own proof', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    $this->actingAs($poster)
        ->get("/wallet/deposits/{$deposit->id}/proof")
        ->assertOk();
});

it('lets an admin read a proof', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $admin = User::factory()->admin()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    $this->actingAs($admin)
        ->get("/wallet/deposits/{$deposit->id}/proof")
        ->assertOk();
});

it('blocks another poster from reading a proof', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $other = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($owner)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    $this->actingAs($other)
        ->get("/wallet/deposits/{$deposit->id}/proof")
        ->assertNotFound();

    $this->actingAs($other)
        ->get("/wallet/deposits/{$deposit->id}")
        ->assertNotFound();
});

it('blocks a freelancer from reading a proof', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $freelancer = User::factory()->approvedFreelancer()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    $this->actingAs($freelancer)
        ->get("/wallet/deposits/{$deposit->id}/proof")
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Access control on the form itself
|--------------------------------------------------------------------------
*/

it('blocks a freelancer from submitting a deposit', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($freelancer)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertForbidden();

    expect(DepositRequest::count())->toBe(0);
});

it('blocks a pending poster from submitting a deposit', function (): void {
    $poster = User::factory()->pending()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->post('/wallet/deposits', [
            'payment_method_id' => $method->id,
            'amount' => '100.00',
            'transfer_reference' => 'REF123',
            'proof' => UploadedFile::fake()->image('proof.png'),
        ])
        ->assertForbidden();

    expect(DepositRequest::count())->toBe(0);
});

it('cannot set its own status or credited amount', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    // A poster posting status=approved must be ignored entirely.
    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
        'status' => 'approved',
        'credited_amount_cents' => 999999,
        'user_id' => User::factory()->admin()->create()->id,
    ]);

    $deposit = DepositRequest::first();

    expect($deposit->status)->toBe(DepositStatus::PENDING)
        ->and($deposit->credited_amount_cents)->toBeNull()
        ->and($deposit->user_id)->toBe($poster->id);

    expect(app(LedgerService::class)->available($poster)->toDecimal())->toBe('0.00');
});

/*
|--------------------------------------------------------------------------
| The funding page (FR-039)
|--------------------------------------------------------------------------
*/

it('shows only enabled methods with their real details', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    PaymentMethod::factory()->crypto()->create([
        'is_enabled' => true,
        'label' => 'USDT — TRC-20',
        'crypto_address' => 'TXyzVISIBLE123456',
    ]);
    PaymentMethod::factory()->crypto()->create([
        'is_enabled' => false,
        'label' => 'Hidden method',
        'crypto_address' => 'TXyzHIDDEN123456',
    ]);

    $this->actingAs($poster)
        ->get('/wallet/deposits')
        ->assertOk()
        ->assertSee('USDT — TRC-20')
        ->assertSee('TXyzVISIBLE123456')
        ->assertDontSee('Hidden method')
        ->assertDontSee('TXyzHIDDEN123456');
});

it('shows an honest empty state when no methods are enabled', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    PaymentMethod::factory()->crypto()->create(['is_enabled' => false]);

    $this->actingAs($poster)
        ->get('/wallet/deposits')
        ->assertOk()
        ->assertSee('Funding is not open yet')
        ->assertDontSee('Step 1');
});

it('tells the poster the balance is not credited until review', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)
        ->get('/wallet/deposits')
        ->assertOk()
        ->assertSee('not credited until an admin confirms');
});

it('lists only the posters own deposits', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $other = User::factory()->approvedPoster()->create();

    DepositRequest::factory()->forUser($owner)->create(['transfer_reference' => 'MINE1234']);
    DepositRequest::factory()->forUser($other)->create(['transfer_reference' => 'THEIRS99']);

    $this->actingAs($owner)
        ->get('/wallet/deposits')
        ->assertOk()
        ->assertSee('MINE1234')
        ->assertDontSee('THEIRS99');
});

it('never renders the proof as a public storage url', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $method = PaymentMethod::factory()->crypto()->create(['is_enabled' => true]);

    $this->actingAs($poster)->post('/wallet/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '100.00',
        'transfer_reference' => 'REF123',
        'proof' => UploadedFile::fake()->image('proof.png'),
    ]);

    $deposit = DepositRequest::first();

    $html = $this->actingAs($poster)
        ->get("/wallet/deposits/{$deposit->id}")
        ->assertOk()
        ->getContent();

    // The image must come from the authorised route, never /storage/.
    expect($html)->toContain('/wallet/deposits/'.$deposit->id.'/proof')
        ->and($html)->not->toContain('/storage/'.$deposit->proof_path);
});
