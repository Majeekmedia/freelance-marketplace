# Tasks — Trust-Prime

> Each task is small enough to review, traces to at least one `FR-`, and states
> how it will be verified. **Files existing is not completion.**
> Only one task may be `in-progress`.

## Legend

| Field | Meaning |
|---|---|
| `Status` | `todo` · `in-progress` · `done` · `blocked` |
| `Requirements` | `FR-###` ids this task satisfies |
| `Files` | what is expected to change (keeps the diff honest) |
| `Verify` | the exact command/check that proves it works |
| `Evidence` | path to the artefact that recorded the result |
| `Depends` | task ids that must be done first |
| `Milestone` | which vertical slice this belongs to |

---

### T-001 — Application foundation

- **Status:** todo
- **Milestone:** M1
- **Requirements:** FR-005
- **Files:** project scaffold, `.env.example`, health route, `config/`
- **Depends:** —
- **Verify:** `php artisan about` reports Laravel + PHP versions; health route returns 200; `php artisan test` runs 0 tests without error
- **Evidence:**
- **Risk:** low

### T-002 — Money value object and float guard

- **Status:** todo
- **Milestone:** M1
- **Requirements:** FR-030
- **Files:** `app/Domain/Money.php`, `tests/Unit/MoneyTest.php`
- **Depends:** T-001
- **Verify:** unit test proves `0.10 × 10 == 1.00` exactly, and that a float input is rejected; `grep -rn "float\|double" app/Domain` returns nothing money-related
- **Evidence:**
- **Risk:** medium

### T-003 — Users, roles, statuses migration + model

- **Status:** todo
- **Milestone:** M1
- **Requirements:** FR-003, FR-024
- **Files:** `users` migration, `User` model, `UserType`/`UserStatus` enums, factory
- **Depends:** T-001
- **Verify:** migration runs on a disposable DB; factory creates a pending freelancer; attempting `type = admin` via registration validation fails
- **Evidence:**
- **Risk:** medium

### T-004 — Settings and branding

- **Status:** todo
- **Milestone:** M1
- **Requirements:** FR-027, FR-035
- **Files:** `settings` migration, `Setting` model, `Brand` helper, admin settings controller + view
- **Depends:** T-003
- **Verify:** changing the site name changes the header, footer, `<title>` and a dispatched test email; uploading a logo replaces the wordmark; a `.txt` upload is rejected
- **Evidence:**
- **Risk:** medium

### T-005 — Auth: register, login, logout, reset

- **Status:** todo
- **Milestone:** M2
- **Requirements:** FR-005
- **Files:** auth controllers, routes, Blade views, `password_reset_tokens`
- **Depends:** T-003
- **Verify:** register → login → logout in the browser; a reset token works once and expires; a suspended account cannot log in
- **Evidence:**
- **Risk:** medium

### T-006 — Account approval gate

- **Status:** todo
- **Milestone:** M2
- **Requirements:** FR-004
- **Files:** `AccountGate` middleware, `AdminGate` middleware, pending-approval UI
- **Depends:** T-005
- **Verify:** a pending freelancer's application POST creates no row and returns the pending message; after admin approval the same POST succeeds; a poster hitting `/admin` is denied
- **Evidence:**
- **Risk:** medium

### T-007 — Rate limiting and generic auth errors

- **Status:** todo
- **Milestone:** M2
- **Requirements:** FR-006
- **Files:** `RateLimiter` config, login/register/reset throttles, error copy
- **Depends:** T-005
- **Verify:** 6 rapid logins return 429; a valid user can still log in after the window; the failure message is identical for unknown email and wrong password
- **Evidence:**
- **Risk:** medium

### T-008 — Admin account management

- **Status:** todo
- **Milestone:** M2
- **Requirements:** FR-024
- **Files:** `Admin\UserController`, views, audit wiring
- **Depends:** T-006
- **Verify:** approve/suspend/reinstate each change `status` and block or allow login accordingly; deleting a user with ledger rows is refused
- **Evidence:**
- **Risk:** high — account state controls access

### T-009 — Freelancer profile and portfolio

