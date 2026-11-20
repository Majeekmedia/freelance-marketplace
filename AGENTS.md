# Trust-Prime

This project uses the **Agentic Development System**. Read these before acting.

## Working principle

Give the agent enough context to act well. Give it boundaries so it cannot act
carelessly. Require evidence before accepting its work.

## Read first

- `requirements.md` — the source of truth (must be **approved** before implementation)
- `design.md` — the technical approach and rejected alternatives
- `tasks.md` — the ordered work with verification steps
- `milestones.md` — the current slice and its definition of done
- `hosting-capability.md` — hard deployment constraints
- `steering/` — durable project rules (stack, architecture, security, testing, deployment, permissions)

## Stack profile: Laravel + Blade + Tailwind (shared cPanel / VPS)

---
inclusion: always
---

# Profile: Laravel + Blade + Tailwind

## Approved stack

- PHP 8.3 (confirm on host with `php -v`)
- Laravel (confirm exact compatible version)
- MySQL 8 / MariaDB 10.6 (confirm engine — they differ)
- Blade, Tailwind CSS, Alpine.js
- Frontend assets built with Vite **locally or in CI** if Node is absent on host

## Rules

- Do not introduce a second backend runtime — the host may have no process manager.
- Validate all untrusted input with Form Requests on the server.
- Authorise with policies or middleware **on every protected route**.
- Recalculate money server-side. Never trust a submitted price.
- Use `DECIMAL(12,2)` for money; never `float`/`double`.
- Queue work must survive a shared host: cron + `queue:work --stop-when-empty`.
- Migrations run with a backup taken first, and are never destructive without approval.
- Never commit `.env`. Keep it outside the web root.
- Add tests for security-sensitive changes.

## Commands

```bash
composer install
php artisan migrate
php artisan test
npm run build          # run locally/CI, then upload public/build
vendor/bin/pint --test
npx playwright test
```

## Shared-host specifics

- Web root must point at `public/`.
- `php artisan storage:link` may fail without symlink support — verify early.
- Confirm required extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (if images).
- Confirm outbound HTTPS is allowed, or the payment provider will never be reached.
- Confirm inbound webhook route is reachable (many hosts block unexpected paths).

## Testing

- Unit: totals, discounts, stock, order-state transitions.
- Feature/HTTP: authn, authz, validation, object-level access.
- Playwright: critical customer and admin journeys only.


## Non-negotiables

1. Do not invent client decisions. If something is unknown, ask.
2. Do not write application code while `requirements.md` is unapproved.
3. Only one milestone may be `in-progress`.
4. A milestone is done only when its acceptance criteria pass **and**
   `.aidev/evidence/Mn-verification.md` exists with a real tally.
5. A skipped or blocked test is **not** a pass.
6. The server is the authority for money, permissions and payment state.
7. Never commit secrets. Never fill in approval fields — a human approves.
8. Before editing, state: outcome, allowed actions, prohibited actions, evidence.

## Check your position before working

```bash
aidev status     # where are we in the lifecycle
aidev gate       # what is blocking
aidev next       # the next smallest safe task
```
