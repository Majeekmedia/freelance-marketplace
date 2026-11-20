<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site settings (T-004, FR-027, FR-035).
 *
 * A single key/value table rather than a wide row with one column per setting,
 * because settings are read far more often than written and new settings should
 * not require a migration.
 *
 * `value` is stored as text and cast by the consumer. JSON-encoding it would
 * make a simple site name awkward to inspect in the database during support.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
