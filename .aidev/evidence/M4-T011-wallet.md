# M4 Verification — T-011 Wallet and ledger

- **Requirement IDs:** FR-029 (immutable ledger), FR-018 (balances), FR-041 (no stored balance), FR-030 (precision)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium (VS Code integrated browser)
- **Date:** 2026-11-20

## Tally

```
passed: 32 · failed: 0 · blocked: 0
```

Full suite: **284 passed (599 assertions)**. T-011 contributes 17 ledger tests,
7 derived-balance/immutability guards, and 8 wallet page tests.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Record a credit, derive balance | integration | 100.00 | 100.00 | passed |
| 2 | Update a ledger row refused | integration | `DomainException` | refused, value unchanged | passed |
| 3 | Delete a ledger row refused | integration | `DomainException` | refused, row survives | passed |
| 4 | Zero amount refused | unit | exception | `InvalidArgumentException` | passed |
| 5 | Reason under 5 chars refused | unit | exception | refused (empty and "hm") | passed |
| 6 | Non-USD amount refused | unit | exception | refused | passed |
| 7 | Direction derived from kind | unit | credit / debit | correct for both | passed |
| 8 | Every kind has a direction + label | unit | exhaustive | all cases | passed |
| 9 | Balance = credits − debits | integration | 350.00 | 350.00 | passed |
| 10 | Ten × 0.10 = exactly 1.00 | unit | 100 cents | 100 cents | passed |
| 11 | Lifetime credited separate from available | integration | 200 / 150 | 200.00 / 150.00 | passed |
| 12 | Escrow held reported separately | integration | 120 / 180 | 120.00 / 180.00 | passed |
| 13 | `canAfford` boundary | unit | true / false | true at 100.00, false at 100.01 | passed |
| 14 | One wallet per user | integration | same id | same id, count 1 | passed |
| 15 | Two credits before any read both count | integration | 30.00 | 30.00 | passed |
| 16 | Reconciliation after random sequence | integration | 875.50 | 875.50, matches PHP recomputation | passed |
| 17 | Negative balance surfaced, not clamped | integration | throws | `DomainException` | passed |
| 18 | **No `balance` column on any table** | guard | none | none | passed |
| 19 | **No `balance` column in any migration** | guard | none | none | passed |
| 20 | Wallet holds identity only | guard | no balance/available/amount | confirmed | passed |
| 21 | Raw SQL UPDATE refused by database | guard | `QueryException` | refused via trigger | passed |
| 22 | Raw SQL DELETE refused by database | guard | `QueryException` | refused via trigger | passed |
| 23 | Raw SQL zero amount refused | guard | `QueryException` | refused | passed |
| 24 | Raw SQL unknown direction refused | guard | `QueryException` | refused (typo `crdeit`) | passed |
| 25 | Own wallet renders | integration | 200, $250.00 | 200 | passed |
| 26 | Empty state honest | integration | "No entries yet", $0.00 | both | passed |
| 27 | No `/wallet/{id}` route exists | guard | no `{` in any wallet URI | confirmed | passed |
| 28 | Wallet requires auth | integration | redirect to login | redirect | passed |
| 29 | Another user's entries never rendered | integration | absent | absent | passed |
| 30 | Entries newest first | integration | order | newer above older | passed |
| 31 | Reason and actor shown | integration | both visible | both | passed |
| 32 | Debits negative, credits positive | integration | `−$30.00`, `+$100.00` | both | passed |

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `284 passed (599 assertions)` |
| Command output — ledger | `./vendor/bin/pest tests/Feature/LedgerTest.php` → `17 passed (46 assertions)` |
| Command output — guards | `./vendor/bin/pest tests/Feature/WalletBalanceIsDerivedTest.php` → `7 passed` |
| Browser — wallet page | `/wallet` at 390px → Available **$900.50**, escrow **$250.00**, withdrawal **$0.00**, lifetime **$1,150.50** |
| Browser — arithmetic check | rows: +$1,000.00, +$150.50, −$250.00 → 900.50 net, matches the card |
| Browser — overflow | `scrollWidth 505 <= innerWidth 520` at mobile width |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| Read another user's ledger | any | denied | route has no id; entries scoped to session user | passed |
| `GET /wallet` anonymous | visitor | denied | redirect to `/login` | passed |
| `UPDATE ledger_entries` via SQL | raw | denied | trigger aborts | passed |
| `DELETE ledger_entries` via SQL | raw | denied | trigger aborts | passed |
| Insert `amount_cents = 0` | raw | denied | trigger aborts | passed |
| Insert `direction='crdeit'` | raw | denied | trigger aborts | passed |

