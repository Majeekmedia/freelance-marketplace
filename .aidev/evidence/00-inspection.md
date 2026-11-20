# Inspection — SkillLink (freelance marketplace)

> Read-only baseline. Produced **before** any application file was created.
> This is a **greenfield** project: there is no code to inspect, so the baseline
> records that fact and the assumptions it creates, rather than pretending an
> inspection happened.

## Stack

- Nothing installed yet.
- Target profile: `laravel-blade` (PHP 8.3, Laravel, MySQL, Blade/Tailwind).
- Profile chosen by the developer, **not yet confirmed against a real host**.

## Entry points

- None. The directory contains only the ADS scaffold
  (`requirements.md`, `design.md`, `tasks.md`, `milestones.md`,
  `hosting-capability.md`, `steering/`, `.aidev/`).

## Data

- No database exists.
- No migrations, seeders or factories.
- Connection values unknown — the hosting capability sheet is unfilled.

## Tests

- No test framework installed.
- Expected once scaffolded: `php artisan test`, `npx playwright test`.
- Baseline test count: **0**. Everything from here is new.

## Deployment

- No repository, no CI config, no Dockerfile, no deploy script.
- Hosting is **undetermined**. This is the largest single unknown and it gates G3:
  a marketplace with wallets, webhooks and background jobs has very different
  hosting needs from a brochure site.

## Risks already visible

- **Money custody.** Admin can fund wallets, adjust balances and pay freelancers
  on behalf of posters. That means the platform holds other people's money — a
  regulated activity in most jurisdictions. Someone must be the merchant of
  record, in writing.
- **Escrow ambiguity.** If a poster pays and the freelancer never delivers, who
  refunds? This is a business rule that blocks the payment design.
- **Withdrawal compliance.** Payouts to bank/mobile-money need KYC and a payout
  provider; neither is chosen.
- **Chat scope.** In-app messaging ranges from a simple inbox to real-time
  presence with attachments. Unbounded, it can consume the entire budget.
- **Admin power.** "Admin can edit any balance" is also a fraud and audit surface.
  Every such action needs an immutable trail.

## Uncertainties

1. Which payment provider, and does it support **payouts** as well as collection?
2. Which market and currency — single or multi-currency?
3. Does the platform take a commission, and from whom?
4. Is money held in escrow, or passed through on release?
5. Real-time chat required, or asynchronous inbox acceptable for release 1?
6. Who performs KYC on freelancers and posters?
7. Hosting budget and type — shared cPanel cannot run long-lived websockets or a
   queue worker.

> None of these are guessed in `requirements.md`. They are recorded as open
> questions that block design, and `aidev gate G1` will fail until they are
> resolved or explicitly deferred.
