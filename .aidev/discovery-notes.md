# Discovery notes — Trust-Prime

Raw capture of what the client has actually said, before any of it becomes a
requirement. Three lists, never blurred (see the `client-discovery` skill).

Source: the brief given on 2026-11-20.

---

## Confirmed — the client said this

### Actors

- **Freelancers** — individuals who look for work.
- **Job posters** — individuals *or companies* who post work.
- **Admin** — platform operator, separate from the two above.

### Public surface

- Job listings appear on the **landing page** (public, no login to view).
- Public visitors can browse job listings.

### Freelancer capabilities

- Must have an account to act.
- Can create and maintain a **profile**.
- Can **upload a portfolio**.
- Can **update their profile**.
- Can **apply to jobs**.
- Holds a **balance**, and can **request a withdrawal**.

### Job poster capabilities

- Can create a **job posting profile**.
- Can **post new job listings**.
- Can **approve** freelancer applications.
- Can **message** freelancers in-app.
- Can **pay** freelancers in-app.

### Admin capabilities

- Separate **admin dashboard** in addition to the poster and freelancer dashboards.
- **Site content is CMS-managed**: admin can change site name, email address and
  phone number wherever they appear on the site.
- Can **edit, delete and approve** freelancer accounts and poster accounts.
- Can **see** job post listings.
- Can **manage** job post listings on behalf of posters.
- Can **fund wallet balances** of all users.
- Can **add, remove and manually edit** balances.
- Can **send payments to freelancer accounts on behalf of job posters**.
- Can **manage user accounts** generally.

### Communication

- "Easy message communication in app between freelancer and job poster."

### Workflow shape

- Freelancer applies → poster approves (an approval step exists, not instant hire).

---

## Assumptions — believed, NOT confirmed

> These are written down so they cannot silently become requirements.

| # | Assumption | If wrong, this breaks |
|---|---|---|
| A1 | Single currency, NGN | pricing display, payout maths, provider choice |
| A2 | Platform takes a commission on each job | revenue model, wallet ledger design |
| A3 | Money is held in escrow between payment and approval of delivery | refund flow, dispute handling |
| A4 | Default Laravel/Blade stack is acceptable | entire architecture |
| A5 | Release 1 does not need real-time chat | chat design, hosting (websockets) |
| A6 | Both freelancers and posters are individuals or small companies, no enterprise SSO | auth design |
| A7 | Admin is a single role, not a graded hierarchy | permissions model |
| A8 | Withdrawals go to a Nigerian bank account or mobile money | payout provider |
| A9 | Jobs are one-off, not recurring retainers | data model |
| A10 | Milestones inside a job are not needed in release 1 | payment release granularity |

---

## Open questions — these BLOCK design

| # | Question | Blocks | Status | Impact |
|---|---|---|---|---|
| Q1 | Which payment provider, and does it support **payouts** as well as collection? | payment + withdrawal design | open | blocks M6 |
| Q2 | Is money held in escrow by the platform, or passed straight to the freelancer on poster approval of the work? | ordering of funds, refunds, ledger | open | blocks data model |
| Q3 | Who is legally the merchant of record? Platform, or the poster? | terms, KYC, tax | open | blocks payment design |
| Q4 | What is the commission model — %, flat fee, or none? And charged to whom? | wallet ledger, totals | open | blocks M5/M6 |
| Q5 | How does a freelancer withdraw — bank transfer, mobile money, or manual admin payout? | withdrawal flow | open | blocks M7 |
| Q6 | Is KYC required before withdrawal? | onboarding flow | open | blocks M7 |
| Q7 | Real-time chat or asynchronous inbox for release 1? | hosting (websockets), scope | open | blocks M8 (chat) |
| Q8 | What happens when a poster pays but the freelancer never delivers? | dispute flow, refunds | open | blocks payment + admin |
| Q9 | Hosting type and budget — shared cPanel, VPS, or PaaS? | everything | open | blocks G3 |
| Q10 | Can a user be both a poster and a freelancer on one account? | roles model | open | blocks data model |
| Q11 | Are job listings moderated before appearing on the landing page? | approval workflow, admin scope | open | blocks M2 |
| Q12 | Do jobs have categories, skills tags, and search/filter in release 1? | catalogue design | open | blocks M2 |

---

## Contradictions and tensions noticed

These are recorded because the `client-discovery` skill requires surfacing them
rather than smoothing them over.

1. **"Poster approves the freelancer" vs "poster pays freelancer in-app."**
   Approval of an *application* is not the same as acceptance of *delivered work*.
   If money is released on application approval, a poster can lose money before
   any work happens. If money is released after delivery, there must be a
   delivery-confirmation step. **This needs an explicit decision (Q2).**

2. **Admin funding wallets freely vs financial integrity.**
   An admin who can add balance with no trail creates untracked money. This is
   legitimate as a *platform credit* mechanism, but must be distinguishable from
   real customer funds, and every adjustment must be immutable-audited.

