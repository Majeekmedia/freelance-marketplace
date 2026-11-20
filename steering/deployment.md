---
inclusion: always
---

# Deployment — Trust-Prime

Derived from `hosting-capability.md`. Never assume root access, a process manager
or a binary that has not been confirmed on the host.

## Target

- Hosting type: **shared cPanel**
- Web server: **Apache 2.4**
- Document root: **`public/`**
- PHP: **8.3** on host (local XAMPP is 8.2.4 — see the compatibility note)
- Deploy method: upload over restricted SSH, or cPanel File Manager

## Build

- `npm run build` runs **locally** — Node is not assumed on the host.
- Upload `public/build` with the release.
- `.env` lives **outside** `public/`, at the application root.
- Minimum env: `APP_ENV=production`, `APP_DEBUG=false`,
  `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`,
  `CACHE_STORE=database`.

## Cron entries (both required)

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

Without the second, **no email is ever sent** — the queue silently fills.

## Migrations

- Run by the developer over restricted SSH.
- **Backup taken and verified before every migrate.**
- Never run `migrate:fresh` or `db:wipe` against production.
- Destructive migrations (drop/rename column) require written approval and a
  forward-fix plan, because a code rollback does not undo schema.

## Storage

- `php artisan storage:link` — **verify it works**. If it fails, serve uploads
  through a controller (required for KYC and deposit proofs anyway).
- Upload directories must be writable but not executable.

## Smoke tests after every deploy

| # | Test | Expected |
|---|---|---|
| 1 | Home page | 200, funded jobs visible |
| 2 | Registration | creates a pending account |
| 3 | Login as admin | dashboard loads |
| 4 | Unfunded job URL | 404 |
| 5 | `/admin` as poster | denied |
| 6 | Queue drained | a test email arrives |
| 7 | Logs | no new errors |

A deploy that "worked" but filled the log with errors is a failed deploy.

## Rollback

- **Code:** restore the previous release directory.
- **Database:** a code rollback does **not** undo migrations. Ship a forward fix or a
  tested reverse migration — never improvise on production.
- **Files:** uploads live outside the release directory so they survive a code
  rollback; confirm this before the first deploy.
- **External effects:** approved deposits and paid withdrawals cannot be un-sent —
  compensating entries only.

## Maintenance

- Dependency review: monthly (`composer outdated`, `npm audit`).
- Backup + **tested restore**: weekly, with the date recorded.
- Log review: weekly.
- Ledger reconciliation: monthly — total credits must equal total approved deposits.
- Access review: quarterly — who still needs admin?
