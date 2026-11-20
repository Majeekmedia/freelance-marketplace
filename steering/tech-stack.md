---
inclusion: always
---

# Approved stack â€” Trust-Prime

Must match `hosting-capability.md`. If they disagree, the hosting sheet wins.

## Runtime and framework

- Runtime: **PHP 8.3** on host Â· **PHP 8.2.4** locally in XAMPP
- Framework: **Laravel 12**
- Database: **MySQL 8.0** (host may be MariaDB 10.6 â€” confirm before using
  MariaDB-incompatible syntax)
- Frontend: **Blade + Tailwind CSS + Alpine.js**
- Build: Vite, built **locally**, `public/build` uploaded

## Hard version constraint

Local PHP is **8.2.4**; the host targets **8.3**. **Write 8.2-compatible code.**
No 8.3-only syntax, no `json_validate()`, no typed class constants, no
`#[Override]`. If 8.3 syntax is genuinely needed, upgrade XAMPP first and record it.

## Approved libraries

| Library | Why |
|---|---|
| Laravel Breeze (Blade) | minimal auth scaffolding we then harden ourselves |
| Tailwind CSS | utility CSS, no runtime cost, builds to static assets |
| Alpine.js | small interactive islands without a SPA |
| Playwright | end-to-end journeys |

## Forbidden

| Not allowed | Reason |
|---|---|
| Any SPA framework (React/Vue/Inertia) | host has no Node runtime; one artefact only |
| Redis | not assumed on shared cPanel â€” cache and queue use `database` |
| Websockets / Pusher / Reverb | no long-lived process on shared hosting |
| `float`/`double` for money | rounding errors corrupt balances |
| A `balance` column on any table | balances are derived from the ledger (FR-041) |
| Elasticsearch/Meilisearch | no extra service available; MySQL FULLTEXT instead |
| Stripe/Paystack SDKs | no gateway in release 1 â€” deposits are manual |
| `INTERVAL`/window-function syntax that MariaDB 10.6 lacks | engine not confirmed |

## Money rules (non-negotiable)

- All amounts are **integer cents** in a `BIGINT`. Nothing else.
- One `Money` value object owns arithmetic. Controllers never do maths on money.
- Only `LedgerService` writes to `ledger_entries`. Nothing else.
- Every ledger row has a `reason` of at least 5 characters.
- Ledger rows are **never** updated or deleted.

## Commands

```bash
composer install
php artisan migrate
php artisan test
npm run build          # local only; upload public/build
vendor/bin/pint --test
npx playwright test
php artisan schedule:run          # cron, every minute
php artisan queue:work --stop-when-empty --max-time=50   # cron, every minute
```
