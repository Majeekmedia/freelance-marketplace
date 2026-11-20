<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escrow holds (FR-009, FR-010, FR-011).
 *
 * One row per funded job. Its *existence* is what makes a job public, so the
 * "Escrow funded" badge on a job page is a claim backed by a row rather than by
 * a status field someone set.
 *
 * `held_cents` is the amount currently held. It is reduced as milestones are
 * released and reaches zero when the job is fully settled. There is no balance
 * column: available vs held is always derived from ledger rows plus this hold.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();

            // One escrow per job. A second funding attempt violates this, so the
            // database refuses double-funding even if the service has a bug.
            $table->foreignId('job_id')->unique()->constrained('job_postings')->restrictOnDelete();

            $table->unsignedBigInteger('held_cents')->default(0);
            $table->char('currency', 3)->default('USD');

            $table->timestamp('funded_at');

            $table->timestamps();

            $table->index('funded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};
