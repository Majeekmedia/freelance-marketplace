# T-006 Verification — Approval flow end to end

- **Requirement IDs:** FR-004 (approval gates actions), FR-024 (admin account management)
- **Environment:** local — PHP 8.3.30, Laravel 12.73.0, Pest 4.4.1; verified in Chromium
- **Date:** 2026-11-20

## Tally

```
passed: 165 · failed: 0 · blocked: 0
```

## Automated results

| # | Test | Expected | Actual | Status |
|---|---|---|---|---|
| 1 | **Full story: register → pending → approve → can act** | state moves correctly | exact | passed |
| 2 | Pending user reaches dashboard with an explanation | 200 + notice | exact | passed |
| 3 | Approved user's dashboard loses the pending notice | notice absent | absent | passed |
| 4 | Admin can list accounts | 200 + rows | exact | passed |
| 5 | Accounts filter by status | only matching rows | exact | passed |
| 6 | **Freelancer gets 404 on `/admin/users`** | 404 | 404 | passed |
| 7 | **Poster gets 404 on `/admin/users`** | 404 | 404 | passed |
| 8 | Suspend blocks authentication afterwards | cannot authenticate | exact | passed |
| 9 | Reinstate restores approved status | approved | exact | passed |
| 10 | **Illegal transition (pending → suspended) refused via route** | error, status unchanged | refused | passed |
| 11 | **Admin cannot suspend their own account** | error, unchanged | refused | passed |
| 12 | **Cannot delete the only administrator** | error, admin remains | refused | passed |
| 13 | Unauthenticated delete is blocked | redirect to login | exact | passed |

## Browser verification — the real workflow

| Step | Result |
|---|---|
| Register a freelancer via the form | dashboard shows **Pending approval** and the "awaiting approval" notice |
| Sign out, sign in as admin | admin dashboard shows "Administration" |
| `GET /admin/users` | table lists both accounts; pending count "1 awaiting approval" |
| Click **Approve** on the pending user | **"Pending Tester has been approved."** |
| Row after approval | status → **Approved**, actions → **Suspend**, pending count → **0** |
| Sign in as that freelancer | dashboard loads, **pending notice gone**, no "Accounts" nav link |
| `GET /admin/users` as that freelancer | **404 NOT FOUND**, no table rendered |

The last row is the one that matters. The nav link was already hidden — a user
who trusts the UI would believe they have no access. The 404 proves the **server**
refuses independently of what the interface shows.

## Guard rails added beyond the requirement

These were not asked for, but each prevents a way to lock the platform out of itself:

1. **An admin cannot suspend or delete their own account.** One misclick would
   otherwise remove the only person able to fix it.
2. **The last administrator cannot be deleted.** Checked by counting remaining
   admins before the transition.
3. **Soft delete only.** A hard delete would orphan ledger rows and break the
   balance reconciliation guarantee (FR-041). `DELETED` is a terminal status, not
   a missing row.

## Why the controller is thin

Status transitions live on `User` and throw on an illegal move. The controller
only authorises, delegates and reports. That means the rules cannot drift between
the web route, a queue job and a console command — they are defined once.

## Auditing: an honest gap

Transitions are currently written to the **application log** with actor, target
and old/new status. That is not the immutable audit trail FR-028 requires —
`audit_logs` does not exist yet (T-012). Recorded here so the difference is not
mistaken for compliance.

## Reproduction

```
php artisan migrate
./vendor/bin/pest
```

Expected: 165 passed (328 assertions).

## What is NOT yet proven

- Persistent, immutable admin audit trail (T-012)
- Branded welcome / approval emails (T-018c) — no mail is sent on approval yet
- Pagination behaviour beyond 25 accounts
