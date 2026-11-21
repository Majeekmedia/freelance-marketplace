# M4 Verification — T-011d Admin deposit approval

- **Requirement IDs:** FR-040 (a–e), FR-028 (audit log), FR-041 (derived balance)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium at 390px; `curl` with a real admin session and CSRF
- **Date:** 2026-11-21

## Tally

```
passed: 22 · failed: 0 · blocked: 0
```

Full suite: **350 passed (817 assertions)**. T-011d contributes 22 tests.

This is the task where money first enters the system, so the assertions are
deliberately blunt: exactly one ledger row, exactly one audit row, and no second
credit under any retry.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Approval credits once and balance rises by exactly that (a) | integration + HTTP | +250.00 | 0.00 → 250.00, 1 ledger row | passed |
| 2 | Ledger row has correct kind, amount, reason, actor | integration | `deposit_credit` | all correct | passed |
| 3 | Second approval refused (b) | integration + HTTP | refused, 1 row | balance and rows unchanged | passed |
| 4 | Second approval refused **at the service layer** | integration | `DomainException` | thrown | passed |
| 5 | Admin-chosen amount differs from request | integration + browser | credits 271.25 | 271.25 credited, 275.00 recorded as sent | passed |
| 6 | Defaults to requested amount with no override | integration | 123.45 | 123.45 | passed |
| 7 | Rejection credits nothing (c) | integration | 0 rows, balance 0.00 | confirmed | passed |
| 8 | Rejection records the reason (c) | integration | reason stored | stored, shown to poster | passed |
| 9 | Reason required to reject | integration | error | `reason` error | passed |
| 10 | Reason required to approve | integration | error | `reason` error | passed |
| 11 | Cannot reject an approved deposit (e) | integration | refused, credit intact | APPROVED, $100.00 kept | passed |
| 12 | Cannot approve a rejected deposit (e) | integration | refused, 0 rows | REJECTED, 0 rows | passed |
| 13 | One audit row on approval naming actor + amount (d) | integration + HTTP | 1 row | 1 row, actor + amount + ledger id | passed |
| 14 | One audit row on rejection (d) | integration | 1 row | 1 row, amount null | passed |
| 15 | Audit row cannot be updated | integration | `DomainException` | refused | passed |
| 16 | Audit row cannot be deleted | integration | `DomainException` | refused | passed |
| 17 | Audit row cannot be deleted by raw SQL | integration | `QueryException` | refused by trigger | passed |
| 18 | Credit rolls back if a later step fails | integration | nothing persists | 0 rows, still pending, 0 audits | passed |
| 19 | Poster cannot approve their own deposit | integration | 404 | 404, 0 rows | passed |
| 20 | Freelancer cannot approve | integration | 404 | 404 | passed |
| 21 | Guest blocked from the queue | integration | redirect | `/login` | passed |
| 22 | Balance reconciles to ledger after approval | integration | equal | equal, 25000 | passed |

Plus: the queue lists pending with a count, and an admin sees the deposit.

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `350 passed (817 assertions)` |
| Command output — T-011d | `./vendor/bin/pest tests/Feature/DepositApprovalTest.php` → `22 passed (77 assertions)` |
| HTTP verification script | `tools/verify-deposit-approval.php` |
| Live DB after browser approval | `status=approved requested=27500 credited=27125 balance=$1,171.75 ledger_rows=4` |
| Live audit row | `deposit.approved actor_id=3 amount=27125 reason="Network fee of 3.75 deducted; matched on-chain"` |

### HTTP verification output (real session, real CSRF)

```
deposit #2: requested 400.00, poster balance 0
POST approve #1 → 302
  status=approved credited=40000
  balance change: 0 → 40000 (+40000)
  ledger rows: 0 → 1
POST approve #2 → 302  (back with an error, not a 500)
  balance: 40000 → 40000 (unchanged)
  ledger rows: 1 → 1 (unchanged)
audit rows for this deposit: 1 (expect 1)
ALL CHECKS PASSED
```

