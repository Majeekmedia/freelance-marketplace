# M1 Verification — Foundation: money, users, branding

- **Requirement IDs:** FR-030, FR-003, FR-024, FR-027, FR-035
- **Environment:** local — Windows, PHP 8.2.4 (XAMPP), Node 22.14, Composer 2.7.2
- **Browser(s):** Chromium (Playwright, bundled)
- **Date:** 2026-11-20

## Tally

```
passed: 33 · failed: 0 · blocked: 0
```

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | Design prototype renders | browser | all sections present | hero, board, escrow, footer all in a11y tree | passed |
| 2 | No horizontal overflow | browser | none at 8 widths | none at 320/375/640/768/1024/1100/1280/1440 | passed |
| 3 | No invisible content (opacity < 0.99) | browser | 0 hidden | 0 at all widths | passed |
| 4 | Tap targets ≥ 32px | browser | 0 undersized | 0 at all widths | passed |
| 5 | Every job card has an amount | browser | 6/6 | 6/6 | passed |
| 6 | Every job card has a badge | browser | 6/6 | 6/6 | passed |
| 7 | Badges pair icon + word (colour-independent) | manual | all | "Funded" ×5, "Awaiting release" ×1, each with inline SVG | passed |
| 8 | Webfonts load | browser | Instrument Serif + Sans | both `loaded` | passed |
| 9 | No gradient / purple default | static scan | 0 | 0 occurrences of "gradient" | passed |
| 10 | No NGN in rendered page | static scan | 0 | 0 | passed |
| 11 | HTML tags balanced | static scan | balanced | div/span balanced, 1 h1 | passed |
| 12 | Stray/undefined CSS classes | static scan | 0 | none | passed |
| 13 | **Job grid is 3-up at desktop** | browser | 3 columns ≥1280px | 3 cols at 1280 and 1440, 6 cards in 2 rows | passed |
| 14 | **Job grid degrades correctly** | browser | 2 cols tablet, 1 col mobile | 2 cols at 768–1100, 1 col at 320–640 | passed |
| 15 | **Human images load** | browser | all `naturalWidth > 0` | 11/11 loaded (128×128 each) | passed |
| 16 | **Headline replaced** | static scan | "Work that's already paid for" absent | now "Hire with certainty. Get paid without chasing." | passed |
| 17 | **Hero has expanded content** | browser | >6 child elements | 8 elements: eyebrow, h1, lede, search, actions, note, proof row, stats | passed |
| 18 | **Hero contains real human faces** | browser | ≥2 human photos | 5 in facepile/payout + 6 poster avatars = 11 | passed |
| 19 | **Hero uses one bold photograph, not an activity feed** | browser | `.hero-figure` present, `.payout`/`.receipts` absent | figure present; old panels return false | passed |
| 20 | **Hero photo loads** | browser | naturalWidth > 0 | 1000×1500 natural, rendered 461×530 | passed |
| 21 | **No region-specific or placeholder names** | static scan | 0 | "Adaeze", "Lagos", "Kola" all absent from rendered text | passed |
| 22 | **Hero figure visible at every width** | browser | non-zero box | 265×225 (320px) → 461×530 (1440px) | passed |
| 23 | **Hero image follows copy on mobile** | browser | image top > copy top | 320px: copy 115 / image 1142 · 768px: 115 / 724 | passed |
| 24 | **Hamburger visible on mobile only** | browser | display none ≥721px | visible 375/320, hidden at 1280 | passed |
| 25 | **Desktop nav visible ≥721px** | browser | display flex | 5 links rendered | passed |
| 26 | **Menu opens on real mouse click** | browser | aria-expanded=true | `true`, drawer 455px tall, body scroll-locked | passed |
| 27 | **Escape closes and restores focus** | browser | closed + focus on trigger | closed, `focusOnToggle: true` | passed |
| 28 | **Menu closes after tapping a link** | browser | closed | closed | passed |
| 29 | **No overflow with menu open at 320px** | browser | none | none; menu right edge within viewport | passed |
| 30 | **Menu links are ≥44px tap targets** | browser | all ≥44px | 49/49/49/49/49/50/50 | passed |
| 31 | **Framework installs with no advisories** | CLI | `composer audit` clean | "No security vulnerability advisories found" | passed |
| 32 | **Application reports its version** | CLI | Laravel 12.73.0 | `php artisan --version` → 12.73.0 | passed |
| 33 | **Baseline test suite passes** | CLI | ≥1 passing | 2 passed (2 assertions) | passed |

## Blocked items

| Test | Blocker | Owner | Impact |
|---|---|---|---|
| Laravel framework install | **resolved** — see below | Developer | was blocking T-001 and all server-side work |

### Resolved during this milestone

**Original blocker:** `composer create-project laravel/laravel` selected Laravel
13.11.0 (requires PHP ≥ 8.3), and the first attempts hit a Windows file lock on
`vendor/composer/tmp-*`.

**Resolution, and a materially better decision than the original plan:**

