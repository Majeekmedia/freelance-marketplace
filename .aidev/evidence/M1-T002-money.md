# T-002 Verification — Money value object

- **Requirement IDs:** FR-030 (money precision), FR-036 (currency whitelist), NFR-003 (money integrity)
- **Environment:** local — PHP 8.3.30 (Herd), Laravel 12.73.0, Pest 4.4.1
- **Date:** 2026-11-20

## Tally

```
passed: 78 · failed: 0 · blocked: 0
```

## What this task proves

Every balance in Trust-Prime is derived from ledger rows expressed in `Money`.
If `Money` is wrong, every figure in the product is wrong. So this is the one
class that cannot be "about right".

## Results

| # | Test | Expected | Actual | Status |
|---|---|---|---|---|
| 1 | Ten dimes sum to exactly 1.00 | `100` minor units | `100` | passed |
| 2 | Float arithmetic is demonstrably broken | `0.1×10 !== 1.0` | confirmed false | passed |
| 3 | Float input rejected at the boundary | `TypeError` | thrown | passed |
| 4 | Third decimal place rejected | throws | throws | passed |
| 5 | Negative amounts rejected on all 4 routes | throws ×4 | throws ×4 | passed |
| 6 | Round-trip decimal → minor → decimal | exact for 8 values | exact | passed |
| 7 | Human input parsed (`$1,200.00`) | `120000` | `120000` | passed |
| 8 | Ambiguous input rejected | throws ×4 | throws ×4 | passed |
| 9 | Cross-currency arithmetic rejected | throws ×3 | throws ×3 | passed |
| 10 | Allocation sums exactly (100.00 ÷ 3) | total `100.00` | `100.00` | passed |
| 11 | Remainder allocated, not lost (0.05 ÷ 3) | `0.02, 0.02, 0.01` | exact | passed |
| 12 | Ratio allocation (500 ÷ 1:2:2) | `100/200/200` | exact | passed |
| 13 | Awkward ratio sums exactly (100 ÷ 1:1:1) | total `100.00` | `100.00` | passed |
| 14 | Allocation invariants across 8 awkward splits | recombined = original | all exact | passed |
| 15 | JSON keeps precision (minor units) | round-trips | round-trips | passed |
| 16 | Currency whitelist is exactly USD/EUR/GBP/CAD | 4 codes | 4 codes | passed |
| 17 | **NGN rejected with explicit message** | throws, names supported | throws, names all 4 | passed |
| 18 | **NGN rejected in any case/whitespace** | throws ×4 | throws ×4 | passed |
| 19 | **No NGN case exists on the enum** | absent | absent | passed |
| 20 | **Static guard: no float money in app/** | 0 offenders | 0 | passed |
| 21 | **Static guard: no `balance` column** | 0 offenders | 0 | passed |
| 22 | Guard detects a real violation (injected, then removed) | FAIL + names file | FAIL + named `_Violation.php → (float)` | passed |

## The guard was tested by breaking it

A guard that never fires is indistinguishable from no guard. I injected
`app/Domain/_Violation.php` containing a `(float)` cast, ran the suite, and
confirmed:

```
FAIL  Tests\Unit\NoFloatingPointMoneyTest
app\Domain\_Violation.php → (float)
```

The file was then removed and the suite returned to 78 passing. **The guard is
proven to work, not merely present.**

## Design decisions recorded

1. **Private constructor.** Callers must use a factory that validates. This
   makes an unvalidated `Money` impossible to construct.
2. **`fromDecimal` accepts `string|int` only.** A `float` is rejected at the type
   level: by the time a float reaches the class, precision is already lost, so
   accepting one would defeat the purpose.
3. **No negative amounts.** Direction is the ledger row's job (`credit`/`debit`),
   not the sign of the value. This makes double-entry reasoning explicit and
   removes an entire class of sign bugs.
4. **`subtract` throws rather than going negative.** An attempt to overdraw is an
   invariant violation, not a state the system should represent silently.
5. **`allocate` and `allocateByRatio` guarantee exact sums.** These are what make
   FR-032 (milestone totals must equal the budget) enforceable: a 500.00 budget
   split into three milestones can never total 499.99 or 500.01.
6. **`parse()` is the only method for raw input.** It tolerates `$1,200.00`
   because humans type that, but rejects anything ambiguous rather than guessing.
7. **NGN gets a specific error message** naming the supported currencies. A
   generic "unknown currency" would invite someone to simply add it.

## Sanitation check

```
composer audit → No security vulnerability advisories found
```

## Reproduction

```
./vendor/bin/pest
```

Expected: 78 passed (123 assertions).

## What is NOT yet proven

This task proves the arithmetic and the currency whitelist. It does **not** yet
prove:

- that no `balance` column exists in a real schema (no migrations exist yet —
  the guard is in place and will fail when one is added)
- that ledger rows reconcile (T-011)
- that escrow arithmetic holds across milestones (T-014, T-020)

Those are later tasks and are tracked in `tasks.md`.
