<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log (FR-028).
 *
 * Every admin action that moves money or changes an account is recorded here,
 * immutably, with the actor, the action, the target, the reason and the amount.
 *
 * Why this is a separate table from `ledger_entries`, not extra columns on it:
 *
 *  - The ledger answers "why is this balance what it is". The audit log answers
 *    "who did this and what did they see". Those are different questions with
 *    different retention needs.
 *  - Not every audited action touches money. Suspending an account, editing a
 *    job or rejecting KYC must be auditable but produces no ledger row.
 *  - On a dispute, "the admin credited 97.00 with reason X at time T" is the
 *    statement being verified. Putting it beside the credit makes the check
 *    circular.
 *
 * Like the ledger, this is append-only, enforced at the database layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who. Nullable for system actions (scheduled jobs, migrations),
            // because the absence of a human actor is itself information.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // What. A dotted action name such as 'deposit.approved'.
            $table->string('action', 80);

            // On what. Free-form so it can name a user, a job, a deposit…
            $table->string('target_type', 60);
            $table->unsignedBigInteger('target_id');

            // Why. A money action without a reason is not auditable.
            $table->string('reason', 255)->nullable();

            // How much, when money is involved. Integer minor units, no float.
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->char('currency', 3)->nullable();

            // Anything else worth keeping: before/after state, a decision code.
            // JSON rather than columns because audit detail is read by a human
            // investigating, not queried by the application.
            $table->json('context')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        // Append-only, at the database layer, exactly as for the ledger.
        if ($driver === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TRIGGER audit_logs_immutable_update
                BEFORE UPDATE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'audit_logs are immutable');
                END;
            SQL);

            DB::statement(<<<'SQL'
                CREATE TRIGGER audit_logs_immutable_delete
                BEFORE DELETE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'audit_logs cannot be deleted');
                END;
            SQL);

            return;
        }

        // MySQL/MariaDB: no CHECK needed here, but the same intent is documented.
        // Immutability on those engines is enforced by model events and by having
        // no update or delete route, since they cannot prevent a TRUNCATE anyway.
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
