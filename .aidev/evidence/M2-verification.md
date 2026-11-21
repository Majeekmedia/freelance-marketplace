# M2 Verification — Accounts: auth and approval gates

- **Requirement IDs:** FR-005 (auth + reset), FR-006 (brute-force resistance), FR-004 (approval gates), FR-024 (account management), FR-003 (registration type)
- **Environment:** local — PHP 8.3.30, Laravel 12.73.0, Pest 4.4.1; verified in Chromium
- **Date:** 2026-11-20
- **Task-level evidence:** `.aidev/evidence/M2-T005-auth.md`, `.aidev/evidence/M2-T006-approval.md`

## Tally

```
passed: 36 · failed: 0 · blocked: 0
```

Full suite at the time M2 closed: **165 passed**. This file records the
milestone-level verification; the task files carry the detail.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Register as a freelancer | integration + browser | account created, status pending | pending, type freelancer | passed |
| 2 | Register as a poster | integration | type poster | correct | passed |
| 3 | Cannot register as an admin | integration | rejected | type not settable from input | passed |
| 4 | Login with correct credentials | integration + browser | redirect to dashboard | dashboard | passed |
| 5 | Login with wrong password | integration + browser | generic error | byte-identical to unknown email | passed |
| 6 | Login with unknown email | integration | generic error | identical message | passed |
| 7 | Suspended account cannot log in | integration | refused | same generic message | passed |
| 8 | Brute-force throttling | integration | 429 after N attempts | throttled per email+IP | passed |
| 9 | Logout invalidates session | integration + browser | signed out | session invalidated | passed |
| 10 | Password reset link is single use | integration | second use refused | refused | passed |
| 11 | Expired or invalid token rejected | integration | refused | refused | passed |
| 12 | Reset form never reveals whether an email exists | integration | same message | identical | passed |
| 13 | Pending user can log in but cannot act | browser | can browse, blocked on action | confirmed | passed |
| 14 | Pending user sees an explanation, not an empty page | browser | explains approval | confirmed | passed |
| 15 | Poster denied `/admin` | integration + browser | 404 | 404, not 403 | passed |
| 16 | Admin approves a pending account | integration + browser | status approved | approved, actions unlocked | passed |
| 17 | Admin rejects an account | integration | status rejected | rejected, cannot log in | passed |
| 18 | Admin suspends an active account | integration + browser | status suspended, logged out | confirmed | passed |
| 19 | Admin reinstates a suspended account | integration | status approved | confirmed | passed |
| 20 | Cannot hard-delete an account with history | integration | refused | refused | passed |

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `165 passed` at M2 close |
| Task evidence — auth | `.aidev/evidence/M2-T005-auth.md` |
| Task evidence — approval flow | `.aidev/evidence/M2-T006-approval.md` |
| Browser — pending state | dashboard explains "awaiting approval", actions blocked |
| Browser — wrong password vs unknown email | two identical messages, byte-for-byte |
| Browser — `/admin` as a poster | generic Not Found page |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| `GET /admin/users` | poster | denied | 404 | passed |
| `GET /dashboard` | pending poster | allowed, limited | allowed with explanation | passed |
| `POST` a gated action | pending account | denied | blocked by `AccountGate` | passed |
| Login | suspended account | denied | generic credentials error | passed |
| Reuse a reset token | any | denied | refused | passed |

## Notes on deliberate design

- **Approval gates actions, not login.** A pending user must be able to sign in
  and edit the profile an admin needs to review. Blocking login would make the
  approval queue impossible to satisfy.
- **`/admin` returns 404, not 403.** A 403 confirms the area exists to an account
  with no business knowing.
- **Throttling is per email+IP.** It resists both a single-account attack and a
  spray across accounts from one source, without a permanent lockout an attacker
  could aim at a victim.
- **Every failure message is identical.** Wrong password, unknown email,
  suspended account and non-admin all produce the same response, so no form can
  be used to enumerate accounts or roles.
