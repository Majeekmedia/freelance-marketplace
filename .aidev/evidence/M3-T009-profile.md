# M3 Verification — Milestone 3

- **Requirement IDs:** FR-007 (freelancer profile), FR-008 (portfolio), NFR-009 (no contact exposure before engagement)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium (VS Code integrated browser, real mouse clicks via accessibility refs)
- **Date:** 2026-11-20

## Tally

```
passed: 21 · failed: 0 · blocked: 0
```

Full suite: **186 passed (375 assertions)** across 8 test files.
T-009 contributes 21 of those (17 original + 4 added after browser review).

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Save a profile as an approved freelancer | integration | persists headline, skills, rate | `hourly_rate_cents` = 4550 (int, not float) | passed |
| 2 | Skills string is parsed and de-duplicated | unit | `"Figma, Laravel, Figma"` → 2 entries | `['Figma', 'Laravel']` | passed |
| 3 | Skills list is capped | integration | ≤20 entries | 40 submitted → 20 stored | passed |
| 4 | Non-numeric hourly rate rejected | integration | validation error | `hourly_rate` error | passed |
| 5 | Pending freelancer cannot publish | integration | forbidden | 403 | passed |
| 6 | Pending freelancer can save private draft | integration | allowed, `is_public` false | redirect, `is_public` = false | passed |
| 7 | Public profile renders for a visitor | e2e (browser) | 200, shows headline | 200, headline + 2 portfolio items | passed |
| 8 | Public profile hides email | api | no address in bytes | 10,246 bytes, `freelancer@trust-prime` absent | passed |
| 9 | Private profile denied to visitor | integration | forbidden | 403 | passed |
| 10 | Pending freelancer denied to visitor | integration | forbidden | 403 | passed |
| 11 | Owner sees own private profile | integration | 200 | 200 | passed |
| 12 | Directory lists only approved + public | integration | 1 of 3 shown | Public shown, Private and Pending absent | passed |
| 13 | Add a portfolio item | integration | created | 1 row, `user_id` = owner | passed |
| 14 | `javascript:` URL rejected | integration | validation error | `external_url` error, 0 rows created | passed |
| 15 | `javascript:` URL rejected in a real browser | e2e (browser) | inline alert, no row | "The external url field must be a valid URL." | passed |
| 16 | Freelancer A cannot update B's item | integration | forbidden | 403 | passed |
| 17 | Freelancer A cannot update B's item — forged request | e2e (browser) | 403, record unchanged | 403; title still "Belongs to other", `user_id` 3 | passed |
| 18 | Freelancer A cannot delete B's item | integration | forbidden, row survives | 403, row present | passed |
| 19 | Owner can update and delete own item | integration + browser | persists | "Portfolio item updated." then row removed | passed |
| 20 | Poster cannot add portfolio items | integration | forbidden | 403 | passed |
| 21 | Pending freelancer cannot add portfolio items | integration | forbidden | 403 | passed |

### Reachability tests (added after browser review)

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 22 | Dashboard links to profile and portfolio | integration | both routes present in HTML | present | passed |
| 23 | Poster is not offered freelancer links | integration | portfolio route absent | absent | passed |
| 24 | Every owned item exposes an edit control | integration | form action to `portfolio.update` | present | passed |
| 25 | No edit control for a foreign item | integration | route absent from HTML | absent | passed |

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `Tests: 186 passed (375 assertions)` |
| Command output — T-009 only | `./vendor/bin/pest tests/Feature/FreelancerProfileTest.php` → `17 passed (38 assertions)` |
| Browser — public profile | `http://127.0.0.1:8199/freelancers/1` → 200, headline "Product designer for regulated fintech" |
| Browser — directory | `http://127.0.0.1:8199/freelancers` → 200, "Avery Lindqvist · $65.00 /hr" |
| Byte check | `Invoke-WebRequest` → 10,246 bytes; no email, no password string |
| Forged cross-user PUT | `fetch('/portfolio/3', {_method:'PUT', title:'HIJACKED BY A'})` → **403** |
| Database after forged request | `Portfolio::find(3)` → `title=Belongs to other`, `user_id=3` |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| Direct GET `/freelancers/{private}` | anonymous | denied | 403 | passed |
| Direct GET `/freelancers/{pending}` | anonymous | denied | 403 | passed |
| Direct PUT `/portfolio/{other id}` | approved freelancer | denied, no data written | 403, record byte-identical | passed |
| Direct DELETE `/portfolio/{other id}` | approved freelancer | denied, row survives | 403, row survives | passed |
| Direct POST `/portfolio` | poster | denied | 403 | passed |
| Direct POST `/portfolio` | pending freelancer | denied | 403 | passed |
| Public profile HTML | anonymous | no email/phone/balance | none present | passed |

## Defects found and fixed during this milestone

1. **`authorize()` was undefined on every controller (500 instead of 403).**
   Laravel 12's base `App\Http\Controllers\Controller` is an empty class; it does
   not include `AuthorizesRequests`. Every policy call threw
   `Call to undefined method ProfileController::authorize()`, so denials looked
   like crashes. Fixed by adding `AuthorizesRequests` and `ValidatesRequests` to
   the base controller. This is why 11 tests failed on first run with `500`
   where `403` was expected — the tests caught it, a manual click-through would
   probably have been mistaken for a server bug.

2. **Profile and portfolio pages were unreachable from the UI.**
   Routes, controllers, policies and views all existed and passed their tests,
   but the layout had no links and the dashboard said the features would "arrive
   in milestone M3 onward". Only browser review found this — HTTP tests assert
   that a page responds, not that a human can get to it. Added nav links,
   dashboard CTAs, and inline `<details>` editing for portfolio items, plus four
   regression tests.

3. **The test suite was silently wiping the development database** (see L-008).
   `phpunit.xml` ships `DB_CONNECTION`/`DB_DATABASE` commented out and
   `RefreshDatabase` applies to every Feature test, so the suite migrated the
   real sqlite file. Demo accounts vanished mid-session. Fixed by pinning
   `DB_DATABASE=:memory:`, and a blocking G4 gate check now prevents recurrence
   (proven in both directions: fails when commented out, passes when restored).

## Reproduction steps for defects 1 and 2

```
1. php artisan serve --port=8199
2. Sign in as freelancer@trust-prime.test / DemoPass123!
3. Before fix: dashboard offers only "Dashboard"; /profile and /portfolio are
   reachable only by typing the URL.
4. Before fix: any policy denial renders 500 "Call to undefined method
   ...::authorize()" instead of 403.
```