## Defects found and fixed during this task

### 1. `wallets.user_id` was silently dropped by `firstOrCreate`

Same class as T-009: `user_id` is deliberately not mass-assignable (so a wallet
can never attach to the wrong user from request input), so `firstOrCreate` threw
`NOT NULL constraint failed: wallets.user_id` on 12 of the first 17 tests. Fixed
by creating through the owning relation, which sets the owner explicitly.

### 2. `LedgerEntry::$fillable = []` broke mass assignment in `post()`

The empty `$fillable` is correct — it means `LedgerEntry::create($request->all())`
has no path into the money table. But it also blocked the service. Replaced with
an explicit `LedgerEntry::post(...)` static constructor taking named arguments.
The table now has exactly one write door.

### 3. `update()` threw `MassAssignmentException`, not the useful message

`update()` fills attributes *before* firing `updating`, so the immutability event
never ran and the developer got a mass-assignment error pointing at the wrong
problem. `update()` and `delete()` are now overridden directly to throw a message
that names the rule and the remedy. The model events remain as belt and braces
for `save()` on a dirty model and bulk operations.

### 4. SQLite cannot `ALTER TABLE ... ADD CONSTRAINT ... CHECK`

The migration used `ALTER` for the positive-amount and direction constraints,
which MySQL accepts and SQLite rejects. Rather than branch into a table rebuild,
SQLite gets equivalent `BEFORE INSERT`/`BEFORE UPDATE`/`BEFORE DELETE` triggers.
Both engines now reject a zero amount, a typo'd direction, an update and a
delete at the database layer.

### 5. `Blueprint::check()` does not exist in Laravel 12

An intermediate attempt used `$table->check(...)`, which fails with
`__call("check")`. Removed in favour of the trigger approach above.

### A failed migration left an orphan table

The first `migrate` attempt created `ledger_entries` then died on the constraint
statement, leaving the table in place while the migration stayed `Pending`. The
retry failed with "table already exists". Dropped the orphan and re-ran. Worth
noting because on a real host this is exactly the state that makes someone reach
for `migrate:fresh` and destroy data.

## Guard proven in both directions

`WalletBalanceIsDerivedTest` was verified by injecting a real violation: adding
`$table->unsignedBigInteger('balance_cents')` to the wallets migration. Both
guards failed and named the file and column:

```
Balance columns found in migrations:
2026_11_20_000009_create_wallets_table.php → balance_cents
```

Removing the column returned the suite to green. This is the check that protects
FR-041, so it was not taken on trust.

## Notes on deliberate design

- **Direction is a column, not a sign.** `amount_cents > 0` is enforced by the
  database; a signed amount cannot be. Summing is written explicitly as credit
  minus debit so nobody can write `SUM(amount)` and get a plausible wrong number.
- **`LedgerService` derives the direction from the kind.** A caller cannot post
  an `escrow_hold` as a credit, which would create money from nothing.
- **A negative derived balance throws.** It means spending rules and the ledger
  disagree; clamping to zero would hide a real defect behind a plausible figure.
- **Wallet route takes no id.** `GET /wallet` resolves the user from the session,
  so there is no identifier to tamper with — a stronger guarantee than a policy.
