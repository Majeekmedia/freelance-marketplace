<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add platform identity to the stock Laravel users table (T-003).
 *
 * `type`   — freelancer | poster | admin. Fixed at registration (assumption A5).
 * `status` — pending | approved | rejected | suspended | deleted.
 *
 * Both are stored as short strings rather than native ENUMs so the schema stays
 * portable between SQLite (local) and MySQL/MariaDB (host), and so adding a
 * status later does not require a table rebuild on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 20)->default('freelancer')->after('email');
            $table->string('status', 20)->default('pending')->after('type');

            // Soft delete: an account with ledger history must never be
            // hard-deleted (FR-024), so deletion is a status + timestamp.
            $table->softDeletes();

            // Admin queues filter on status constantly.
            $table->index('status');
            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'type']);
            $table->dropSoftDeletes();
            $table->dropColumn(['type', 'status']);
        });
    }
};
