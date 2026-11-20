# T-005 Verification — Authentication

- **Requirement IDs:** FR-005 (auth + session), FR-006 (brute-force resistance), FR-003 (registration type)
- **Environment:** local — PHP 8.3.30, Laravel 12.73.0, Pest 4.4.1; verified in Chromium
- **Date:** 2026-11-20

## Tally

```
passed: 144 · failed: 0 · blocked: 0
```

## Automated results

| # | Test | Expected | Actual | Status |
|---|---|---|---|---|
| 1 | Register as freelancer → pending | type freelancer, status pending | exact | passed |
| 2 | Register as poster → pending | type poster, status pending | exact | passed |
| 3 | **Register with `type=admin` rejected** | validation error, no user | rejected, no user created | passed |
| 4 | Weak/mismatched password rejected | errors, no user | rejected | passed |
| 5 | Duplicate email rejected | errors | rejected | passed |
| 6 | Password stored hashed | not plaintext | `Hash::check` true | passed |
| 7 | Approved user can sign in | redirect dashboard | exact | passed |
| 8 | **Pending user can sign in** (A7) | redirect dashboard | exact | passed |
| 9 | **Wrong password and unknown email give identical errors** | same string | identical | passed |
| 10 | **Suspended account refused, reason hidden** | error, guest | error, guest | passed |
| 11 | **Throttling blocks even a correct password** | error after 5 fails | blocked | passed |
| 12 | Logout invalidates session | redirect home, guest | exact | passed |
| 13 | **Reset request identical for known/unknown email** | same status message | identical | passed |
| 14 | **Invalid reset token rejected, password unchanged** | error | rejected | passed |
| 15 | **Valid reset token works exactly once** | reset then token dead | one use only | passed |

## Browser verification (real Chromium, real form submissions)

The automated tests prove logic. A browser proves the flow a user actually walks.

| Step | Result |
|---|---|
| `GET /register` | 200, 7,873 bytes, form rendered with both account types |
| Fill form, click "Create account" | redirected to `/dashboard` |
| Dashboard content | "Signed in as Browser Test User", type **Freelancer**, status **Pending approval** |
| Pending notice visible | yes — and actions are explained as blocked |
| Click "Sign out" | redirected to `/` |
| Login with wrong password | "These credentials do not match our records." |
| Login with unknown email | **"These credentials do not match our records."** |
| Messages byte-identical | **true** |

That last row is the important one: account enumeration is closed in the running
application, not only in the test suite.

## Security properties proven

1. **No privilege escalation at registration.** `type` is validated against
   `UserType::registrable()` (freelancer, poster only), so `type=admin` fails
   validation before the controller. Combined with `type`/`status` not being
   mass-assignable (T-003), there are two independent barriers.
2. **No account enumeration on login.** A wrong password and an unknown email
   produce the same message. Status (suspended) is also checked *after*
   credentials, so it cannot leak either.
3. **Throttling that doesn't create a denial-of-service.** Limit is per
   email+IP, so one attacker cannot lock out a victim globally, and the window
   expires rather than locking permanently.
4. **One-time, expiring reset tokens.** Laravel's broker deletes the token on
   success; the test proves reuse fails and the password stays at the new value.
5. **Session regenerated on login and on logout**, with token invalidation, so a
   pre-login session id cannot be reused.

## Design decisions recorded

1. **No auth scaffolding package.** Laravel Breeze/Fortify were not installed.
   The steering file already chose to harden our own auth rather than inherit a
   scaffold we then have to audit — this keeps every line of the flow visible.
2. **Pending users can log in (assumption A7).** They land on a dashboard that
   explains their state. Gating login instead would have forced admins to approve
   profiles they could not see.
3. **Throttle applied twice** — inside `LoginController` (per email+IP) and at
   the route (`throttle:10,1`). Route-level protection survives a future
   controller that forgets.
4. **Reset-request response is deliberately uniform.** The message says "If that
   email address is registered…" because anything else is an enumeration oracle.
5. **Password rule:** minimum 8 with letters and numbers. Not length-only, not
   complexity theatre — enough to stop trivially guessable passwords without
   pushing users to written-down passwords.

## Notes

- The `/login` placeholder from T-003 is now replaced with the real controller.
- `APP_URL` is unset in `.env`, so reset emails would use the default. That is a
  **T-030 deployment concern** (SPF/DKIM and correct base URL must be set on the
  host), not a defect in this task.
- No email is actually sent in tests — `MAIL_MAILER=array` in `phpunit.xml`.

## Reproduction

```
php artisan migrate
./vendor/bin/pest
```

Expected: 144 passed (275 assertions).

## What is NOT yet proven

- Rate limiting on the *reset* endpoint under real load (route throttle is in
  place; behaviour verified only for login)
- Session regeneration on privilege change (approval) — that path arrives in T-008
  when admin approves an account
- Real email delivery (T-018)