- **Status:** todo
- **Milestone:** M3
- **Requirements:** FR-007, FR-008
- **Files:** `freelancer_profiles`, `portfolios` migrations + models, `ProfileController`, public profile page
- **Depends:** T-006
- **Verify:** profile edit persists and appears publicly; public page never renders email, phone or balance; freelancer A cannot edit B's portfolio item (403/404)
- **Evidence:**
- **Risk:** medium

### T-010 — Categories and job creation (draft)

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-009
- **Files:** `categories`, `jobs`, `milestones` migrations + models, `JobManageController`
- **Depends:** T-003
- **Verify:** a poster creates a draft job; a draft job returns 404 publicly; posting `currency = NGN` is rejected
- **Evidence:**
- **Risk:** medium

### T-011 — Wallet and ledger (immutable, derived balance)

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-029, FR-018, FR-041
- **Files:** `wallets`, `ledger_entries` migrations, `LedgerService`, `WalletService`, `WalletController`
- **Depends:** T-002, T-003
- **Verify:** appending credits and debits yields a derived balance equal to the sum; **no table has a `balance` column** (`grep -rn "balance" database/migrations` shows none); no endpoint can update or delete a ledger row; a user cannot read another user's wallet; reconciliation test passes after a random sequence of movements
- **Evidence:**
- **Risk:** **high — money**

### T-011b — Admin payment methods (bank + crypto, enable/disable)

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-038
- **Files:** `payment_methods` migration, `PaymentMethod` model, admin CRUD + toggle views
- **Depends:** T-011
- **Verify:** multiple crypto methods on different networks can be created; disabling one removes it from the poster funding page; a crypto method missing network or address is rejected at save; soft-deleting a method with requests preserves history
- **Evidence:**
- **Risk:** medium

### T-011c — Poster deposit request with proof upload

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-039
- **Files:** `deposit_requests` migration, `DepositController`, proof upload to private disk, funding page
- **Depends:** T-011b
- **Verify:** a submitted request is `pending` and the balance is still 0; a disabled method reference is rejected; a non-image proof is rejected; `method_snapshot` is populated; the proof is unreadable by another user
- **Evidence:**
- **Risk:** medium

### T-011d — Admin deposit approval queue

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-040
- **Files:** `Admin\DepositController`, approval views, ledger wiring
- **Depends:** T-011c
- **Verify:** approving creates exactly one `deposit_credit` row for the **chosen** amount and the balance rises by exactly that; approving twice is rejected; rejecting credits nothing and records the reason; each action writes one audit row
- **Evidence:**
- **Risk:** **high — money**

### T-012 — Admin wallet credit/debit with reason + audit

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-022, FR-028
- **Files:** `Admin\WalletController`, `AuditLogger`, `audit_logs` migration
- **Depends:** T-011
- **Verify:** a credit without a reason is rejected; a credit with a reason creates exactly one ledger row and one audit row; audit rows cannot be edited or deleted through the app
- **Evidence:**
- **Risk:** **high — money**

### T-013 — Milestone validation (sum must equal budget)

- **Status:** todo
- **Milestone:** M4
- **Requirements:** FR-032
- **Files:** `MilestoneService`, job create/edit form with repeatable milestone rows
- **Depends:** T-010
- **Verify:** milestones totalling 250 against a 300 budget are rejected; matching totals are accepted; a single-amount job creates exactly one milestone
- **Evidence:**
- **Risk:** medium

### T-014 — Escrow funding (atomic)

- **Status:** todo
- **Milestone:** M5
- **Requirements:** FR-009, FR-010, FR-011
- **Files:** `escrows` migration, `EscrowService::fund`, fund button + badge
- **Depends:** T-011, T-013
- **Verify:** funding with sufficient balance moves the exact amount and publishes the job; funding twice is rejected; insufficient funds creates no escrow and leaves the balance unchanged
- **Evidence:**
- **Risk:** **high — money**

### T-015 — Public landing page, search and filter

- **Status:** todo
- **Milestone:** M5
- **Requirements:** FR-001, FR-002
- **Files:** public `JobController`, `jobs` FULLTEXT index, filter/sort UI
- **Depends:** T-014
- **Verify:** only funded+published jobs appear; category filter and text search return correct sets; an unfunded job URL returns 404
- **Evidence:**
- **Risk:** medium

