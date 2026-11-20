---
inclusion: always
---

# Product context — Trust-Prime

## What it is

A freelance marketplace where **escrow is funded before a job goes live**. Posters
prove seriousness with money; freelancers stop applying to fiction.

## Who uses it

| User | Goal | Frequency |
|---|---|---|
| Visitor | find credible work, judge the platform | one-off, then register |
| Freelancer | win work, get paid, withdraw | daily while job-hunting |
| Job poster | find competence, get work done safely | weekly while hiring |
| Admin | keep money and accounts trustworthy | daily operations |

## What success looks like

1. A freelancer can tell within seconds whether a job is real (funded badge).
2. A poster can hire without fearing the platform invents or loses money.
3. Any balance can be explained by listing ledger rows.

## Commercial rules the agent cannot infer

- **No commission in release 1.** The platform takes nothing from a transaction.
- **Money is simulated.** Deposits are approved by an admin against a real transfer
  made outside the app; payouts are marked paid by an admin. No card is charged.
- **Admin is the escrow holder.** Only admin releases or refunds escrow.
- **USD is the only wallet currency.** NGN is not supported anywhere.
- **One freelancer per job.** No multi-hire, no partial releases beyond milestones.

## Domain vocabulary

| Term | Means exactly |
|---|---|
| **Job** | a posting with a budget, one category, one poster |
| **Milestone** | an ordered, additive slice of a job's budget. A single-amount job has exactly one |
| **Escrow** | the funded budget held for a job; one hold per job, earmarked per milestone |
| **Funded** | money has left the poster's available balance into escrow |
| **Published** | funded *and* publicly visible. A draft or unfunded job is invisible |
| **Hired** | the poster selected one applicant; all others are declined |
| **Delivered** | freelancer attests a milestone is complete. **Not** a money movement |
| **Accepted** | poster agrees a milestone is complete. **Not** a money movement |
| **Released** | admin moved a milestone's money to the freelancer. **This** is a money movement |
| **Deposit** | a poster's funding request, pending until an admin approves it |
| **Adjustment** | an admin credit or debit outside the normal flow; always reasoned, always audited |
| **Review** | a 1–5 rating plus comment on a freelancer. Open: no job relationship required |

> The three-way distinction between *delivered*, *accepted* and *released* is the
> most important vocabulary in this product. Conflating them loses money.

## What this product deliberately does not do

- Charge real money, or hold real customer funds
- Arbitrate disputes or judge work quality
- Store deliverables
- Let posters release escrow themselves
- Verify that a reviewer ever worked with the freelancer (accepted risk)
