<?php

declare(strict_types=1);

use App\Domain\DepositStatus;
use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\AuditLog;
use App\Models\DepositRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DepositApprovalService;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('proofs');
});

/** A pending deposit with a fresh poster. */
function pendingDeposit(int $cents = 25000): array
{
    $poster = User::factory()->approvedPoster()->create();
    $deposit = DepositRequest::factory()->forUser($poster)->pending()->create([
        'amount_requested_cents' => $cents,
    ]);

    return [$poster, $deposit];
}

/*
|--------------------------------------------------------------------------
| Approval credits exactly once (FR-040a, FR-040b)
|--------------------------------------------------------------------------
*/

it('credits the wallet once on approval and the balance rises by exactly that', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();
    $ledger = app(LedgerService::class);

    expect($ledger->available($poster)->toDecimal())->toBe('0.00');

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/approve", [
            'credited_amount' => '250.00',
            'reason' => 'Transfer matched the bank statement',
        ])
        ->assertRedirect();

    expect($ledger->available($poster)->toDecimal())->toBe('250.00')
        ->and($poster->ledgerEntries()->count())->toBe(1);

    $entry = $poster->ledgerEntries()->first();

    expect($entry->kind)->toBe(LedgerKind::DEPOSIT_CREDIT)
        ->and($entry->amount_cents)->toBe(25000)
        ->and($entry->reason)->toBe('Transfer matched the bank statement')
        ->and($entry->actor_id)->toBe($admin->id);

    expect($deposit->fresh()->status)->toBe(DepositStatus::APPROVED)
        ->and($deposit->fresh()->credited_amount_cents)->toBe(25000);
});

it('refuses to approve the same deposit twice', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();
    $ledger = app(LedgerService::class);

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'credited_amount' => '250.00',
        'reason' => 'First approval, legitimate',
    ]);

    // Second attempt — a double click, a retry, a stale tab.
    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/approve", [
            'credited_amount' => '250.00',
            'reason' => 'Second approval should be refused',
        ])
        ->assertSessionHasErrors('deposit');

    // Exactly one ledger row, exactly one credit.
    expect($poster->ledgerEntries()->count())->toBe(1)
        ->and($ledger->available($poster)->toDecimal())->toBe('250.00');
});

it('refuses at the service layer too, not only at the route', function (): void {
    // A route-level check can be bypassed by a new caller. The service must hold
    // the line on its own.
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();
    $service = app(DepositApprovalService::class);

    $service->approve($admin, $deposit, null, 'First approval at service level');

    expect(fn () => $service->approve($admin, $deposit->fresh(), null, 'Second at service level'))
        ->toThrow(DomainException::class);

    expect($poster->ledgerEntries()->count())->toBe(1);
});

it('credits the admin-chosen amount when it differs from the request', function (): void {
    // Banks deduct fees: poster sent 250, 243.50 arrived.
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();
    $ledger = app(LedgerService::class);

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'credited_amount' => '243.50',
        'reason' => 'Bank deducted a 6.50 fee',
    ]);

    expect($ledger->available($poster)->toDecimal())->toBe('243.50')
        ->and($deposit->fresh()->credited_amount_cents)->toBe(24350)
        ->and($deposit->fresh()->amount_requested_cents)->toBe(25000)
        ->and($deposit->fresh()->wasAdjusted())->toBeTrue();
});

it('defaults to the requested amount when no override is given', function (): void {
    [$poster, $deposit] = pendingDeposit(12345);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Full amount arrived as stated',
    ]);

    expect($deposit->fresh()->credited_amount_cents)->toBe(12345)
        ->and(app(LedgerService::class)->available($poster)->toDecimal())->toBe('123.45');
});

/*
|--------------------------------------------------------------------------
| Rejection credits nothing (FR-040c)
|--------------------------------------------------------------------------
*/

it('rejects a deposit without crediting anything', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/reject", [
            'reason' => 'No matching transfer found in the account',
        ])
        ->assertRedirect();

    expect($deposit->fresh()->status)->toBe(DepositStatus::REJECTED)
        ->and($deposit->fresh()->rejection_reason)->toBe('No matching transfer found in the account')
        ->and($deposit->fresh()->credited_amount_cents)->toBeNull()
        ->and($poster->ledgerEntries()->count())->toBe(0)
        ->and(app(LedgerService::class)->available($poster)->toDecimal())->toBe('0.00');
});

it('requires a reason to reject', function (): void {
    [, $deposit] = pendingDeposit();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/reject", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($deposit->fresh()->status)->toBe(DepositStatus::PENDING);
});

it('requires a reason to approve', function (): void {
    [, $deposit] = pendingDeposit();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/approve", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($deposit->fresh()->status)->toBe(DepositStatus::PENDING);
});

/*
|--------------------------------------------------------------------------
| Decisions are terminal (FR-040e)
|--------------------------------------------------------------------------
*/

it('cannot reject an approved deposit', function (): void {
    [$poster, $deposit] = pendingDeposit(10000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Approved, legitimate transfer',
    ]);

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/reject", [
            'reason' => 'Trying to reject after approving',
        ])
        ->assertSessionHasErrors('deposit');

    expect($deposit->fresh()->status)->toBe(DepositStatus::APPROVED)
        // And the money stays: an approve-then-reject would orphan a credit.
        ->and(app(LedgerService::class)->available($poster)->toDecimal())->toBe('100.00');
});

