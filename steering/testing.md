---
inclusion: always
---

# Testing rules — Trust-Prime

## Commands

```bash
php artisan test                 # unit + feature
php artisan test --filter=Money  # one subject
npx playwright test              # browser journeys
vendor/bin/pint --test           # style
```

## The test that must never be deleted

```php
it('reconciles every balance to the ledger', function () { ... });
```

If this test is removed, weakened, or skipped, the ledger guarantee is gone.
Treat its deletion as a **critical** review finding.

## Rules

- Isolated test data, disposable database. Never point tests at real records.
- Report **passed, failed and blocked separately**. A blocked test is not a pass.
- **Never disable, skip or weaken a test to make the suite green.**
- Add a regression test for every confirmed bug **before** fixing it.
- Preserve failure artefacts (trace, screenshot, log) **before** changing code.
- Zero tests passing is not success — it is an untouched scaffold.

## What each level must cover

| Level | Must cover |
|---|---|
| Unit | `Money` arithmetic; milestone sum validation; currency whitelist rejects NGN |
| Feature | every policy's permitted **and** forbidden path; ledger derivation; idempotency guards |
| Browser | the full journey plus the forbidden paths (admin as poster, cross-user data) |

## Required feature tests (money — non-negotiable)

| Test | Proves |
|---|---|
| deposit approved credits exactly the chosen amount | FR-040 |
| deposit approved twice creates one row | idempotency |
| pending deposit credits nothing | FR-039 |
| fund job deducts exactly the budget | FR-010 |
| fund twice rejected | `escrows.job_id` unique |
| release milestone out of order rejected | FR-032 |
| release milestone twice rejected | idempotency |
| partial refund leaves released milestones paid | FR-026 |
| withdrawal cannot exceed available | FR-019 |
| withdrawal without KYC rejected | FR-019 |
| balance reconciliation after random sequence | FR-041 |
| no table has a `balance` column | FR-041 |

## Required feature tests (access control)

| Test | Proves |
|---|---|
| freelancer cannot read another's wallet | object-level |
| freelancer cannot read another's thread | object-level |
| freelancer cannot read another's KYC | object-level |
| poster cannot read another's applicants | object-level |
| poster cannot reach `/admin` | role gate |
| pending user cannot apply | account gate |
| guest cannot submit a review | auth gate |

## Evidence format

Every verification record needs a tally line:

```
passed: 12 · failed: 0 · blocked: 0
```

Blocked items must name a **Blocker** and an **Owner**. A milestone whose tally
reads `passed: 0` has run no tests and is **not** verified.

## What a green build does not prove

`php artisan test` passing does not prove a poster can fund a job in a browser.
Only a browser run of the journey proves that.
