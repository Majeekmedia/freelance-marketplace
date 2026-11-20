# T-003 Verification — Users, roles, statuses, gates

- **Requirement IDs:** FR-003 (registration type), FR-004 (approval gates actions), FR-024 (admin account management)
- **Environment:** local — PHP 8.3.30, Laravel 12.73.0, Pest 4.4.1, SQLite
- **Date:** 2026-11-20

## Tally

```
passed: 108 · failed: 0 · blocked: 0
```

## Results

| # | Test | Expected | Actual | Status |
|---|---|---|---|---|
| 1 | **Admin cannot be created by mass assignment** | stays freelancer/pending | freelancer/pending | passed |
| 2 | **Account cannot self-approve by mass assignment** | stays pending | stays pending | passed |
| 3 | Registration offers only freelancer + poster | `['freelancer','poster']` | exact | passed |
| 4 | Pending account can authenticate but not act | canLogin ✓ / canAct ✗ | as expected | passed |
| 5 | Approved account can act | both true | both true | passed |
| 6 | Suspended cannot authenticate | false | false | passed |
| 7 | Rejected cannot authenticate | false | false | passed |
| 8 | Admin can approve a pending account | pending → approved | approved, canAct true | passed |
| 9 | Admin can suspend an approved account | approved → suspended | suspended, cannot log in | passed |
| 10 | Illegal transition refused (pending → suspended) | throws | `DomainException` | passed |
| 11 | Deleted is terminal | `[]` transitions | `[]` | passed |
| 12 | Transition to deleted sets `deleted_at` | soft deleted | trashed = true | passed |
| 13 | Type predicates correct for all three types | correct | correct | passed |
| 14 | Factory admin is always approved | canAct true | true | passed |
| 15 | Scopes: approved / pending counts | 2 / 1 | 2 / 1 | passed |
| 16 | Scope by type | 2 freelancers, 1 poster | exact | passed |
| 17 | Password and remember token never serialised | absent from JSON | absent | passed |
| 18 | **AccountGate lets approved through** | 200 | 200 | passed |
| 19 | **AccountGate blocks pending with a clear message** | 403 + "awaiting approval" | 403 + message | passed |
| 20 | **AccountGate ends the session of a suspended user** | 403 + guest | 403, `assertGuest` | passed |
| 21 | AccountGate blocks rejected | 403 | 403 | passed |
| 22 | Unauthenticated never reaches a gated handler | no content leak | redirected | passed |
| 23 | **AdminGate lets admin through** | 200 | 200 | passed |
| 24 | **AdminGate hides admin area from freelancer** | 404 | 404 | passed |
| 25 | **AdminGate hides admin area from poster** | 404 | 404 | passed |
| 26 | Unauthenticated never reaches the admin handler | no content leak | redirected | passed |
| 27 | Schema has type, status, deleted_at | present | present | passed |
| 28 | **Database default is freelancer + pending** (bare SQL insert) | freelancer/pending | exact | passed |
| 29 | Columns are portable short strings | varchar | varchar | passed |
| 30 | `status` is indexed for the admin queue | index present | present | passed |

## Security properties proven, not assumed

The three highest-risk account bugs are closed **structurally**, not by convention:

1. **Privilege escalation at signup** — `type` and `status` are absent from
   `$fillable`, so `User::create($request->all())` cannot mint an admin. Test 1
   proves it with an actual attacker-shaped payload.
2. **Self-approval** — same mechanism. Test 2.
3. **Database-level fallback** — even a raw SQL insert that omits both columns
   lands on `freelancer` / `pending` (test 28). The application is not the only
   guard.

## Design decisions recorded

1. **Approval gates actions, not login (assumption A7).** A pending user can sign
   in and edit their profile — which is what an admin needs to review. Making
   approval gate login instead would have forced admins to approve blind.
2. **`type` and `status` are not mass-assignable.** This is the single most
   important line in `User`. Documented in the model so nobody "helpfully" adds
   them to `$fillable` later.
3. **Invalid transitions throw.** `pending → suspended` is refused because a new
   account is either approved or rejected; allowing the jump would let a mistake
   silently lock out a legitimate signup.
4. **`DELETED` is terminal.** An account with ledger history must never be
   resurrected (FR-024).
5. **AccountGate logs out suspended users.** A suspended account should not hold
   a working session; the gate invalidates and regenerates rather than merely
   denying the current request.
6. **AdminGate returns 404, not 403.** A freelancer has no business knowing an
   admin area exists at that path. 403 confirms it.
7. **Type and status are short strings, not native ENUMs.** Portability between
   SQLite (local) and MySQL/MariaDB (host), and no table rebuild to add a status
   later.

## Known gap carried forward

The `login` route currently returns a placeholder string. It exists because
Laravel's `auth` middleware redirects to the route named `login`, and without it
the middleware raised a 500 instead of redirecting — which is a worse failure
mode than a placeholder. **T-005 replaces it with the real auth flow.**

## Reproduction

```
php artisan migrate
./vendor/bin/pest
```

Expected: 108 passed (180 assertions). `composer audit` clean.

## What is NOT yet proven

- Real login/logout/password-reset behaviour (T-005)
- Rate limiting on auth endpoints (T-007)
- Session regeneration on login (T-005)
- That admin actions are audited (T-012 — `audit_logs` does not exist yet)