### Browser — admin queue and decision

| Observation | Value |
|---|---|
| Pending count | `1 request waiting for a decision` |
| Detail page projection (while pending) | "If you credit $275.00, the balance becomes $1,175.50" |
| Approved with fee deducted | credited **$271.25**, balance $900.50 → **$1,171.75** |
| Decision block after approval | Approved / amount credited / reviewed by / reviewed at + "cannot be changed" |

### Browser — poster view after approval

`Approved. $271.25 was added to your wallet by Ops Admin on 21 Nov 2026.` plus
"The amount credited differs from the amount you sent — banks and networks often
deduct a fee, and only what arrived is credited."

Decision forms are absent on a decided request (verified `noDecisionForm: true`).

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| Approve a deposit | poster (owner) | denied | 404, no credit | passed |
| Approve a deposit | freelancer | denied | 404, no credit | passed |
| Approve a deposit | guest | denied | redirect to login | passed |
| Approve the same deposit twice | admin | refused | second attempt no-ops | passed |
| Approve a rejected deposit | admin | refused | refused | passed |
| Reject an approved deposit | admin | refused, credit intact | refused, $100.00 kept | passed |
| Delete an audit row (raw SQL) | — | refused | trigger aborts | passed |

## Guard proven by breaking it

The double-approval guard was disabled (`if (false && $locked->status->isDecided())`)
and three tests failed:

- `it refuses to approve the same deposit twice`
- `it refuses at the service layer too, not only at the route`
- `it cannot approve a rejected deposit`

Restoring it returned the suite to 350 green. The guard is therefore enforced by
tests that have been observed failing — including the service-layer one, which
proves the protection does not depend on the route being the only caller.

## Defects found and fixed during this task

### 1. The policy check pre-empted the useful error message

`$this->authorize('review', $deposit)` ran before the service, and
`DepositPolicy::review()` requires `isPending()`. The most common real case —
two admins opening the same queue, or one double-clicking — produced a bare `403
Unauthorized` instead of "this has already been approved". Three tests failed
because no error reached the session.

Fixed by checking **admin-ness** in the controller and **pending state** in the
service, which already re-checks under a row lock. The money is protected either
way; the person now gets a message that describes what actually happened. This is
the distinction between authorisation ("may you") and state ("can this still
happen") — conflating them produces an unhelpful error for the common case.

### 2. A stale projection shown on a decided request

The admin detail page rendered "If you credit $275.00, the balance becomes
$1,446.75" even after approval. On a settled request that is a hypothetical
sitting beside a fact, and it double-counts the credit that has already landed.
Now shown only while pending; afterwards it says the balance already includes the
decision. Caught by reading the rendered page rather than trusting the code.

## Notes on deliberate design

- **One transaction covers credit + decision + audit.** A credit without an audit
  row, or a decided request without a ledger row, are both unacceptable; the test
  `rolls back the credit if the audit write fails` proves they fail together.
- **`lockForUpdate` inside the transaction.** Checking the status on the instance
  passed in would be a time-of-check/time-of-use bug: two concurrent approvals
  could both observe `pending`. The row lock makes the second one fail. Verified
  over HTTP, where the second POST left both the balance and the ledger row count
  untouched.
- **The credited amount belongs to the admin.** Banks and networks deduct fees, so
  the amount that arrived is often not the amount sent. The UI shows both figures
  and explains the difference rather than silently crediting the smaller number.
- **A separate `audit_logs` table, not columns on the ledger.** The ledger answers
  "why is this balance what it is"; the audit log answers "who decided this and
  what did they see". Not every audited action moves money, and putting the
  decision record beside the credit would make a dispute check circular.
- **Decisions are terminal.** Approved → rejected would leave a credit in place
  for a request marked rejected. A correction is a new compensating entry, so the
  history always explains itself.