| Attempt | Version | PHP | Advisories |
|---|---|---|---|
| 1 | 13.11.0 | ≥8.3 | n/a — discarded |
| 2 | 11.57.1 | ^8.2 | **5 advisories** (all affect `<12.71.0`) |
| **3 (final)** | **12.73.0** | **^8.2** | **0** |

The Laravel 11 pin was chosen to stay PHP 8.2-compatible. `composer audit` showed
that Laravel 11 sits entirely inside the affected range of five advisories and is
out of security support. **Laravel 12.73.0 carries the same `php ^8.2`
constraint and has zero advisories** — so the compatibility argument for 11 was
false, and the security argument against it was decisive.

Also discovered: `php` on PATH is **Laravel Herd 8.3.30**, not XAMPP's 8.2.4.
The host targets 8.3, so local and production now match exactly.

**Verification:** `composer audit` → "No security vulnerability advisories found."
`php artisan --version` → Laravel Framework 12.73.0. `php artisan test` → 2 passed.

## What this milestone actually proves — and what it does not

**Proves:** the landing-page design is real, renders responsively, leaks nothing,
uses no AI-default aesthetic, and every status is legible without colour.

**Does not prove:** any business rule. There is no `Money` class, no `users` table,
no branding table yet — those tasks (T-001…T-004) are **blocked** on the framework
install. The tally above counts design-prototype verification only; it does **not**
claim FR-030 (money) or FR-003 (users) are satisfied.

> M1 is therefore **not** done. It is `in-progress` with 2 blockers named above.
> Marking it done would be exactly the false completion this system exists to prevent.

## Evidence artefacts

| Artefact | Path |
|---|---|
| Prototype | `prototype/landing.html` |
| Structural validation output | this file, tests 9–12 |
| Browser measurement output | this file, tests 1–8 |

## Reproduction

```
open prototype/landing.html in a browser
resize to 320 / 375 / 768 / 1024 / 1440
```

## Design decisions recorded during this milestone

1. **Headline replaced.** "Work that's already paid for" was rejected by the owner
   as too passive. Replaced with a two-clause line that names both sides of the
   marketplace: **"Hire with certainty. Get paid without chasing."** The second
   clause is set in italic forest to carry the reward.
2. **Hero expanded from 5 to 8 elements.** Added a real search field, a social-proof
   row (facepile + aggregate rating), and a **single bold photograph** on the right.
   The hero demonstrates the product rather than describing it.
3. **Static activity feed removed.** A "Released in the last hour" panel with hardcoded
   receipts implied live data that does not exist in a prototype. Replaced with one
   editorial portrait: a freelancer working on their phone, with a single
   "Escrow funded" tag. **This is the rule: never fake live data in a design mock.**
   When the real feed is built (M7), it returns as real data.
4. **Region-neutral naming.** "Adaeze N. · Lagos" and "Kola Roasters" were removed.
   Trust-Prime is a US-headquartered platform open to users worldwide, so demo copy
   must not imply a regional bias. Poster names are now international and neutral
   (Northwind Labs, Stayhub, Ember Coffee Co., Mira Stores, Vantage, Ferrite).
5. **Card is fully clickable** via a stretched link on the title (`::after` inset 0),
   so the tap target is the whole card while the accessible name stays the title.
6. **No animated balance counters.** A number counting up implies change when
   nothing changed — misleading on a financial screen.
7. **Depth via offset, not opacity.** A receipt at 0.72 opacity reads as *disabled*
   rather than *behind*. Replaced with scale + translate.
8. **Status = icon + word, never colour alone.** "Awaiting release" and "Paid"
   differ by hundreds of dollars; they must be distinguishable without colour.
9. **Hero image panel moves below the copy below 980px,** so the person and the
   payout are the last thing seen on a phone rather than delaying the headline.

## Navigation — required before more pages exist

The landing page is the first of several public pages, so the header must scale.

**Desktop nav (≥721px):** Browse jobs · Freelancers · How escrow works · About us · Contact

**Mobile (<721px):** the same five links collapse into a hamburger drawer, plus an
"Account" group holding Sign in and Post a job.

### Why a drawer rather than just hiding links

Hiding `.nav` on mobile (the previous behaviour) would have orphaned About, Contact
and a dedicated Browse Jobs page — reachable only by scrolling or guessing URLs.
A menu with no entries is worse than no menu.

### Behaviour implemented

| Behaviour | Implementation |
|---|---|
| Toggle | real `<button>`, 40×40px, icon swaps menu↔close |
| State exposed | `aria-expanded`, `aria-controls`, `aria-label` changes to "Close menu" |
| Drawer | `data-open` attribute toggles `display` |
| Scroll lock | `body.menu-open` sets `overflow:hidden` |
| Close on link tap | delegated listener on the drawer |
| Close on Escape | returns focus to the trigger |
| Close on resize | `matchMedia('(min-width: 721px)')` listener, so a rotated phone or
widened window never leaves a stale open menu |

### Constraint for later pages

Every new public page must be added to **both** the desktop `.nav` and the mobile
drawer. Adding only one is a common and invisible defect — the page becomes
unreachable for half the audience. The `Browse jobs` entry is intended to become a
dedicated `/jobs` page rather than an anchor once routing exists.
