<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The immutable wallet ledger (FR-029, FR-030, FR-041).
 *
 * Every balance change in this product is one INSERT into this table. Nothing
 * here is ever updated or deleted: a mistake is corrected by inserting a
 * compensating row, so the history of what happened remains intact.
 *
 * Design points worth defending:
 *
 * 1. `amount_cents > 0` is a database CHECK, not just app validation. Direction
 *    carries the sign, so a negative amount is always a bug and the database
 *    refuses it even if someone bypasses the service layer with raw SQL.
 *
 * 2. `direction` and `kind` are short strings, not native ENUMs. MySQL ENUM
 *    changes need a table rebuild, which is the operation that times out on
 *    shared hosting. The enums live in PHP where a deploy can change them.
 *
 * 3. `reason` is NOT NULL with a minimum length enforced in the service. An
 *    unexplained money movement is not allowed to exist, even internally.
 *
 * 4. `actor_id` is nullable because a system-generated row (a scheduled job, a
 *    migration) has no human actor — but the *absence* of an actor is itself
 *    information, so the column exists rather than being omitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('ledger_entries', function (Blueprint $table) use ($driver) {
            $table->id();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();

            // What happened, and which way the money moved.
            $table->string('kind', 32);
            $table->string('direction', 8);

            // Always positive. Sign lives in `direction`.
            $table->unsignedBigInteger('amount_cents');

            $table->char('currency', 3)->default('USD');

            // Rate captured when the row was written, so a historical amount
            // renders the same figure forever (FR-036c).
            $table->decimal('fx_rate_to_usd', 18, 8)->nullable();

            // Optional references, each set only by the flow that produces it.
            $table->foreignId('job_id')->nullable()->constrained('job_postings')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();

            // Minimum 5 characters is enforced in LedgerService so the rule can
            // produce a useful validation message.
            $table->string('reason', 255);

            // Who caused it. NULL means the system itself.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Only created_at: a row that cannot be edited has no updated_at.
            $table->timestamp('created_at')->useCurrent();

            // Balance derivation and per-user history listing.
            $table->index(['user_id', 'created_at']);
            $table->index(['wallet_id', 'created_at']);
            $table->index(['kind', 'created_at']);
            $table->index('job_id');
        });

        /*
         * Positive-amount and direction constraints.
         *
         * These belong in the database because the one thing that must never
         * happen is money written by a path that skipped the service layer.
         *
         * SQLite cannot add a CHECK to an existing table, so it needs a table
         * rebuild. Rather than rebuild, the same guarantee is enforced there by
         * two triggers — an INSERT and an UPDATE trigger — which is the idiomatic
         * SQLite equivalent and keeps one code path for both engines.
         */
        if ($driver === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TRIGGER ledger_entries_amount_positive_insert
                BEFORE INSERT ON ledger_entries
                WHEN NEW.amount_cents <= 0 OR NEW.direction NOT IN ('credit','debit')
                BEGIN
                    SELECT RAISE(ABORT, 'ledger_entries: amount must be > 0 and direction must be credit or debit');
                END;
            SQL);

            DB::statement(<<<'SQL'
                CREATE TRIGGER ledger_entries_immutable_update
                BEFORE UPDATE ON ledger_entries
                BEGIN
                    SELECT RAISE(ABORT, 'ledger_entries are immutable');
                END;
            SQL);

            DB::statement(<<<'SQL'
                CREATE TRIGGER ledger_entries_immutable_delete
                BEFORE DELETE ON ledger_entries
                BEGIN
                    SELECT RAISE(ABORT, 'ledger_entries cannot be deleted');
                END;
            SQL);

            return;
        }

        DB::statement('ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_amount_positive CHECK (amount_cents > 0)');
        DB::statement("ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_direction_valid CHECK (direction IN ('credit','debit'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
