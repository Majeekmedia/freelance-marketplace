<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestones (FR-032).
 *
 * A job's budget is split into ordered milestones. The sum of the milestone
 * amounts must equal the job budget exactly — that rule is enforced in
 * MilestoneService, but `UNIQUE(job_postings_id, sequence)` is here so two
 * milestones can never claim the same position.
 *
 * `released_cents` exists because a milestone may be partially released; the
 * remaining held amount is derived, never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();

            $table->unsignedSmallInteger('sequence');
            $table->string('title', 160);
            $table->unsignedBigInteger('amount_cents');

            // pending → delivered → accepted → released (or refunded)
            $table->string('status', 20)->default('pending');

            $table->text('delivered_note')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->unique(['job_id', 'sequence']);
            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
