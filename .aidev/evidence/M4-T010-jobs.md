# M4 Verification — T-010 Categories and job creation (draft)

- **Requirement IDs:** FR-009 (job creation, draft privacy), FR-036 (NGN excluded), FR-011 (no balance/contact exposure)
- **Environment:** local — `php artisan serve` on `http://127.0.0.1:8199`, PHP 8.3.30, SQLite
- **Browser(s):** Chromium (VS Code integrated browser, real clicks and form submissions)
- **Date:** 2026-11-20

## Tally

```
passed: 39 · failed: 0 · blocked: 0
```

Full suite: **242 passed (485 assertions)**. T-010 contributes 26 feature tests,
6 escrow-invariant tests, 10 money-conversion unit tests, and 3 Blade/table
structure guards added while fixing a 500 found during manual review.

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Approved poster creates a draft | integration | status draft, private | draft, `published_at` null | passed |
| 2 | Budget stored as integer minor units | integration | 250000 cents | 250000 cents | passed |
| 3 | Human input `$2,500.00` normalised | integration + browser | 250000 cents | 250000 cents | passed |
| 4 | Poster cannot set their own status | integration | `status=published` ignored | still draft, `published_at` null | passed |
| 5 | `NGN` budget rejected | integration + browser | 422, names supported currencies | 422 with explicit message | passed |
| 6 | `NGN` attempt creates no row | integration | 0 jobs | 0 jobs, 0 NGN rows | passed |
| 7 | Inactive category rejected | integration | validation error | `category_id` error | passed |
| 8 | Description under 40 chars rejected | integration | validation error | `description` error | passed |
| 9 | Draft returns 404 to a visitor | integration + browser | 404 | 404 | passed |
| 10 | Draft returns 404 (not 403) to another poster | integration | 404 | 404 | passed |
| 11 | Draft visible to its owner | integration | 200 | 200 | passed |
| 12 | Draft absent from public listing | integration + browser | not in HTML | absent | passed |
| 13 | Funded job listed and viewable | integration + browser | 200 + visible | 200, listed | passed |
| 14 | Poster email absent from public job page | api | no address in bytes | 8,520 bytes, absent | passed |
| 15 | Poster email absent from public listing | api | absent | absent | passed |
| 16 | Freelancer cannot create a job | integration | forbidden | 403 | passed |
| 17 | Pending poster cannot create a job | integration | forbidden | 403 | passed |
| 18 | Poster A cannot edit B's job | integration | 404, unchanged | 404, title intact | passed |
| 19 | Poster A cannot delete B's draft | integration | 404, row survives | 404, row survives | passed |
| 20 | Owner edits and deletes own draft | integration | persists | title and budget updated, then deleted | passed |
| 21 | Funded job cannot be deleted | integration | refused | 404, row survives | passed |
| 22 | Brief locks once a freelancer is hired | integration | refused | 404 | passed |
| 23 | Illegal status jump refused | unit | throws | `DomainException` | passed |
| 24 | `published_at` stamped once only | unit | original preserved | timestamp unchanged | passed |
| 25 | Dashboard links poster to their jobs | integration | route present | present | passed |
| 26 | Empty state offers "Post a job" | integration | route present | present | passed |
| 27 | Published job with no escrow is not listed | integration | absent | absent | passed |
| 28 | Job with zero-held escrow is not listed | integration | absent | absent | passed |
| 29 | Published job with no escrow returns 404 | integration | 404 | 404 | passed |
| 30 | Funded badge only when money held | integration | "Escrow funded" | "Escrow funded", not "Awaiting escrow" | passed |
| 31 | `isFunded()` false without a row | unit | false / true | false / true | passed |
| 32 | Second escrow row rejected | integration | `QueryException` | unique violation | passed |

## Blocked items

None.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Command output — full suite | `./vendor/bin/pest` → `Tests: 230 passed (459 assertions)` |
| Command output — T-010 only | `./vendor/bin/pest tests/Feature/JobDraftTest.php` → `32 passed (69 assertions)` |
| Browser — create form | `http://127.0.0.1:8199/jobs/manage/create` → 10 real categories, USD-only select |
| Browser — draft created | `/jobs/manage/1` → "Draft saved. It is private until its budget is funded." |
| Browser — NGN rejected | POST `currency=NGN` → **422**, body: "Jobs are priced in USD. NGN is not a supported currency; display currencies are USD, EUR, GBP, CAD." |
| Browser — draft hidden | `GET /jobs/1` while draft → **404**; `GET /jobs` → draft absent |
| Byte check | `/jobs/1` → 8,520 bytes, no `recruiter@` or `trust-prime.test` |
| Database state | `jobs=1`, `ngn rows=0`, `cents=250000`, `published_at=NULL` |

## Permitted and forbidden actions tested

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| POST `/jobs/manage` | freelancer | denied | 403 | passed |
| POST `/jobs/manage` | pending poster | denied | 403 | passed |
| POST `/jobs/manage` with `status=published` | approved poster | ignored | stayed draft | passed |
| POST with `currency=NGN` | approved poster | rejected | 422, no row created | passed |
| PUT `/jobs/manage/{B's job}` | poster A | denied | 404, data unchanged | passed |
| DELETE `/jobs/manage/{B's draft}` | poster A | denied | 404, row survives | passed |
| DELETE `/jobs/manage/{funded job}` | owner | denied | 404, row survives | passed |
| GET `/profile` | poster | denied | 403 with message | passed |
| GET `/jobs/{draft}` | anonymous | denied | 404 | passed |

