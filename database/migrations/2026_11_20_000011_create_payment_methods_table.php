<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment methods (FR-038).
 *
 * These are the instructions a poster follows to put money into the system:
 * a bank account, or a crypto address on a named network.
 *
 * Two design points:
 *
 * 1. `softDeletes`. A poster's deposit request snapshots the method it was told
 *    to pay, but the method row still has to exist for the admin to understand
 *    history. Hard-deleting would orphan that trail (FR-038e).
 *
 * 2. `is_enabled` rather than deletion for the normal case. Disabling removes a
 *    method from the funding page immediately while leaving every existing
 *    request intact and readable (FR-038b).
 *
 * The crypto columns are separate from the bank columns rather than one generic
 * "details" blob, so the shape of each type is visible in the schema and a
 * constraint can reference it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            // Presented to the admin and copied onto each request's snapshot.
            $table->string('label', 80);

            $table->string('type', 20);

            // --- Bank transfer ---
            $table->string('bank_name', 120)->nullable();
            $table->string('account_name', 120)->nullable();
            $table->string('account_number', 60)->nullable();
            // Sort code, IBAN tail, SWIFT, or whatever the admin needs to add.
            $table->string('routing_detail', 160)->nullable();

            // --- Crypto transfer ---
            $table->string('crypto_asset', 20)->nullable();      // e.g. USDT
            $table->string('crypto_network', 40)->nullable();    // e.g. ERC-20, TRC-20
            $table->string('crypto_address', 120)->nullable();

            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // The funding page query: enabled methods, in admin-defined order.
            $table->index(['is_enabled', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
