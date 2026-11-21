<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deposit requests (FR-039, FR-040).
 *
 * A poster has already made the transfer outside the app. This row is their
 * claim that it happened, plus the evidence. **Nothing is credited here** — the
 * money only enters the ledger when an admin approves, because a self-reported
 * transfer is not proof.
 *
 * Two columns carry most of the design:
 *
 * `method_snapshot` is the payment method as the poster saw it, captured as JSON
 * at submission. Editing the method later must not rewrite what this person was
 * told to pay — that would make the record lie about its own instructions.
 *
 * `credited_amount_cents` is filled by the admin, not the poster. Banks deduct
 * fees, so the amount that arrives is often less than the amount sent, and the
 * admin credits what actually landed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            /*
             * Nullable so the row survives a method being removed, but the
             * snapshot keeps it meaningful. `nullOnDelete` rather than cascade:
             * deleting a payment method must never delete the record of money
             * someone says they sent.
             */
            $table->foreignId('payment_method_id')->nullable()
                ->constrained('payment_methods')->nullOnDelete();

            // What the poster says they sent.
            $table->unsignedBigInteger('amount_requested_cents');

            // What the admin decided actually arrived (FR-040a).
            $table->unsignedBigInteger('credited_amount_cents')->nullable();

            $table->char('currency', 3)->default('USD');

            // Bank reference or transaction hash.
            $table->string('transfer_reference', 255);

            // Private disk only. Never on the public disk.
            $table->string('proof_path');

            // The instructions as shown, frozen.
            $table->json('method_snapshot');

            $table->string('status', 20)->default('pending');

            $table->string('rejection_reason', 255)->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // The admin queue: pending first, oldest first.
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_requests');
    }
};