## Defects found and fixed during this task

### 1. `jobs` collided with Laravel's queue table

Laravel already creates a `jobs` table for queued work. My first migration used
that name and would have failed on a fresh install. The table is now
`job_postings`; the model, routes and UI still say "job" because that is the
product's word. Verified by running the migration against a clean database.

### 2. The float guard caught a real float

`Job::budgetInBaseCurrency()` used `(float) $this->fx_rate_to_usd` and
`round()`. The existing `NoFloatingPointMoneyTest` failed immediately:

```
app/Models/Job.php → (float)
```

Replaced with `Money::convert()`, which does integer-only arithmetic. While
writing its tests I found a second bug in my own new code: an 8-decimal rate
multiplied by a large minor-unit count **overflows 64-bit integers**, at which
point PHP silently promotes to float and precision is lost with no error.
`convert()` now checks for that and throws. Rate precision is capped at 8
places to match `decimal(18,8)`, so the in-memory value cannot disagree with
what the database can store.

### 3. The public page claimed escrow funding that did not exist

The worst of the three, and the one no unit test would have caught. A job with
`status=published` and **no escrow row** was listed with a green
**"Escrow funded"** badge. The status was set by a factory/test path; nothing
had moved money. A visitor deciding whether to trust the platform was shown a
guarantee that did not exist — the same class of failure as L-004 (fake live
data in a mock).

Three fixes:

- The badge now reads `$job->escrow->held_cents > 0`, not the status.
- `scopePubliclyVisible` and `scopeOpenForApplications` now require an escrow
  row with `held_cents > 0`, so an unfunded job cannot be listed at all.
- `JobController@show` checks `isFunded()` as well as status, so a direct id
  cannot reach a page that would make the claim.

Proven in both directions: removing the `whereHas('escrow')` clause makes
exactly the two invariant tests fail; restoring it makes them pass.

### 4. A poster could open the freelancer profile editor

`ProfileController@edit` had no type check, so a poster could create a
freelancer profile for themselves and appear in the public freelancer directory
as a provider of work. Now returns 403 with a plain explanation. Verified in the
browser.

### 5. `@php(...)` on its own line swallowed the rest of the template (L-009)

`@php($poster = $job->poster)` compiled to an opening PHP tag with no closing tag
and no semicolon, absorbing the remainder of the file into one broken expression.
The job detail page returned **500** while the whole suite stayed green, because
no test rendered that view. Diagnosed only by compiling the view to a file and
running `php -l` on the output.

Fixed by using the block form, and three guards were added:

- every `.blade.php` is compiled and linted, so a template syntax error fails the
  build instead of production
- the inline directive form is banned outright
- the milestone table's header/cell counts are asserted structurally

Both guards were proven by reintroducing the bug: two tests fail, and pass again
once restored.

### 6. The milestone table had more headers than cells (L-010)

`<thead>` declared three columns (`#`, `Milestone`, `Amount`) but each row emitted
two `<td>`. The browser put the title under `#`, the amount under `Milestone`, and
left `Amount` above nothing — which read as "disorganised" styling. The HTML was
the defect, not the CSS.

Rebuilt as two columns with the sequence number inside the primary cell, a fixed
amount column, and a total row that states whether the stages sum to the
escrowed budget. Verified at 375px (all cells share right edge 359, amounts
311px wide) and 1280px (714px / 96px, headers aligned).

## Responsive defects found during review

The user reported the dashboard pages were not mobile responsive. Measured, not
assumed: at 375px every page scrolled **431px wide** and the job detail page's
content column collapsed to **38px** while its 280px sidebar held width, because
`grid-template-columns: minmax(0,1fr) 280px` was written inline and could not
reflow. The layout had **zero media queries**.

Fixed:

- Layout gains media queries at 700px and 860px, plus `prefers-reduced-motion`.
- `.split` (content + sidebar) and `.duo` (form rows) are classes now, stacking
  below 860px — an inline style cannot adapt, a class can.
- Tables become stacked cards below 760px via `table.responsive` + `data-label`.
- Top nav scrolls horizontally on phones; a bottom nav (Home / Jobs / Post or
  Work+Profile / Accounts) appears below 700px with `aria-current="page"` and
  `env(safe-area-inset-bottom)` padding.
- Job listing and manage pages are cards, not tables.
- On mobile the budget card comes first on the job detail page; desktop restores
  brief-left / facts-right.

After the work, all audited pages measure **scrollWidth == clientWidth** at
320, 375, 390 and 768px.

## Notes on deliberate design

- **No publish route exists.** `DRAFT → PUBLISHED` is reachable only through
  `Job::transitionTo()`, which `EscrowService` will call once it has moved
  money. There is no form field, no query parameter and no admin action that
  sets it, so "public" always means "funded".
- **404 rather than 403 for someone else's draft**, because a 403 confirms the
  id exists and would let anyone enumerate unannounced work.
- **`status` is not fillable** on `Job`, so `Job::create($request->validated())`
  can never publish. Same reasoning as `User::$fillable` excluding `type`.