### T-016 — Applications: apply, list, hire, decline

- **Status:** todo
- **Milestone:** M6
- **Requirements:** FR-012, FR-013
- **Files:** `applications` migration, `ApplicationController`, `ApplicationService`, poster applicant list
- **Depends:** T-015
- **Verify:** duplicate application rejected; applying to a non-published job rejected; poster A cannot see poster B's applicants; hiring declines all others and closes the job
- **Evidence:**
- **Risk:** medium

### T-017 — Messaging threads (polling)

- **Status:** todo
- **Milestone:** M6
- **Requirements:** FR-014
- **Files:** `threads`, `messages` migrations, `ThreadController`, poll endpoint, thread UI
- **Depends:** T-016
- **Verify:** only the two participants can read/post (third user gets 403/404); unread count decrements on open; polling returns only new messages
- **Evidence:**
- **Risk:** medium

### T-018 — Notifications and queued email

- **Status:** todo
- **Milestone:** M6
- **Requirements:** FR-015, FR-016
- **Files:** `notifications` migration, `NotificationService`, mailables, `queue:work` cron wiring
- **Depends:** T-017
- **Verify:** an event creates exactly one notification for the recipient and none for the actor; the email job is queued not sent inline; failure retries without blocking the request
- **Evidence:**
- **Risk:** medium

### T-019 — Milestone delivery and acceptance

- **Status:** todo
- **Milestone:** M7
- **Requirements:** FR-017
- **Files:** `MilestoneController`, `MilestoneService` transitions
- **Depends:** T-016
- **Verify:** the hired freelancer can mark delivery; a non-hired user cannot; the poster cannot accept an undelivered milestone; accepting moves no money
- **Evidence:**
- **Risk:** medium

### T-020 — Admin escrow release (per milestone, ordered, idempotent)

- **Status:** todo
- **Milestone:** M7
- **Requirements:** FR-021, FR-032
- **Files:** `Admin\EscrowController`, `EscrowService::release`, row locking
- **Depends:** T-012, T-019
- **Verify:** release credits exactly the milestone amount and records ledger + audit; releasing out of order is rejected; releasing twice is rejected; two concurrent releases produce one ledger row (row lock test)
- **Evidence:**
- **Risk:** **high — money**

### T-021 — Admin escrow refund (unreleased remainder)

- **Status:** todo
- **Milestone:** M7
- **Requirements:** FR-026
- **Files:** `EscrowService::refund`
- **Depends:** T-020
- **Verify:** partial refund returns only the unreleased remainder and leaves released milestones paid; a fully released job cannot be refunded; double refund rejected
- **Evidence:**
- **Risk:** **high — money**

### T-022 — Admin job management and reporting

- **Status:** todo
- **Milestone:** M7
- **Requirements:** FR-025, FR-031
- **Files:** `Admin\JobController`, `Admin\DashboardController`, `ReportService`
- **Depends:** T-020
- **Verify:** archiving a funded job routes through the refund path; dashboard escrow total equals the sum of open escrows and reconciles with the ledger
- **Evidence:**
- **Risk:** medium

### T-023 — KYC upload and review

- **Status:** todo
- **Milestone:** M8
- **Requirements:** FR-020
- **Files:** `kyc_documents` migration, `KycController`, private disk, admin KYC queue
- **Depends:** T-009
- **Verify:** a `.exe` or oversized file is rejected; the document is reachable only by owner and admin; a direct public URL returns 404
- **Evidence:**
- **Risk:** **high — sensitive data**

### T-024 — Withdrawals and admin payout

- **Status:** todo
- **Milestone:** M8
- **Requirements:** FR-019, FR-023
- **Depends:** T-011, T-023
- **Files:** `withdrawals` migration, `WithdrawalService`, `WalletController`, `Admin\WithdrawalController`
- **Verify:** requesting more than available is rejected; requesting twice cannot exceed the balance; withdrawal without approved KYC is rejected; a paid withdrawal cannot be paid again; a rejected one restores exactly the held amount
- **Evidence:**
- **Risk:** **high — money**

