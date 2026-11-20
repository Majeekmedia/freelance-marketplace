<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portfolio items (FR-008).
 *
 * `external_url` is preferred over a file upload for work samples: it avoids
 * turning the platform into a file host and sidesteps the storage-symlink risk
 * on shared hosting. An optional image is allowed for a thumbnail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title', 140);
            $table->text('description')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->string('image_path')->nullable();

            // Manual ordering — freelancers present their strongest work first.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
