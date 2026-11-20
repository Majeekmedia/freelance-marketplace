<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job postings (FR-009, FR-036).
 *
 * The table is `job_postings`, NOT `jobs`: Laravel's own queue migration
 * already creates a `jobs` table, so the obvious name would collide and the
 * second migration would fail. The model is still `Job` and the routes are
 * still `/jobs`, because those are the words the product uses; only the schema
 * needs to dodge the framework.
 *
 * Two further deliberate choices:
 *
 * 1. `status` is a short string, not a native ENUM. MySQL ENUMs need a table
 *    rebuild to change, and `ALTER TABLE ... MODIFY ENUM` on a shared cPanel
 *    host is exactly the operation that times out. The enum lives in PHP
 *    (`App\Domain\JobStatus`) where it can be changed with a deploy.
 *
 * 2. There is no `funded` boolean. Whether a job is funded is *derived* from
 *    the escrow row, so the flag cannot disagree with the money. A boolean here
 *    would be a second source of truth for the most important fact in the
 *    product.
 *
 * `currency` is constrained to USD because jobs are priced in USD only
 * (FR-036). `fx_rate_to_usd` is stored so a converted amount created today
 * renders the same figure forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('poster_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

            $table->string('title', 160);
            $table->text('description');

            // Money as integer minor units. Never a decimal, never a float.
            $table->unsignedBigInteger('budget_cents');

            $table->char('currency', 3)->default('USD');

            // Rate captured at creation so converted display values are stable.
            // 8 decimal places because cross-currency rates need them.
            $table->decimal('fx_rate_to_usd', 18, 8)->default(1);

            $table->date('deadline')->nullable();

            $table->string('status', 20)->default('draft');

            // Set only when a freelancer is hired (FR-013).
            $table->foreignId('hired_freelancer_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Listing query: public jobs, newest first.
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['poster_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