### T-025 — Reviews: submit, update, flag

- **Status:** todo
- **Milestone:** M9
- **Requirements:** FR-033, FR-037
- **Files:** `reviews`, `review_flags` migrations, `ReviewController`, `ReviewService`, public profile review list
- **Depends:** T-009
- **Verify:** a signed-in user with no job relationship can review and it publishes immediately; display name is the free text submitted; a second review updates rather than duplicates; self-review rejected; guest rejected; exceeding the rate returns 429
- **Evidence:**
- **Risk:** medium

### T-026 — Admin review authoring and moderation

- **Status:** todo
- **Milestone:** M9
- **Requirements:** FR-034
- **Files:** `Admin\ReviewController`, review queue view
- **Depends:** T-025
- **Verify:** an admin-authored review with **no linked user** publishes and shows the typed name; hide removes it from the list and the average; unhide restores both; edit changes the average; every action writes one audit row; delete hides it everywhere but is reconstructable from audit
- **Evidence:**
- **Risk:** medium

### T-027 — Currency whitelist and display conversion

- **Status:** todo
- **Milestone:** M10
- **Requirements:** FR-036, FR-030
- **Files:** `CurrencyPresenter`, currency switcher, `fx_rates` cache, validation rule
- **Depends:** T-014
- **Verify:** `NGN` is rejected by validation on every endpoint; switching display currency changes only rendered values, never a ledger amount; the same record shows the same converted figure across reloads (stored rate)
- **Evidence:**
- **Risk:** medium

### T-028 — Security hardening pass

- **Status:** todo
- **Milestone:** M11
- **Requirements:** FR-005, FR-006, FR-014, FR-019, FR-020, FR-021, FR-022, FR-023, FR-026, FR-034
- **Files:** CSRF config, headers, upload validation, escaping review, `security-review.md`
- **Depends:** T-020, T-024, T-026
- **Verify:** object-level matrix passes for every policy; no public response contains email/phone/balance/KYC path; dependency audit has no unresolved High
- **Evidence:**
- **Risk:** **high — gates release**

### T-029 — Full journey end-to-end test

- **Status:** todo
- **Milestone:** M11
- **Requirements:** FR-001, FR-004, FR-009, FR-012, FR-013, FR-014, FR-017, FR-019, FR-021, FR-023, FR-033
- **Files:** `tests/Browser/` Playwright specs
- **Depends:** T-028
- **Verify:** register → approve → fund → publish → apply → hire → message → deliver → accept → release → withdraw → pay → review, all in a real browser, plus the forbidden-path checks
- **Evidence:**
- **Risk:** medium

### T-030 — Deployment preparation and smoke tests

- **Status:** todo
- **Milestone:** M12
- **Requirements:** FR-009, FR-021, FR-023, FR-027, FR-035
- **Files:** deploy runbook, `.env.example`, cron entries, release checklist
- **Depends:** T-029
- **Verify:** the app boots on the real host; cron drains the queue; assets load; smoke tests pass; a restore has been tested
- **Evidence:**
- **Risk:** **high — production**

---

## Task dependency order

```
T-001 ─┬─ T-002 ─┐
       ├─ T-003 ─┼─ T-005 ─ T-006 ─┬─ T-008
       │         │                ├─ T-009 ─┬─ T-023 ─ T-024
       │         │                │         └─ T-025 ─ T-026
       │         │                └─ T-007
       │         ├─ T-004
       │         └─ T-010 ─ T-013 ─┐
       │                           │
       └─ T-011 ─┬─ T-012 ─────────┼─ T-014 ─ T-015 ─ T-016 ─┬─ T-017 ─ T-018
                 │                 │                        └─ T-019 ─ T-020 ─┬─ T-021
                 └─────────────────┘                                          └─ T-022
                                                        T-014 ─ T-027
                                                        T-020,T-024,T-026 ─ T-028 ─ T-029 ─ T-030
```

**Critical path:** T-001 → T-003 → T-005 → T-006 → T-009 → T-023 → T-024 → T-028 → T-029 → T-030
(the money and KYC chain carries the most risk; it is deliberately early)
