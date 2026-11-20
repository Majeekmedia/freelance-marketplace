---
project: Trust-Prime
profile: laravel-blade
checked_at: 2026-11-20
host_status: not yet provisioned
---

# Hosting capability sheet â€” Trust-Prime

> Derived from the requirements' deployment constraints and the owner's stated
> stack. Fields marked **VERIFY** are assumptions about shared cPanel hosting that
> must be confirmed on the real host before release. They do not block the build,
> but they block G8.

## Required capability

- **Runtime:** PHP 8.3
- **Framework:** Laravel 12
- **Database:** MySQL 8.0 (host may provide MariaDB 10.6 â€” engine must be confirmed)
- **Web server:** Apache 2.4
- **Hosting type:** shared cPanel
- **Shell access:** restricted SSH
- **Background jobs:** cron available; no queue worker daemon and no long-running processes
- **Frontend build:** built locally (XAMPP + Node), compiled assets uploaded
- **Storage:** to be confirmed â€” symlink support required for `storage:link`
- **Email:** external SMTP provider, configured by the site owner

## What the host must provide

| Capability | Required | Actual | Verified by |
|---|---|---|---|
| Runtime | PHP 8.3 | **VERIFY** | `php -v` on host |
| Database version | MySQL 8.0 or MariaDB 10.6 | **VERIFY** | `SELECT VERSION()` |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` | **VERIFY** | `php -m` on host |
| Cron | at least one 1-minute cron entry | **VERIFY** | cPanel Cron Jobs panel |
| Symlinks | supported | **VERIFY** | `php artisan storage:link` |
| Outbound HTTP | allowed (FX rate feed) | **VERIFY** | `curl` from host |
| Inbound webhooks | not required in release 1 | n/a | â€” |
| SSL | Let's Encrypt via AutoSSL | **VERIFY** | cPanel SSL/TLS Status |
| Disk quota | enough for KYC document uploads | **VERIFY** | cPanel Disk Usage |
| Max upload size | at least 5 MB | **VERIFY** | `php -i \| grep upload_max_filesize` |

## Local development environment

- **XAMPP** at `C:\xampp` â€” Apache + MySQL/MariaDB + PHP.
- Local PHP is **8.2.4** (`C:\xampp\php\php.exe`). The host targets **8.3**.
  **Gap:** local and production minor versions differ. Code must not rely on
  8.3-only syntax, or XAMPP must be updated. Recorded as a build constraint.
- Node is available locally for asset compilation (`npm run build`).
- Local queue can be run as a daemon; **production cannot**. Any queue-dependent
  feature must be proven to work with a cron-driven tick.

## Compatibility verdict

- **Every dependency has a reason:** yes â€” see `steering/tech-stack.md`
- **Anything the host cannot do:**
  - No long-running queue worker â†’ queue must be drained by cron.
  - No websockets â†’ messaging is polling-based (already required by FR-014).
  - Possible no symlink support â†’ uploaded files may need a direct public path
    with access control, or storage outside webroot served through a controller.

## Build and deploy location

- **Frontend assets built:** locally with Node, then `public/build` uploaded.
- **Migrations run:** over restricted SSH by the developer, after a verified backup.
- **Secrets stored:** `.env` outside the web root, never in the repository.
- **Cron entries required:**
  1. `php artisan schedule:run` â€” every minute
  2. `php artisan queue:work --stop-when-empty --max-time=50` â€” every minute

## Incompatibilities and alternatives

| Requirement | Host reality | Alternative |
|---|---|---|
| Queue worker daemon | cron only | `queue:work --stop-when-empty` on a 1-minute cron; accept up to 60s email delay |
| Real-time chat | no websockets | polling every 15s (already the accepted design) |
| PHP 8.3 locally | XAMPP has 8.2.4 | keep code 8.2-compatible, or upgrade XAMPP before relying on 8.3 features |
| Storage symlink | unconfirmed | serve uploads through a controller with an auth check (also stronger for KYC files) |

> Compatibility is part of the definition of done. The **VERIFY** rows are honest
> unknowns, not passes â€” G8 will not accept the release until they are confirmed
> on the real host.
