---
inclusion: always
---

# Permissions — Trust-Prime

## Autonomy setting

- **Mode:** accept-file-changes for foundation work; **supervised** for anything
  touching money, auth, KYC or a migration.
- **Why:** this codebase holds balances. A careless edit can invent or destroy money.

## Low risk — proceed without asking

- Read any project file (never echo secret values)
- `git status`, `git diff`, `git log`
- `php artisan test`, `vendor/bin/pint --test`
- Create or edit files **inside the current task's `Files:` list**
- Add a unit test

## Medium risk — proceed, but show the diff and test output

- Edit files outside the task's `Files:` list
- Add a Composer or npm dependency
- Change routes, middleware or config
- Create a migration (never run it against production data)
- Change a Blade layout or shared component

## High risk — explicit written human approval

- Delete files, or drop/rename a table or column
- Any command against production data
- Expose, print or commit a credential
- Change authentication, authorisation, or any money logic
  (`LedgerService`, `EscrowService`, `DepositService`, `WithdrawalService`)
- Change the `money` invariants or reconciliation test
- Publish a release or push a tag
- Trigger an external transaction
- Force-push, rebase shared branches, rewrite history

## Forbidden — never, in any autonomy mode

- Commit `.env`, keys, KYC files, deposit proofs, or customer data
- Disable, skip or weaken a test to make the suite green
- Mark a blocked test as passed
- Write a `balance` column onto any table
- Put KYC or deposit proofs inside `public/`
- Edit approval fields (`approved_by`, `approved_at`, `status`, `human_approval`)
  — only a human approves
- Mark a security finding `fixed+retested` without re-running the test
- Use `float`/`double` for money

## Destructive patterns that must always ask

```
php artisan migrate:fresh        php artisan db:wipe
DROP TABLE / DROP COLUMN         TRUNCATE
rm -rf                           git push --force
git reset --hard                 composer remove
```

## Interrupt and rollback

- **Interrupt:** stop the agent turn; `Ctrl+C` in the terminal.
- **Roll back a file:** `git restore <path>` (before staging) or `git checkout -- <path>`.
- **Roll back a commit:** `git reset --soft HEAD~1` keeps the changes.
- **Not covered by rollback:** migrations already run, approved deposits, paid
  withdrawals, emails already delivered. Those need compensating ledger entries.

## Secret handling

- Secrets live in `.env` outside the webroot.
- To prove config without leaking it, print key **names** and whether they are set —
  never values.
