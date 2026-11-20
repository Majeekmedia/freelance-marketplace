<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wallets (FR-018, FR-041).
 *
 * READ THIS BEFORE ADDING A COLUMN
 * --------------------------------
 * A wallet is an *identity for money*, not a number. There is deliberately no
 * `balance` column here, and there must never be one.
 *
 * Balances are derived by summing ledger rows at read time. A stored balance is
 * a second source of truth that will eventually disagree with the ledger, and
 * when it does, nobody can say which figure is right. The ledger answers "why is
 * my balance $40?" by listing rows; a column answers with silence.
 *
 * `WalletBalanceIsDerivedTest` fails the build if a `balance` column ever
 * appears on any table, so this comment is not the only thing protecting it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            // One wallet per user. The unique index means a bug cannot create a
            // second wallet and split someone's money across two identities.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Wallets are USD-only (FR-036). Present as a column so the invariant
            // is visible in the schema rather than assumed from the app code.
            $table->char('currency', 3)->default('USD');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