3. **Withdrawal requests vs payout capability.**
   A freelancer can "apply to withdraw", which implies a request-and-approval
   flow. If no payout provider is integrated, admin becomes a manual bank clerk.
   That is acceptable for release 1 — but it must be a *stated decision*, not an
   accident.

---

## Feature recommendations for the client to decide on

Not in the brief; each is common in freelance marketplaces. Grouped by whether I
would advise it for release 1.

### Recommend for release 1

| Feature | Why it is worth it early |
|---|---|
| Job categories + skill tags + search | without them the landing page stops scaling past ~50 jobs |
| Saved/favourite jobs for freelancers | cheap, and drives return visits |
| Job listing expiry / auto-archive | stops a dead catalogue accumulating |
| Application status tracking (applied → shortlisted → hired → declined) | makes the approval step meaningful |
| Email notifications for key events | users will not sit in the app waiting |
| Immutable wallet ledger (every credit/debit a row) | makes admin adjustments auditable and disputes resolvable |
| Audit log for every admin action | protects the admin and the platform |
| Terms/privacy pages managed by the CMS | required before you take anyone's money |
| Basic reporting: signups, jobs posted, GMV, withdrawals pending | you cannot operate without these numbers |

### Recommend for release 2

| Feature | Why defer |
|---|---|
| Real-time chat (websockets) | hosting cost + complexity; an inbox with polling proves the flow first |
| Reviews and ratings (both directions) | needs enough completed jobs to be meaningful, and needs dispute rules |
| Dispute/arbitration flow | needs real cases to design well; release 1 can handle manually |
| Saved payment methods | provider-dependent, adds PCI surface |
| Freelancer verification badges / KYC tiers | policy-heavy; start manual |
| Job recommendations | needs data volume |
| Referral / affiliate programme | needs a working funnel first |

### Recommend against (or defer well past release 2)

| Feature | Why |
|---|---|
| Video calls in-app | expensive, and nobody switches from Zoom for a one-off job |
| Built-in file storage for deliverables | turns you into a storage provider; use links or a bounded upload |
| Multi-currency at launch | doubles the money bugs for no early benefit |
| Automated escrow arbitration (AI judging quality) | will lose you money and trust |

---

## Revision 2 — 2026-11-20

Changes requested by the owner after reading revision 1.

### Newly confirmed

| # | Decision | Supersedes |
|---|---|---|
| R1 | Platform name is **Trust-Prime**, not SkillLink. Name **and logo** must be editable in admin and propagate everywhere. | the working name |
| R2 | **Reviews and ratings are in scope.** Admin can add, edit, delete and hide reviews. | "Reviews and ratings" was previously out of scope |
| R3 | **Milestone-based payments are in scope.** Some jobs need staged payment. | A9 previously said "no partial escrow release" |
| R4 | **NGN is excluded from the entire application** — not a wallet currency, not a pricing currency, not a display currency, not in seeds or fixtures. | A2 previously allowed a display currency switcher without naming the whitelist |
| R5 | Milestone funding model: the poster funds the **full budget upfront**, internally earmarked across milestones. | — |
| R6 | Reviews are **open**: any approved user may review any other approved user. | — |

### Contradictions resolved in revision 2

1. **"No NGN on the website" vs "NGN as a converted display value".**
   The owner's written instruction was "ensure no NGN currency on the website".
   A follow-up answer allowed jobs priced in any currency with NGN merely excluded
   from wallet balances — which would still render NGN on screen. **Resolved in
   favour of the written instruction:** NGN is absent from the application
   entirely. Recorded in FR-036 and AC-021.

2. **"Any currency pricing" vs "USD-only ledger".** Allowing jobs to be priced in
   a non-USD currency while the ledger holds only USD is a real FX conversion, not
   a display-only conversion (which revision 1 promised). **Resolved by** storing
   the FX rate on the record at creation time, so every conversion is auditable
   and reproducible. Jobs are priced in USD, which removes the ambiguity while
   keeping a display switcher.

3. **Milestones vs "fund the whole budget upfront".** These are compatible only if
   escrow is one hold per job, internally earmarked per milestone. Chosen
   deliberately so that refund rules stay simple (refund is possible only while no
   milestone has been released). Recorded as A13.

4. **Open reviews vs the earlier recommendation of verified-only reviews.**
   The owner chose open reviews. This is buildable, but it weakens the signal — a
   poster can review a freelancer they never hired. Logged as a risk with
   mitigations (one review per pair, rate limits, admin moderation, audit trail).

### Still unresolved after revision 2

- Whether milestones may be *delivered* out of order (Q7). Release is strict order.
- Whether a freelancer may delete their own review (Q8).
- Whether milestone amounts are public on the job page (Q10).

These are all recorded as `Deferred: non-blocking` and do not block the spec gate.

---

## Revision 3 — 2026-11-20

