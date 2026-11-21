# Milestones — Trust-Prime

> A milestone is a **vertical slice**: interface + server logic + data + tests.
> Only one milestone may be `in-progress`. A milestone is `done` only when its
> acceptance criteria pass **and** its evidence file exists on disk.

## Rules

1. Write the definition of done **before** starting.
2. If the work depends on an unresolved client decision, stop and ask.
3. Do not begin the next milestone because the previous one produced files.
4. Every `done` milestone must link a file under `.aidev/evidence/`.

### M1 — Foundation: money, users, branding

- **Status:** done
- **Requirements:** FR-030, FR-003, FR-024, FR-027, FR-035
- **Files:** scaffold, `Money` value object, `users` + `settings` migrations, `Brand` helper, base layout
- **Tests:** `MoneyTest` proves integer-cent arithmetic exactly; factory creates a pending user; branding change updates header, footer and `<title>`
- **Review:** human confirms no secrets committed and money is integer-only
- **Evidence:** .aidev/evidence/M1-verification.md
- **Acceptance:** app boots and reports versions, `php artisan test` green with a non-zero passing count, and money arithmetic is proven by unit test

### M2 — Accounts: auth and approval gates

- **Status:** done
- **Requirements:** FR-005, FR-006, FR-004, FR-024
- **Files:** auth controllers/views, `AccountGate`, `AdminGate`, rate limiters, admin user management
- **Tests:** register/login/logout/reset; reset token single-use; throttling; pending user blocked; poster denied `/admin`
- **Review:** human reviews auth and throttle policy
- **Evidence:** .aidev/evidence/M2-verification.md
- **Acceptance:** full account journey in the browser; both permitted and forbidden actions verified

### M3 — Freelancer identity: profile and portfolio

- **Status:** done
- **Requirements:** FR-007, FR-008
- **Files:** `freelancer_profiles`, `portfolios`, profile CRUD, public profile page
- **Tests:** profile persists; public page leaks no private field; A cannot edit B's portfolio
- **Review:** human reviews the public field allowlist
- **Evidence:** .aidev/evidence/M3-verification.md
- **Acceptance:** profile editable, publicly visible, leak-free

### M4 — Ledger, deposits and admin money controls

- **Status:** in-progress
- **Requirements:** FR-029, FR-018, FR-041, FR-038, FR-039, FR-040, FR-022, FR-028, FR-032
- **Files:** `wallets`, `ledger_entries`, `payment_methods`, `deposit_requests`, `LedgerService`, `WalletService`, admin wallet + payment method + deposit UI, poster funding page
- **Tests:** derived balance equals ledger sum and **no balance column exists**; ledger immutable; deposit request credits nothing until approved; admin approval credits the chosen amount exactly once; disabled method hidden and rejected; adjustment requires a reason; audit row per admin action; milestone sum validation
- **Review:** **human approves the ledger invariant and deposit-approval tests before proceeding**
- **Evidence:** .aidev/evidence/M4-verification.md
- **Acceptance:** a poster can see enabled methods, submit a deposit with proof, and after admin approval fund a job from that balance — with every step reconstructable from ledger rows

### M5 — Jobs and escrow funding

- **Status:** todo
- **Requirements:** FR-009, FR-010, FR-011, FR-001, FR-002
- **Files:** `jobs`, `escrows`, `EscrowService::fund`, landing page, search/filter
- **Tests:** funding atomic; double funding rejected; insufficient funds leaves nothing changed; unfunded job 404 publicly; search and category filter correct
- **Review:** human reviews the escrow invariant (`released + refunded ≤ amount`)
- **Evidence:** .aidev/evidence/M5-verification.md
- **Acceptance:** a funded job is publicly visible and unfunded jobs are invisible

### M6 — Applications, messaging, notifications

- **Status:** todo
- **Requirements:** FR-012, FR-013, FR-014, FR-015, FR-016
- **Files:** `applications`, `threads`, `messages`, `notifications`, mailables, cron queue
- **Tests:** duplicate application rejected; hire declines others; non-participant blocked from thread; one notification per recipient; email queued not inline
- **Review:** human reviews thread access rules and queue cron setup
- **Evidence:** .aidev/evidence/M6-verification.md
- **Acceptance:** radio silence from all users except the two participants; email actually arrives

### M7 — Delivery, release, refund, reporting

- **Status:** todo
- **Requirements:** FR-017, FR-021, FR-020→FR-032, FR-026, FR-025, FR-031
- **Files:** `MilestoneController`, `EscrowService::release/refund`, admin escrow + job + dashboard
- **Tests:** ordered release; idempotent release; partial refund leaves released milestones paid; dashboard totals reconcile
- **Review:** **human approves every money transition test**
- **Evidence:** .aidev/evidence/M7-verification.md
- **Acceptance:** money can only move in the permitted directions, exactly once

### M8 — KYC and withdrawals

- **Status:** todo
- **Requirements:** FR-020, FR-019, FR-023
- **Files:** `kyc_documents`, private disk storage, `withdrawals`, admin payout queue
- **Tests:** bad file rejected; KYC private to owner+admin; withdrawal capped at available; KYC gate enforced; payout idempotent; rejection restores held amount
- **Review:** **human reviews KYC storage location and access control**
- **Evidence:** .aidev/evidence/M8-verification.md
- **Acceptance:** no KYC document is reachable without authorisation

### M9 — Reviews: open submission and admin moderation

- **Status:** todo
- **Requirements:** FR-033, FR-034, FR-037
- **Files:** `reviews`, `review_flags`, review UI, admin review queue
- **Tests:** no-relationship review publishes; free-text name honoured; duplicate updates; self-review rejected; guest rejected; throttle works; admin can author with no user; hide changes average; audit per action
- **Review:** human reviews the abuse mitigations and accepted risk
- **Evidence:** .aidev/evidence/M9-verification.md
- **Acceptance:** reviews publish instantly and admin can remove them instantly

### M10 — Currency whitelist

- **Status:** todo
- **Requirements:** FR-036
- **Files:** `CurrencyPresenter`, switcher, FX cache, validation rule
- **Tests:** NGN rejected everywhere; conversion changes display only; stored rate reproduces the same figure
- **Review:** human confirms no NGN appears in any rendered surface
- **Evidence:** .aidev/evidence/M10-verification.md
- **Acceptance:** zero NGN anywhere; USD ledger untouched by display changes

### M11 — Security, accessibility, full journey

- **Status:** todo
- **Requirements:** NFR-002, NFR-005, NFR-006, all FRs
- **Files:** fixes only, Playwright specs, `security-review.md`
- **Tests:** full object-level matrix; axe scan; 320/768/1440 widths; complete end-to-end journey
- **Review:** **human reviews and accepts residual risk**
- **Evidence:** .aidev/evidence/M11-verification.md
- **Acceptance:** no open Critical/High finding; every journey passes at three widths

### M12 — Deployment

- **Status:** todo
- **Requirements:** NFR-008
- **Files:** runbook, cron config, `.env.example`, release checklist
- **Tests:** production smoke tests on the real host; queue drains via cron; restore tested
- **Review:** **human approves the release**
- **Evidence:** .aidev/evidence/M12-verification.md
- **Acceptance:** live site works, cron runs, rollback understood, human signed
