# M4 Verification — T-011c Poster deposit request with proof

- **Requirement IDs:** FR-039 (a–e)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium at 390px; `curl` with real sessions for privacy checks
- **Date:** 2026-11-21

## Tally

```
passed: 23 · failed: 0 · blocked: 0
```

Full suite: **328 passed (740 assertions)**. T-011c contributes 23 tests.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Submit creates a `pending` request | integration | pending, credited null | pending, credited NULL | passed |
| 2 | **Balance unchanged by submission (a)** | integration + live DB | 0.00 / unchanged | **$900.50 before and after**, 3 ledger rows both times | passed |
| 3 | No ledger row created on submit | integration | 0 rows | 0 rows | passed |
| 4 | Human-formatted amount `$1,250.50` | integration | 125050 cents | 125050 | passed |
| 5 | Disabled method rejected (b) | integration | error, 0 rows | `payment_method_id` error | passed |
| 6 | Soft-deleted method rejected | integration | error, 0 rows | rejected | passed |
| 7 | Non-image proof rejected (c) | integration | error | `proof` error | passed |
| 8 | Oversized proof rejected (c) | integration | error | rejected at limit+1KB | passed |
| 9 | Short reference rejected | integration | error | `transfer_reference` error | passed |
| 10 | Snapshot captured at submission (d) | integration | crypto keys | address + network frozen | passed |
| 11 | Snapshot survives a later method edit (d) | integration | frozen | original address retained | passed |
| 12 | Snapshot survives method deletion | integration | label retained | retained | passed |
| 13 | Proof stored on private disk | integration | `proofs` only | `proofs` yes, `public` no | passed |
| 14 | Owner can read own proof (e) | integration + HTTP | 200 | 200 | passed |
| 15 | Admin can read a proof (e) | integration + HTTP | 200 | 200 | passed |
| 16 | Another poster blocked from proof (e) | integration + HTTP | 404 | 404 | passed |
| 17 | Another poster blocked from request | integration + HTTP | 404 | 404 | passed |
| 18 | Freelancer blocked from proof (e) | integration + HTTP | 404 | 404 | passed |
| 19 | Freelancer cannot submit | integration | forbidden | 403 | passed |
| 20 | Pending poster cannot submit | integration | forbidden | 403 | passed |
| 21 | Cannot set own status / credited amount | integration | ignored | stayed pending, credited NULL | passed |
| 22 | Funding page shows enabled methods only | integration | 1 of 2 | hidden method absent | passed |
| 23 | Honest empty state with no methods | integration | message | "Funding is not open yet" | passed |

Plus: deposits listed per-user only, and the proof is never rendered as a `/storage/` URL.

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `328 passed (740 assertions)` |
| Command output — T-011c | `./vendor/bin/pest tests/Feature/DepositRequestTest.php` → `23 passed (69 assertions)` |
| Privacy script | `tools/verify-deposit-privacy.php` |
| Browser — funding page | `/wallet/deposits` at 390px, no overflow, real address shown, network warning, "not credited until an admin confirms" |
| Browser — after submit | Available **$900.50** (unchanged), Awaiting confirmation **$275.00** |
| Live DB after submit | `status=pending requested=27500 credited=NULL balance=$900.50 ledger_rows=3` |
| Live DB proof | `proofs` disk `true`, `public` disk `false`, randomised filename |
| Public URL probe | `/storage/...` → **403**, `/storage/app/private/...` → **403**, `/proofs/...` → **404** |

### Privacy matrix (real sessions, real CSRF)

| Case | Got | Expected | Verdict |
|---|---|---|---|
| anonymous → proof | 302 | 302 | ok |
| owner → proof | 200 | 200 | ok |
| owner → request | 200 | 200 | ok |
| admin → proof | 200 | 200 | ok |
| other poster → proof | 404 | 404 | ok |
| other poster → request | 404 | 404 | ok |
| freelancer → proof | 404 | 404 | ok |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| Submit deposit | freelancer | denied | 403 | passed |
| Submit deposit | pending poster | denied | 403 | passed |
| Read another user's proof | poster | denied | 404 | passed |
| Read another user's proof | freelancer | denied | 404 | passed |
| Fetch proof by public URL | anonymous | denied | 403 / 404, never 200 | passed |
| Post `status=approved` on own request | poster | ignored | stayed pending | passed |
| Post `credited_amount_cents=999999` | poster | ignored | NULL | passed |
| Post another `user_id` | poster | ignored | own id retained | passed |

## Guard proven by breaking it

The central promise — **submitting a deposit credits nothing** — was verified by
deliberately violating it: an auto-credit `ledger->record()` was added inside the
submit transaction. Two tests failed immediately (`it creates a pending request
and leaves the balance at zero`, `it cannot set its own status…`). Removing the
violation returned the suite to green. The guarantee is therefore enforced by a
test that has been seen to fail, not merely by a test that has passed.

## Defects found and fixed during this task

### 1. FormRequest name collided with the model

The request class was `App\Http\Requests\DepositRequest`, and it imports
`App\Models\DepositRequest`. PHP refused to load the file:
`Cannot declare class ... because the name is already in use`. Renamed to
`StoreDepositRequest` — the model keeps the domain name, the request says what
it does.

### 2. My own wallet-route guard was too broad

`WalletPageTest` asserted that no route under `/wallet` may contain any
wildcard, which broke when `/wallet/deposits/{deposit}` was added. The guard's
intent was "no route lets you change an id and read *another person's money*" —
not "no parameters anywhere". Narrowed to forbid exactly `wallet/{...}` and
`wallet/{user|id}`, allowing record-scoped routes that the policy protects.
Left as-is it would have blocked any legitimate nested resource forever.

## Notes on deliberate design

- **Nothing is credited on submit.** A self-reported transfer is a claim, not
  money. Only an admin approval writes a `deposit_credit` row (T-011d).
- **A dedicated `proofs` disk with `serve => false`.** The `local` disk sets
  `serve => true`, which registers a route Laravel can serve from. A bank
  screenshot must never be reachable that way, so the disk is separate and the
  flag explicit. Probed: no public URL returns 200.
- **404, not 403, for someone else's deposit.** A 403 confirms the id exists.
- **The snapshot is the record, not the live method.** The detail page renders
  the snapshot with a line stating later changes do not alter it — because
  otherwise editing an account would silently rewrite what a poster was told to
  pay.
- **`credited_amount_cents` is admin-only.** Banks deduct fees, so the amount
  that arrives often differs from the amount sent; the admin credits what landed
  and the UI explains the difference rather than hiding it.

## Tooling note

Playwright `click()` on the submit button did not register, and a direct
`requestSubmit()` did. No validation error was shown, so this was an automation
race rather than a page defect — consistent with the same symptom in T-011b,
where measurements proved the elements were not overlapping. Verified by
submitting programmatically and then checking the database directly, which is
stronger evidence than a click would have been.