Reviews changed from "verified user-to-user" to **open testimonial-style**.

### Newly confirmed

| # | Decision | Supersedes |
|---|---|---|
| R7 | Reviews are **not tied to a job relationship**. Any signed-in user may review any freelancer, whether or not they have worked together. | A11 revision 2 ("any approved user reviews any other approved user") |
| R8 | The **displayed reviewer name is free text** — it need not match the account name. | implied real-name attribution |
| R9 | **Admin can author a review with no linked user account**, supplying a name and text. It publishes immediately, attributed as posted by the platform. | FR-034 revision 2 ("add a review on behalf of a user") |
| R10 | Reviews **publish immediately**; admin removes problems afterwards. No approval queue. | — |
| R11 | **Reviews attach to freelancer profiles only.** Posters do not receive reviews. | A11 ("any other approved user") |

### Tension recorded rather than smoothed over

**"Reviews shouldn't be linked to active users" vs "login required to submit".**
Both were requested. They are compatible if you separate three things:

1. **Who may submit** — must be signed in (chosen).
2. **What name is shown** — free text, so the review is not visibly tied to the
   account (chosen).
3. **Whether a user row is required** — admin-authored reviews have **no** linked
   user at all (chosen).

So a review row's `reviewer_user_id` is **nullable**, exactly to support R9. This
is the design consequence that matters for the schema: the column cannot be
NOT NULL, and the "one review per reviewer per target" uniqueness rule must apply
only when a user is present.

### Consequence the owner should be aware of

An unverified review is a **social signal, not evidence**. Anyone with an account
can rate a freelancer they have never hired, and admin-authored reviews look like
organic ones (they are attributed as platform-posted, but users may not read
closely). Logged as two risks in `requirements.md`. Mitigations chosen: throttling,
user-flagging, admin moderation, full audit trail, and permanent platform
attribution on admin-authored reviews.

### Still unresolved after revision 3

- Q8: may a reviewer delete their own review? (release 1: edit only)
- Q10: are milestone amounts public on the job page?
- Whether flagged reviews should ever auto-hide after N flags (currently: never).

---

## Revision 4 — 2026-11-20

Manual wallet funding added. This changes how money enters the platform, so
`requirements.md` was returned to `draft` for re-approval.

### Newly confirmed

| # | Decision | Consequence |
|---|---|---|
| R12 | **Posters fund their wallet manually.** Admin publishes bank and crypto payment methods; posters transfer outside the app and submit proof. | new `payment_methods` + `deposit_requests` tables |
| R13 | Admin supports **multiple** methods of either type, and can **enable/disable** each. Disabled methods never appear to posters. | `is_enabled` flag; funding page filters on it |
| R14 | Crypto methods carry asset (e.g. USDT), network (e.g. ERC-20, TRC-20) and address. | typed fields per method type |
| R15 | Admin **approves each deposit** and **chooses the credited amount** (may differ from requested, e.g. net of fees). | `credited_amount_cents` set at approval |
| R16 | **Balance is derived, never stored.** No `balance` column exists anywhere. | all movements are ledger INSERTs |

### The question that produced R16

The owner asked: *"you mentioned no balance column, so how do admin fund wallet
balances... or is the column just not called balance?"*

The honest answer, now written into the design:

**There is a wallet, but no stored number.** A balance is the sum of immutable
ledger rows. Admin funding and freelancer payment both **INSERT a row**; they
never overwrite a figure. This was previously stated as a design choice but not
explained; the owner could not see how money actually moved, which is a
documentation failure, not a misunderstanding.

The design now carries a full money-journey diagram (poster deposit → admin
approval → escrow hold → milestone release → withdrawal) and an explicit
before/after table showing the row written at each step.

### Risks newly identified

| Risk | Why it matters | Mitigation |
|---|---|---|
| Poster claims they transferred but did not | classic manual-deposit fraud | admin must sight proof + reference before crediting; snapshot of method on the request |
| Admin credits more than received | creates money from nothing | `credited_amount_cents` is explicit and audited; reconciliation report compares credits against approved requests |
| Crypto address typo | funds sent to an unrecoverable address | address field validated by format per network; snapshot preserved on the request |
| Stale/disabled method left visible mid-session | poster pays an address admin has abandoned | disable takes effect immediately; request re-validates the method is still enabled at submit |

### Still unresolved after revision 4

- Whether a deposit request should expire if unapproved after N days.
- Whether posters may submit multiple concurrent deposit requests.
- Whether crypto amounts are recorded in USD at the rate on the transfer date
  (currently: admin chooses the USD amount credited).

These are non-blocking and recorded as open questions.

---

## Next action

Revision 2 is complete enough to approve. Remaining work before implementation:

1. Human approval of `requirements.md` (G1).
2. Draft `design.md` and `tasks.md` (G2).
3. Confirm the shared-host capability rows marked VERIFY (G3, blocks release not build).
