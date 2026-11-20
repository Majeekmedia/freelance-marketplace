<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freelancer profile (FR-007).
 *
 * The column set is deliberately split into what may be shown publicly and what
 * may not. There is no email, phone or balance column here — those live on
 * `users` (identity) and `ledger_entries` (money), so a public profile query
 * physically cannot select them. That is stronger than remembering to hide them
 * in a view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Public fields.
            $table->string('headline', 140)->nullable();
            $table->text('bio')->nullable();

            // Stored as rows in a related table would be more relational, but a
            // short fixed list of tags is read on every listing page; JSON keeps
            // the query to one row.
            $table->json('skills')->nullable();

            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->char('hourly_rate_currency', 3)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('avatar_path')->nullable();

            // Visibility control. A profile can be filled in but hidden from the
            // public directory — a freelancer may want to complete it before
            // being discoverable.
            $table->boolean('is_public')->default(true);

            $table->timestamps();

            $table->index(['is_public', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};