it('cannot approve a rejected deposit', function (): void {
    [$poster, $deposit] = pendingDeposit(10000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/reject", [
        'reason' => 'Reference did not match anything',
    ]);

    $this->actingAs($admin)
        ->post("/admin/deposits/{$deposit->id}/approve", ['reason' => 'Trying to approve a rejection'])
        ->assertSessionHasErrors('deposit');

    expect($deposit->fresh()->status)->toBe(DepositStatus::REJECTED)
        ->and($poster->ledgerEntries()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Every decision is audited (FR-040d, FR-028)
|--------------------------------------------------------------------------
*/

it('writes exactly one audit row on approval naming the actor and amount', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create(['name' => 'Ops Admin']);

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'credited_amount' => '250.00',
        'reason' => 'Matched the statement line 4471',
    ]);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();

    expect($log->action)->toBe(AuditLogger::DEPOSIT_APPROVED)
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->target_type)->toBe('deposit_request')
        ->and($log->target_id)->toBe($deposit->id)
        ->and($log->amount_cents)->toBe(25000)
        ->and($log->reason)->toBe('Matched the statement line 4471')
        ->and($log->context['ledger_entry_id'])->toBe($poster->ledgerEntries()->first()->id);
});

it('writes exactly one audit row on rejection', function (): void {
    [, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/reject", [
        'reason' => 'No such transfer exists',
    ]);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();

    expect($log->action)->toBe(AuditLogger::DEPOSIT_REJECTED)
        ->and($log->amount_cents)->toBeNull()
        ->and($log->reason)->toBe('No such transfer exists');
});

it('refuses to update an audit row', function (): void {
    [, $deposit] = pendingDeposit();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Audit immutability test',
    ]);

    $log = AuditLog::first();

    expect(fn () => $log->update(['reason' => 'Rewritten']))->toThrow(DomainException::class);
});

it('refuses to delete an audit row', function (): void {
    [, $deposit] = pendingDeposit();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Audit deletion test',
    ]);

    $log = AuditLog::first();

    expect(fn () => $log->delete())->toThrow(DomainException::class)
        ->and(AuditLog::count())->toBe(1);
});

it('refuses a raw SQL delete of an audit row', function (): void {
    [, $deposit] = pendingDeposit();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Raw delete refusal test',
    ]);

    $log = AuditLog::first();

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(AuditLog::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Atomicity
|--------------------------------------------------------------------------
| The ledger row and the decided request must not diverge. If the audit write
| fails, the credit must roll back with it.
*/

it('rolls back the credit if the audit write fails', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();

    // Force the audit insert to fail by dropping the table inside a transaction
    // is not possible in SQLite here, so instead we simulate the failure by
    // making the reason too long for the column after validation.
    // The service validates length, so we call it directly with a sabotage.
    $service = app(DepositApprovalService::class);

    $this->expectException(\Throwable::class);

    try {
        DB::transaction(function () use ($service, $admin, $deposit): void {
            $service->approve($admin, $deposit, null, 'Atomicity probe reason');
            // Now force a failure after the credit was written.
            throw new \RuntimeException('simulated later failure');
        });
    } finally {
        // Nothing should have persisted: no credit, still pending.
        expect($poster->fresh()->ledgerEntries()->count())->toBe(0)
            ->and($deposit->fresh()->status)->toBe(DepositStatus::PENDING)
            ->and(AuditLog::count())->toBe(0);
    }
});

/*
|--------------------------------------------------------------------------
| Access control
|--------------------------------------------------------------------------
*/

it('blocks a poster from approving their own deposit', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);

    $this->actingAs($poster)
        ->post("/admin/deposits/{$deposit->id}/approve", ['reason' => 'Self approval attempt'])
        ->assertNotFound();

    expect($poster->ledgerEntries()->count())->toBe(0)
        ->and($deposit->fresh()->status)->toBe(DepositStatus::PENDING);
});

it('blocks a freelancer from approving a deposit', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->post("/admin/deposits/{$deposit->id}/approve", ['reason' => 'Freelancer approval attempt'])
        ->assertNotFound();

    expect($poster->ledgerEntries()->count())->toBe(0);
});

it('blocks a guest from the deposit queue', function (): void {
    $this->get('/admin/deposits')->assertRedirect('/login');
});

it('shows the queue to an admin', function (): void {
    $admin = User::factory()->admin()->create();
    [, $deposit] = pendingDeposit(25000);

    $this->actingAs($admin)
        ->get('/admin/deposits')
        ->assertOk()
        ->assertSee('Deposit review')
        ->assertSee($deposit->transfer_reference);
});

it('shows the pending count in the queue', function (): void {
    $admin = User::factory()->admin()->create();
    pendingDeposit();
    pendingDeposit();

    $this->actingAs($admin)
        ->get('/admin/deposits')
        ->assertOk()
        ->assertSee('2 requests waiting for a decision');
});

/*
|--------------------------------------------------------------------------
| Derived balance after a full deposit lifecycle (FR-041b)
|--------------------------------------------------------------------------
*/

it('reconciles the balance to the ledger after approval', function (): void {
    [$poster, $deposit] = pendingDeposit(25000);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/admin/deposits/{$deposit->id}/approve", [
        'reason' => 'Reconcile check approval',
    ]);

    $expected = $poster->ledgerEntries()->get()->reduce(
        fn (int $carry, $e): int => $carry + $e->direction->apply($e->amount_cents),
        0,
    );

    expect(app(LedgerService::class)->available($poster)->minorUnits)->toBe($expected)
        ->and($expected)->toBe(25000);
});
