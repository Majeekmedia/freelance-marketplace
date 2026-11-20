# M4 Verification — T-011b Admin payment methods

- **Requirement IDs:** FR-038 (a–e)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium for rendering; `curl` with a real session/CSRF for route behaviour
- **Date:** 2026-11-21

## Tally

```
passed: 21 · failed: 0 · blocked: 0
```

Full suite: **305 passed (670 assertions)**. T-011b contributes 21 tests.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Create a bank transfer method | integration | saved, complete | complete, crypto fields nulled | passed |
| 2 | Create multiple crypto methods on different networks | integration | 3 methods | 3, no bank fields leak | passed |
| 3 | Crypto method with no network rejected | integration | error | `crypto_network` error, 0 rows | passed |
| 4 | Crypto method with no address rejected | integration | error | `crypto_address` error | passed |
| 5 | Bank method with no account number rejected | integration | error | `account_number` error | passed |
| 6 | Wallet address with quotes/whitespace rejected | integration | error | rejected (injection shape) | passed |
| 7 | Error message names the missing field | integration | mentions "network" | yes | passed |
| 8 | Disabling hides from `enabled()` scope (b) | integration + HTTP | scope count 0 | 0 | passed |
| 9 | Re-enabling restores it (c) | integration + HTTP | enabled again | enabled | passed |
| 10 | Enabled methods ordered by sort order | integration | admin order | `['First','Second']` | passed |
| 11 | Soft delete preserves the row (e) | integration | trashed, data intact | address still present | passed |
| 12 | Soft-deleted method never shown to posters | integration | scope 0 | 0 | passed |
| 13 | Poster blocked from admin payment methods | integration | 404 | 404 | passed |
| 14 | Guest blocked | integration | redirect | `/login` | passed |
| 15 | Freelancer blocked from toggling | integration | 404, unchanged | 404, still enabled | passed |
| 16 | Bank snapshot shape | unit | bank keys only | correct | passed |
| 17 | Crypto snapshot shape | unit | crypto keys only | correct | passed |
| 18 | Snapshot unaffected by later edit | unit | frozen value | frozen | passed |
| 19 | Type switch clears irrelevant fields | integration | bank fields nulled | nulled | passed |
| 20 | Admin list renders methods | integration | 200 + label | 200 | passed |
| 21 | Incomplete method is flagged, not hidden | integration | "Incomplete" badge | shown | passed |

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `305 passed (670 assertions)` |
| Command output — T-011b | `./vendor/bin/pest tests/Feature/PaymentMethodTest.php` → `21 passed (71 assertions)` |
| HTTP verification script | `tools/verify-payment-methods.php` |
| HTTP result — login | `POST /login → 302` |
| HTTP result — admin page | `GET /admin/payment-methods → 200` |
| HTTP result — toggle off | `POST toggle → 302`, `enabled=false`, `enabled scope count: 0` |
| HTTP result — toggle on | restored `enabled=true` |
| HTTP result — method guard | `GET /toggle → 405` (POST-only route refuses GET) |
| Browser — empty state | `/admin/payment-methods` at 390px, no overflow |
| Browser — created method | listed as "Crypto transfer · USDT on TRC-20", badge "Enabled" |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| `GET /admin/payment-methods` | poster | denied | 404 | passed |
| `GET /admin/payment-methods` | guest | denied | redirect to `/login` | passed |
| `POST /toggle` | freelancer | denied, no change | 404, still enabled | passed |
| `GET /toggle` | admin | refused | 405 | passed |
| Submit crypto method without network | admin | rejected | error, 0 rows | passed |
| Submit address containing `"` and spaces | admin | rejected | error, 0 rows | passed |

## Defects found and fixed during this task

### 1. `type` was dropped by mass assignment

`PaymentMethod::$fillable` deliberately excludes `type` (it decides which fields
are required, so it must not be shaped purely by request input). The consequence
was `new PaymentMethod($attrs)` leaving `->type` null and
`clearIrrelevantFields()` throwing `Call to a member function on null`. Three
tests failed. Fixed by assigning `type` explicitly from the validated request in
both `store()` and `update()`.

### 2. `clearIrrelevantFields()` threw on a null type

Even with the assignment fixed, a row read back with a type no longer present in
the enum would crash here. The method now returns early rather than throwing
`Call to a member function irrelevantFields() on null`, because an unhelpful
null error is worse than an incomplete record flagged in the admin list.

### 3. Hardcoded brand name in a form placeholder

`NoHardcodedBrandTest` failed and named the file:

```
resources/views\admin\payment-methods\_form.blade.php
```

The placeholder read `GTBank — Trust-Prime` and an account-name placeholder read
`Trust-Prime Ltd`. Both replaced with neutral examples. This is FR-035 working as
intended — the brand is data, and a template must not contain it.

## Notes on deliberate design

- **Type-specific validation, not one wide form.** `required_if` rules mean a
  crypto method cannot be saved without a network, and the message names the
  field the admin missed rather than saying "invalid".
- **Irrelevant fields are nulled on save.** A method that changes type cannot
  keep a stale crypto address, which would show a poster two contradictory sets
  of instructions.
- **Disabled groups are `disabled`, not just hidden.** A disabled input is never
  submitted, so a browser restoring form state after Back cannot inject a bank
  name into a crypto method.
- **Soft delete, never hard.** Deposit requests reference the method for history;
  a hard delete would leave them pointing at nothing (FR-038e).
- **`toggle` is POST, not GET.** A GET could be fired by a link prefetcher or
  crawler and silently disable a payment method. Verified: `GET /toggle` → 405.

## Tooling note

Automated clicks on the submit button repeatedly reported interception by a
fieldset that measurements showed was 141px away and not overlapping. Submitting
the form directly succeeded, and `curl` confirmed the routes. Recorded honestly:
the click failures were a browser-automation scroll race, not a page defect —
the geometry was measured rather than assumed before concluding that.
