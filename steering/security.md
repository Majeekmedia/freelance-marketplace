---
inclusion: always
---

# Security rules — Trust-Prime

## Always

- Enforce authorisation **server-side** on every protected route. Two middleware
  (`AccountGate`, `AdminGate`) plus a `Policy` per model.
- Validate all untrusted input in a `FormRequest`. Never trust a submitted amount,
  status, currency or user id.
- CSRF on every state-changing request.
- Escape all output with `{{ }}`. Raw `{!! !!}` requires a written justification
  and a security review note.
- Parameterised queries or Eloquent only. No string-built SQL.
- Verify object ownership before returning any record.
- Rate-limit login, register, password reset, review submission and deposit requests.
- Regenerate the session on login **and** on any status/privilege change.
- Record an audit row for every admin money or account action, with a reason.

## Never

- Rely on hidden UI for access control.
- Store money as float, or as a `balance` column.
- Log passwords, tokens, full payout details, KYC paths or session ids.
- Return stack traces, SQL or file paths to a user.
- Let a user approve their own deposit, release their own escrow, or review themselves.
- Make permanent account lockout possible (a victim could be locked out by an attacker).
- Write `fixed+retested` on a finding without actually re-running the test.

## Object-level access — the checks that matter here

| Record | Rule |
|---|---|
| `jobs` | poster owns it; public only if `published` **and** funded |
| `applications` | freelancer owns it, or the job's poster |
| `threads` | exactly the two participants |
| `wallets` / `ledger_entries` | owner only; admin may read, never write via UI |
| `withdrawals` | owner creates and views; admin processes |
| `kyc_documents` | owner **or** admin — **never** public, never a direct URL |
| `deposit_requests` (proof) | requester **or** admin |
| `reviews` | anyone may read; author edits own; admin moderates |

## Sensitive file storage

| File | Location | Access |
|---|---|---|
| KYC documents | `storage/app/private/kyc` | streamed via controller, owner or admin only |
| Deposit proofs | `storage/app/private/deposits` | streamed via controller, requester or admin only |
| Brand logo | `storage/app/public/brand` | public (it is the brand) |
| Portfolio images | `storage/app/public/portfolio` | public (they are marketing) |

**Never** place KYC or deposit proof under `public/`. A guessable filename must not
be enough to read someone's ID document.

## Upload validation

- Allowlist MIME **and** extension; never trust `Content-Type` alone.
- Max 5 MB for proofs and KYC.
- Randomised storage filenames (never the original name).
- Reject SVG uploads for KYC and proofs (SVG can carry script).

## Payment-specific

Even though money is simulated, treat the ledger as real:

- Deposit approval requires sight of a transfer reference before crediting.
- `payment_methods` credentials are configuration, not secrets stored in code.
- A disabled method must be rejected at submission, not only hidden in the UI.
- Reconcile: total credits must equal total approved deposit requests.

## Secret handling

- `.env` outside the webroot, never committed.
- If a secret is ever committed: **rotate it**, then clean history. Deleting it in
  a later commit does not remove it.
- Prove configuration without printing values — check key presence, not content.
