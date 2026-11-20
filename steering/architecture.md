---
inclusion: always
---

# Architecture — Trust-Prime

## Folder structure

```
app/
├── Domain/                 # framework-free business logic
│   ├── Money.php           # integer-cents value object
│   ├── Currency.php        # whitelist: USD, EUR, GBP, CAD (never NGN)
│   └── Ledger/
│       ├── LedgerService.php
│       ├── WalletService.php
│       └── EntryKind.php
├── Services/               # orchestration, transactions
│   ├── EscrowService.php
│   ├── MilestoneService.php
│   ├── DepositService.php
│   ├── WithdrawalService.php
│   ├── ReviewService.php
│   ├── AccountService.php
│   └── AuditLogger.php
├── Http/
│   ├── Controllers/        # thin: validate, authorise, delegate, respond
│   ├── Middleware/         # AccountGate, AdminGate
│   ├── Requests/           # all validation lives here
│   └── Policies/           # object-level ownership
├── Models/
└── Support/
    ├── Brand.php           # site name + logo accessor
    └── CurrencyPresenter.php
database/migrations/
resources/
├── views/
│   ├── layouts/
│   ├── components/         # badge, money, status pill, empty state
│   ├── public/
│   ├── freelancer/
│   ├── poster/
│   └── admin/
└── css/ js/
tests/
├── Unit/  Feature/  Browser/
```

## Boundaries

| Layer | Owns | Must not |
|---|---|---|
| Controller | validation hand-off, authorisation, response | contain money maths or DB transactions |
| Service | business rules, transactions, ordering | render HTML |
| `Domain\Money` | arithmetic and rounding | touch the database |
| `LedgerService` | **the only** writer of money | know about HTTP |
| Model | persistence and relationships | hold business rules |
| Blade component | presentation | compute totals or format money ad-hoc |
| View | layout | query the database |

## Money is never touched outside three places

1. `Domain\Money` — arithmetic
2. `Services\LedgerService` — writes rows
3. `Support\CurrencyPresenter` — display conversion only

Any other file doing `+` or `*` on an amount is a review failure.

## Naming

| Thing | Convention | Example |
|---|---|---|
| Classes | PascalCase, singular, role-suffixed | `EscrowService`, `JobPolicy` |
| Methods | camelCase, verb first | `releaseMilestone()` |
| DB tables | snake_case, plural | `deposit_requests` |
| Enum cases | SCREAMING_SNAKE | `EntryKind::ESCROW_HOLD` |
| Blade components | kebab-case | `<x-money :cents="..." />` |
| Tests | mirror the subject | `EscrowServiceTest` |

## Error handling

- **Expected** (validation, 403, 404, insufficient funds): handled, returned as a
  clear user-facing message; logged at `info`.
- **Unexpected**: caught at the boundary, logged with context, **generic** message
  to the user. No stack traces, no SQL, no file paths.
- **Never** swallow an exception silently. No empty `catch`.
- Money operations either fully succeed or leave nothing behind — always inside a
  DB transaction with `SELECT ... FOR UPDATE` on the row that guards the invariant.

## Idempotency

Every state transition that moves money is guarded by a database constraint, not
only an `if`:

| Transition | Guard |
|---|---|
| Fund a job | `escrows.job_id UNIQUE` |
| Release a milestone | milestone `status` check inside a locked transaction |
| Approve a deposit | `deposit_requests.status` check inside a locked transaction |
| Pay a withdrawal | `withdrawals.status` check inside a locked transaction |

## Two cycles to keep clean

- **A ledger row is never the source of truth for a state.** The entity's status is.
- **A derived balance is never written.** It is always computed from rows.
