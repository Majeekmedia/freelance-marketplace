---
status: approved
approved_by: Project Owner
approved_at: 2026-11-20
project: Trust-Prime
profile: laravel-blade
currency: USD (wallet base currency)
revision: 4
revised_at: 2026-11-20
---

# Requirements — Trust-Prime

> **Source of truth.** Every design decision and task traces back here.
> A human approves this file — an agent never fills `approved_by`.

**Purpose of this project:** a production-grade demonstration build. There is no
external client, so commercial and regulatory constraints are deliberately
simplified, but the engineering standard is production-grade: real auth, real
money arithmetic, real access control, real tests.

---

## Project overview

**Trust-Prime** is a freelance marketplace. Job posters (individuals or
companies) publish job listings with the money funded into escrow *before* the
job goes live. One-off jobs are funded in full at posting; larger jobs can be
split into **milestones**, each with its own slice of the escrowed budget.
Freelancers browse publicly, create a profile with a portfolio, apply to jobs,
and get hired. The admin acts as the escrow holder and releases funds — per
milestone or in full — manually. Users can review and rate one another, and the
admin moderates those reviews. Communication happens in-app.

**Problem:** freelancers waste time on unpaid work and fake listings; posters
have no way to signal seriousness. Funding escrow at posting time solves both.

**Success measure:**

1. A poster can fund escrow, publish a job, and hire a freelancer, and the
   freelancer's wallet balance increases — with every step visible in an audit
   trail that reconciles to the cent.
2. A freelancer can apply, message, be hired, deliver against a milestone, get
   paid for it, and request a withdrawal without leaving the app.
3. An authorised admin can explain any wallet balance by pointing at ledger rows.
4. Every currency conversion is reproducible from a stored rate.

---

## Scope

### In scope (release 1)

- Public landing page listing funded, published jobs, with search and filters
- Poster: company/individual profile, post a job, fund escrow, view applicants,
  message, approve/hire, accept delivered work, request refund of unreleased escrow
- Freelancer: profile with portfolio, browse, apply, message, see escrow-funded
  badge, delivery marking, wallet balance, withdrawal request
- Poster: company/individual profile, post a job (single-amount **or**
  milestone-based), fund escrow, view applicants, message, approve/hire, accept
  delivered work, request refund of unreleased escrow
- Freelancer: profile with portfolio, browse, apply, message, see escrow-funded
  badge, per-milestone delivery marking, wallet balance, withdrawal request
- **Milestone-based payments**: a job may be split into ordered milestones, each
  with its own amount; delivery, acceptance, admin release and refund all
  operate per milestone
- **Reviews and ratings**: any signed-in user may review any freelancer without a
  prior job relationship, using a free-text display name; reviews publish
  immediately; admin can author reviews with no linked user and can edit, hide and
  delete any review
- Admin: CMS for site branding (site name, **logo upload**, contact email,
  phone), account approval, job management, wallet funding and manual
  adjustment, escrow release (full or per milestone), payout approval, full
  audit log
- **Manual wallet funding for posters**: admin manages a list of payment methods
  (bank transfer and one or more crypto networks), enables or disables each, and
  posters submit a deposit request with proof of transfer which admin approves to
  credit the wallet
- **Deposit approval queue**: every deposit request is reviewed by admin before
  any money enters a wallet, with the credited amount chosen by admin
- In-app messaging (polling-based, per application thread) + in-app notifications
- Queued email for key events
- Wallet ledger with immutable entries
- KYC document upload before first withdrawal
- Roles: freelancer, poster, admin — chosen at signup, not switchable
- Display-currency switcher for a fixed whitelist (USD, EUR, GBP, CAD).
  **NGN is not supported anywhere in the application.**

### Out of scope (explicit exclusions)

- Real payment processing: escrow funding and payouts are **simulated** through
  admin-actioned wallet credits. No card is charged, no bank is debited.
- Automated payout API integration (withdrawals are approved and marked paid by admin)
- **NGN as a wallet currency, a job pricing currency, or a display currency** —
  excluded from the entire application, including seeds, fixtures and copy
- Dispute/arbitration workflow
- Real-time chat, typing indicators, presence
- File attachments in messages
- Saved payment methods
- Multiple currencies in a single wallet (one base currency, USD)
- Freelancer recommendations
- Referral or affiliate programme
- Mobile app
- Video calls
- Recurring/retainer contracts
- Review responses or public replies to reviews

---

## Users and permissions

| Role | Can do | Must not do |
|---|---|---|
| **Visitor** | browse published jobs, view job detail, view freelancer public profiles, register | apply, message, see escrow amounts beyond the badge, see any wallet |
| **Freelancer** (approved) | edit own profile, manage portfolio, apply to jobs, message in own threads, mark milestone delivery, view own wallet, request withdrawal, **write reviews about any freelancer** | post jobs, see other freelancers' wallets, read others' threads, approve applications, release escrow |
| **Freelancer** (pending) | browse, edit own profile | apply, message, request withdrawal, write reviews |
| **Job poster** (approved) | create/edit own company profile, post jobs (single or milestone), **request a wallet deposit and upload proof**, fund escrow from wallet balance, view applicants to own jobs, approve/hire, message, accept milestone delivery, request escrow refund, **write reviews about any freelancer** | apply as freelancer, release escrow themselves, touch another poster's jobs, credit their own wallet without admin approval |
| **Job poster** (pending) | browse, edit own profile | post, fund, hire, message, write reviews, request a deposit |
| **Admin** | CMS site branding, approve/suspend/delete accounts, view all jobs, edit/archive any job, credit/debit any wallet with a reason, release escrow (full or per milestone), approve or reject withdrawal requests, **manage payment methods (add, edit, enable, disable)**, **approve or reject deposit requests and choose the credited amount**, **author reviews with no linked user, and edit / hide / delete any review**, view audit log, view all reports | edit the immutable ledger rows themselves, delete audit log entries, bypass the reason field on any money movement, delete a review without recording it in the audit log |

> **Enforcement is server-side on every route.** Hiding a nav link is not access
> control. Object-level checks required: a freelancer may only read their own
> threads and applications; a poster may only read applications to their own jobs.

---

## Functional requirements

### FR-001 — Public job browsing

- **Role:** Visitor
- **Behaviour:** The landing page lists all jobs with status `published` and
  escrow `funded`, newest first, paginated. Each card shows title, category,
  budget, currency, and an "Escrow funded" badge.
- **Acceptance:** A job with `status = draft`, `pending_review`, or `closed`, or
  with unfunded escrow, does not appear in the public list and its public URL
  returns 404.
- **Priority:** must-have

### FR-002 — Search and filter jobs

- **Role:** Visitor
- **Behaviour:** Visitors can filter by category, budget range, and free-text
  search across title and description, and sort by newest or highest budget.
- **Acceptance:** Filtering by a category returns only jobs in that category; a
  free-text search for a term present in one job's title returns that job and
  does not return jobs whose text lacks the term.
- **Priority:** must-have

### FR-003 — Registration with account type

- **Role:** Visitor
- **Behaviour:** A visitor registers as either freelancer or poster. The account
  is created with status `pending`. Type cannot be changed by the user afterwards.
- **Acceptance:** Registering creates an account with `status = pending` and the
  chosen type; a request that attempts to set `type = admin` is rejected or
  ignored and the account is not created as admin.
- **Priority:** must-have

### FR-004 — Account approval gates actions

- **Role:** System / Admin
- **Behaviour:** A `pending` account can log in, browse, and edit its own
  profile, but cannot apply, post, fund, or message. Restricted actions return a
  clear "awaiting approval" response. Admin approves or rejects.
- **Acceptance:** Given a pending freelancer, when they submit an application,
  the application is not created and the response states approval is pending.
  After admin approval the same action succeeds.
- **Priority:** must-have

### FR-005 — Authentication and session security

- **Role:** All
- **Behaviour:** Login, logout, and password reset with expiring one-time tokens.
  Passwords hashed with a modern algorithm. Session id regenerated on login and
  on privilege change.
- **Acceptance:** A used reset token cannot be reused; an expired token is
  rejected; a suspended account cannot log in.
- **Priority:** must-have

### FR-006 — Brute-force resistance

- **Role:** All
- **Behaviour:** Login, registration, and password-reset endpoints are rate
  limited by account identifier and by source. Failures return generic messages.
- **Acceptance:** Exceeding the configured attempt limit returns a throttled
  response while a legitimate user with a valid password can still log in after
  the window; the response never reveals whether an email exists.
- **Priority:** must-have

### FR-007 — Freelancer profile

- **Role:** Freelancer
- **Behaviour:** Freelancer maintains headline, bio, skills, hourly rate, and
  location. A public profile page shows the non-sensitive parts.
- **Acceptance:** Updating a profile persists the change and reflects on the public
  page; the public page never exposes email, phone, or wallet balance.
- **Priority:** must-have

### FR-008 — Portfolio items

- **Role:** Freelancer
- **Behaviour:** Freelancer adds portfolio entries (title, description, external
  URL, optional image). Can edit, reorder, and delete own entries.
- **Acceptance:** A freelancer can add and delete an entry and the public profile
  reflects the change; freelancer A cannot modify freelancer B's portfolio entry.
- **Priority:** must-have

### FR-009 — Job creation with mandatory escrow funding

- **Role:** Poster (approved)
- **Behaviour:** A poster creates a job (title, description, category, budget,
  deadline) and must fund escrow for the **full** budget before the job becomes
  publicly visible. Funding debits the poster's wallet into an escrow hold.
- **Acceptance:** A job whose escrow is not funded has `status â‰  published`,
  appears in no public listing, and returns 404 publicly. Funding moves exactly
  the budget amount out of the poster's available balance into an escrow balance.
- **Priority:** must-have
### FR-010 — Escrow funding is atomic

- **Role:** System
- **Behaviour:** Funding a job requires sufficient available balance. The debit of
  poster available balance and the creation of the escrow hold succeed or fail
  together.
- **Acceptance:** Attempting to fund a job whose budget exceeds available balance
  creates no escrow row, leaves the poster's balance unchanged, and returns a
  clear insufficient-funds error.
- **Priority:** must-have

### FR-011 — Public escrow badge

- **Role:** Visitor
- **Behaviour:** A published job visibly shows that its budget is held in escrow.
  The exact wallet balances of the poster are never exposed.
- **Acceptance:** The job detail page shows a funded badge; no response body for
  a public endpoint contains the poster's balance, ledger rows, or email address.
- **Priority:** must-have

### FR-012 — Apply to a job

- **Role:** Freelancer (approved)
- **Behaviour:** A freelancer submits one application per job with a cover note
  and proposed amount. Duplicate applications to the same job are rejected.
- **Acceptance:** A second application to the same job by the same freelancer is
  rejected; a freelancer cannot apply to a job with `status â‰  published`.
- **Priority:** must-have

### FR-013 — Poster reviews applicants and hires

- **Role:** Poster (approved)
- **Behaviour:** Poster views applications to their own jobs and marks one as
  `hired`. Hiring closes the job to further applications and sets other
  applications to `declined`.
- **Acceptance:** A poster cannot view applications to another poster's job
  (403/404 with no data). After hiring, the job accepts no new applications and
  all other applications are `declined`.
- **Priority:** must-have

### FR-014 — In-app messaging per application

- **Role:** Poster + Freelancer
- **Behaviour:** Each application has a message thread visible only to the two
  participants. Messages are polled, not pushed. Both participants see unread counts.
- **Acceptance:** Only the poster who owns the job and the freelancer who applied
  can read or post to a thread (verified for a third user: 403/404). Unread count
  decrements when the thread is opened.
- **Priority:** must-have

### FR-015 — In-app notifications

- **Role:** All
- **Behaviour:** Users receive notifications for new applications, hiring, new
  messages, escrow release, withdrawal approval, and account approval.
- **Acceptance:** A creation event produces exactly one notification for the
  correct recipient and none for the actor; opening the notification marks it read.
- **Priority:** must-have

### FR-016 — Queued email for key events

- **Role:** System
- **Behaviour:** Key events (registration welcome, account approved, hired,
  message received, escrow released, deposit approved or rejected, withdrawal
  processed) queue an email job processed by cron.
- **Acceptance:** Triggering an event enqueues exactly one job per recipient; if
  the mail transport fails, the job is retried and does not block the user action.
- **Priority:** must-have

### FR-042 — Email configuration via environment

- **Role:** Operator (deploy access), System
- **Behaviour:** SMTP settings live in the environment (`.env`), never in the
  database and never editable from the admin dashboard. Required keys: host,
  port, encryption (`tls` | `ssl` | none), username, password, from-address and
  from-name. A documented `.env.example` entry and a single `php artisan
  mail:test` command let the operator verify delivery before going live.
- **Acceptance:** (a) No mail credential is stored in any database table. (b) No
  route or view exposes a mail credential. (c) `mail:test` sends a real message
  through the configured transport and reports success or the SMTP error. (d)
  Missing configuration fails loudly at send time with a clear message rather
  than silently dropping mail.
- **Priority:** must-have

### FR-043 — SMTP delivery over TLS

- **Role:** System
- **Behaviour:** Email is delivered over authenticated SMTP supporting TLS
  (STARTTLS on 587) and implicit SSL (465), with credentials read from the
  environment. Transport failures are logged with the SMTP response code, never
  with credentials.
- **Acceptance:** (a) A message is accepted by an SMTP server configured with
  STARTTLS. (b) A transport failure logs the response code and not the password.
  (c) An application with no mail configuration still boots.
- **Priority:** must-have

### FR-017 — Work delivery and acceptance (per milestone)

- **Role:** Freelancer then Poster
- **Behaviour:** The hired freelancer marks a specific **milestone** `delivered`
  with a note. The poster then marks that milestone `accepted`. Only after a
  milestone is accepted does it become eligible for admin release. The job
  becomes `completed` when all milestones are released.
- **Acceptance:** A freelancer cannot mark delivery on a job they were not hired
  for; cannot mark a milestone already delivered or released; a poster cannot
  accept a milestone that is not `delivered`; accepting does not by itself move
  money (release is admin's action, per FR-032).
- **Priority:** must-have

### FR-018 — Freelancer wallet balance

- **Role:** Freelancer
- **Behaviour:** Freelancer sees available balance, pending (escrow earmarked on
  their behalf) and lifetime earned, each derived from ledger rows.
- **Acceptance:** The displayed balance always equals the sum of that user's
  ledger rows; a freelancer cannot see another user's wallet by changing an id.
- **Priority:** must-have

### FR-019 — Withdrawal request

- **Role:** Freelancer (approved, KYC complete)
- **Behaviour:** A freelancer requests a withdrawal up to the available balance
  with payout details. The request creates a `pending` withdrawal and holds the
  amount so it cannot be double-spent.
- **Acceptance:** Requesting more than the available balance is rejected;
  requesting twice does not allow more than the balance to be withdrawn; a
  withdrawal request without completed KYC is rejected.
- **Priority:** must-have

### FR-020 — KYC document upload

- **Role:** Freelancer
- **Behaviour:** A freelancer uploads an identity document; admin approves or
  rejects. Withdrawals require an approved document.
- **Acceptance:** A file of disallowed type or oversized is rejected; a document
  is only readable by the owner and admin, never by another user or publicly.
- **Priority:** must-have

### FR-021 — Admin escrow release (full or per milestone)

- **Role:** Admin
- **Behaviour:** Admin releases escrow to the hired freelancer, either for a single
  **milestone** or for the whole remaining escrow of a single-amount job. Release
  moves that amount from escrow into the freelancer's available balance, records
  ledger rows, and notifies both parties. Releasing the final milestone closes the job.
- **Acceptance:** Release requires a reason; releasing the same milestone twice is
  rejected (idempotent); the sum of all ledger rows remains consistent before and
  after; release without a hired freelancer is rejected; release of a milestone
  that is not `accepted` is rejected.
- **Priority:** must-have

### FR-022 — Admin wallet adjustment

- **Role:** Admin
- **Behaviour:** Admin can credit or debit any user's wallet, and fund a poster's
  wallet, each with a mandatory reason and full attribution.
- **Acceptance:** An adjustment without a reason is rejected; the resulting ledger
  row cannot be edited or deleted afterwards; the audit log records who did it and when.
- **Priority:** must-have

### FR-023 — Admin payout processing

- **Role:** Admin
- **Behaviour:** Admin reviews `pending` withdrawals and marks each `paid` or
  `rejected`. Rejection returns the held amount to available balance.
- **Acceptance:** A paid withdrawal cannot be paid again; a rejected withdrawal
  restores exactly the held amount; every transition is audited.
- **Priority:** must-have

### FR-024 — Admin account management

- **Role:** Admin
- **Behaviour:** Admin views, approves, rejects, suspends and soft-deletes
  freelancer and poster accounts. Cannot hard-delete an account with ledger history.
- **Acceptance:** Approving a pending account lets it perform gated actions;
  suspending blocks login; deleting an account with ledger rows is refused with a
  clear message.
- **Priority:** must-have

### FR-025 — Admin job management

- **Role:** Admin
- **Behaviour:** Admin views all jobs regardless of status, and can archive,
  unpublish, or edit any job. Archiving a job with unreleased escrow is allowed
  only via the refund path (FR-026).
- **Acceptance:** Archiving a funded job with no hired freelancer triggers the
  refund path rather than silently stranding funds.
- **Priority:** must-have

### FR-026 — Escrow refund

- **Role:** Admin
- **Behaviour:** Admin refunds **unreleased** escrow to the poster's wallet with a
  reason, closing the job. On a milestone job, refund returns only the amount of
  milestones that have **not** been released; milestones already released stay
  paid to the freelancer.
- **Acceptance:** Refund returns exactly the unreleased remainder; a fully
  released job has nothing to refund and the request is rejected; a refunded job
  cannot later be released; a partial refund on a milestone job leaves released
  milestones untouched and zeroes the remainder; double refund is rejected.
- **Priority:** must-have

### FR-027 — CMS site settings

- **Role:** Admin
- **Behaviour:** Admin edits site name, contact email, contact phone, and other
  site-wide copy. Brand name and logo are covered by FR-035. Changes appear
  everywhere on the next page load.
- **Acceptance:** Changing the contact email updates the footer and outgoing
  emails; no template hard-codes contact details; an invalid email or phone is rejected.
- **Priority:** must-have

### FR-028 — Admin audit log

- **Role:** Admin
- **Behaviour:** Every admin action that moves money, changes an account status,
  or edits a job is recorded immutably with actor, action, target, reason, and timestamp.
- **Acceptance:** Each of the money-moving actions in FR-021/022/023/026 produces
  one audit row; audit rows cannot be updated or deleted through the app.
- **Priority:** must-have

### FR-029 — Immutable wallet ledger

- **Role:** System
- **Behaviour:** Every balance change is a ledger row (user, type, direction,
  amount, currency, reference, reason, actor, timestamp). Balances are derived,
  never stored as a mutable number that can drift.
- **Acceptance:** A balance recomputed from ledger rows equals the displayed
  balance for every user after any sequence of operations; no endpoint updates or
  deletes a ledger row.
- **Priority:** must-have

### FR-030 — Money precision
- **Role:** System
- **Behaviour:** All money is stored and computed as integer minor units (cents).
  Display conversion to another currency uses a stored rate and never writes a
  converted amount into the ledger.
- **Acceptance:** Summing ten ledger rows of 0.10 each yields exactly 1.00; no
  floating-point type is used for money; a converted display value never appears
  as a stored ledger amount.
- **Priority:** must-have

### FR-031 — Admin dashboard reporting

- **Role:** Admin
- **Behaviour:** Dashboard shows counts and totals: users by type and status, jobs
  by status, total escrow held, total paid out, pending withdrawals, recent admin actions.
- **Acceptance:** Total escrow held equals the sum of all open escrow holds; the
  figure reconciles with the ledger at all times.
- **Priority:** must-have

### FR-032 — Milestone-based job payments

- **Role:** Poster, Freelancer, Admin
- **Behaviour:** Instead of one amount, a job may define ordered milestones, each
  with a title and an amount. The milestone amounts must sum exactly to the job
  budget. The poster funds the **entire** budget into escrow at posting time, and
  the escrow is internally earmarked across milestones. The hired freelancer
  marks a specific milestone delivered; the poster accepts that milestone; admin
  releases **that milestone's** amount to the freelancer. A single-amount job is
  modelled as exactly one milestone so both paths share one code path.
- **Acceptance:** (a) Milestones whose amounts do not sum to the budget are
  rejected at creation. (b) Releasing milestone 2 before milestone 1 is rejected
  (strict order). (c) Releasing a milestone credits exactly its amount and marks
  it released; releasing it twice is rejected. (d) Sum of released milestones can
  never exceed the escrowed total. (e) A single-amount job behaves identically to
  a one-milestone job in every test.
- **Priority:** must-have

### FR-033 — Reviews and ratings (open, testimonial-style)

- **Role:** Any signed-in user
- **Behaviour:** A review is a 1–5 star rating plus a written comment attached to a
  **freelancer profile**. **No job relationship is required**: any signed-in user
  may review any freelancer, whether or not they have ever worked together. The
  reviewer's **display name is free text** and does not have to match their account
  name — a reviewer may present themselves as a company or any name they choose.
  A signed-in reviewer holds one active review per freelancer; submitting again
  updates it. Reviews publish **immediately**, with no approval step.
- **Acceptance:** (a) A signed-in user with no prior relationship to a freelancer
  can submit a review and it appears on the public profile immediately. (b) The
  displayed reviewer name is exactly the string submitted, even when it differs
  from the account's registered name. (c) A second review by the same account about
  the same freelancer updates the existing review instead of adding a second row.
  (d) A user cannot review their own account. (e) A pending or suspended account
  cannot submit. (f) A guest who is not signed in cannot submit.
- **Priority:** must-have

### FR-034 — Admin review authoring and moderation

- **Role:** Admin
- **Behaviour:** Admin can **author a review with no linked user account**,
  supplying a free-text reviewer name, a rating and a comment. It publishes
  immediately to the freelancer's profile and is attributed as posted by the
  platform. Admin can also **edit** any review's rating or comment, **hide** a
  review (reversible; excluded from public display and from the rating average),
  and **delete** a review (removed from every public view, retained internally for
  the audit trail).
- **Acceptance:** (a) An admin-authored review with no linked user appears
  immediately on the freelancer's public profile, attributed as posted by the
  platform, displaying the exact name the admin typed. (b) Hiding a review removes
  it from the profile and from the average; unhiding restores both. (c) Editing a
  rating immediately changes the displayed average. (d) Hiding, editing or
  deleting without a reason is rejected. (e) Each action produces exactly one audit
  row. (f) A deleted review is absent from every public view but remains
  reconstructable from the audit trail.
- **Priority:** must-have

### FR-035 — Site branding: name and logo

- **Role:** Admin
- **Behaviour:** Admin can change the site name and upload a logo image. Both are
  persisted as settings and rendered everywhere the brand appears: page header,
  footer, browser tab title, emails, and the admin panel. If no logo is uploaded a
  text wordmark of the site name is used instead.
- **Acceptance:** (a) Changing the site name updates the header, footer, page
  titles and outgoing emails on the next page load. (b) Uploading a logo replaces
  the wordmark everywhere; removing it restores the wordmark. (c) A non-image
  file, or an image over the size limit, is rejected with a clear message.
  (d) No template hard-codes the brand name.
- **Priority:** must-have

### FR-036 — Currency whitelist with NGN excluded

- **Role:** System, Visitor
- **Behaviour:** A fixed whitelist of supported display currencies (USD as base,
  plus EUR, GBP and CAD). Jobs are priced in USD. Visitors may switch display
  currency to see converted values. **NGN is not a supported currency anywhere**:
  it is absent from the whitelist, cannot be selected, and cannot be stored.
- **Acceptance:** (a) Submitting `NGN` as a currency code to any endpoint is
  rejected by validation. (b) No page, seed, fixture or email contains an NGN
  amount. (c) Every converted value is computed from a rate stored at the time of
  the value's creation, so the same record always displays the same converted
  figure. (d) Switching display currency never changes any stored ledger amount
  or job budget.
- **Priority:** must-have

### FR-037 — Review abuse resistance

- **Role:** System / Admin
- **Behaviour:** Because reviews publish immediately and need no job relationship,
  submissions are throttled per account and per source, and each review carries a
  flag counter that any signed-in user may raise. Flagged reviews surface at the
  top of the admin review queue.
- **Acceptance:** (a) Exceeding the configured submission rate returns a throttled
  response and creates no review. (b) Every submitted review is persisted with a
  flag count of zero and a moderation state of `visible`. (c) A review flagged by
  users appears ahead of unflagged reviews in the admin queue. (d) Flagging a
  review does not hide it automatically.
- **Priority:** must-have

### FR-038 — Admin payment method management

- **Role:** Admin
- **Behaviour:** Admin maintains a list of payment methods that posters can use to
  fund a wallet. Two types are supported: **bank transfer** (bank name, account
  name, account number, optional sort/routing detail) and **crypto transfer**
  (asset such as USDT, network such as ERC-20 or TRC-20, and wallet address).
  Admin may add any number of methods, edit them, and **enable or disable each
  one**. A disabled method is never shown to posters on the funding page.
- **Acceptance:** (a) Admin can create multiple crypto methods on different
  networks and all appear to posters while enabled. (b) Disabling a method removes
  it from the poster funding page on the next load while leaving already-submitted
  requests intact. (c) Re-enabling restores it. (d) A method missing its required
  fields for its type (e.g. a crypto method with no network or address) is
  rejected at save. (e) Deleting a method that has deposit requests soft-deletes
  it so history is preserved.
- **Priority:** must-have

### FR-039 — Poster deposit request with proof

- **Role:** Poster (approved)
- **Behaviour:** A poster opens the funding page, sees only enabled payment
  methods with the admin's bank or crypto details, makes the transfer outside the
  app, then submits a **deposit request**: amount, chosen payment method, a
  transfer reference (bank reference or transaction hash), and an uploaded proof
  image. The request enters `pending`. **No money is credited at this point.**
- **Acceptance:** (a) A deposit request is created with `status = pending` and the
  poster's derived balance is unchanged. (b) Submitting with a disabled payment
  method is rejected. (c) A non-image proof or one over the size limit is
  rejected. (d) The payment method's details are **snapshotted onto the request**,
  so editing the method later does not rewrite what the poster was told to pay.
  (e) The proof file is readable only by the requester and admin.
- **Priority:** must-have

### FR-040 — Admin deposit approval credits the wallet

- **Role:** Admin
- **Behaviour:** Admin reviews the deposit queue with the submitted proof and
  either **approves** — choosing the exact amount to credit, which may differ from
  the requested amount if fees were deducted — or **rejects** with a reason.
  Approval writes one `deposit_credit` ledger row. Rejection writes nothing and
  requires a reason.
- **Acceptance:** (a) Approving creates exactly one `deposit_credit` ledger row for
  the chosen amount and the poster's derived balance rises by exactly that amount.
  (b) Approving the same request twice is rejected and creates no second row.
  (c) A rejected request credits nothing and its reason is recorded. (d) Every
  approval and rejection writes one audit row naming the actor and the amount.
  (e) A request cannot be approved after it has been rejected, or vice versa.
- **Priority:** must-have

### FR-041 — Wallet balance is derived, never stored

- **Role:** System
- **Behaviour:** There is **no mutable balance field anywhere in the system**. A
  user's available balance is computed as the sum of credit ledger rows minus the
  sum of debit and hold rows. Admin funding, escrow funding, escrow release,
  refunds, withdrawals and adjustments are all expressed as ledger rows.
- **Acceptance:** (a) No database column named `balance` exists on any table.
  (b) After any sequence of deposits, funding, releases, refunds, adjustments and
  withdrawals, the derived balance equals a recomputation from ledger rows to the
  cent. (c) Concurrency: two simultaneous credits cannot lose one another because
  each is an independent insert, not an update of a shared value. (d) An admin can
  explain any balance by listing that user's ledger rows in order.
- **Priority:** must-have

---

## Non-functional requirements

| ID | Area | Requirement | Measure |
|---|---|---|---|
| NFR-001 | Performance | Landing page and job search respond quickly on shared hosting | p95 < 800 ms with 500 jobs |
| NFR-002 | Security | Authorisation enforced server-side on every route | 100% of protected routes tested for both permitted and forbidden roles |
| NFR-003 | Security | Money integrity | every balance reconcilable to ledger; zero float usage |
| NFR-004 | Reliability | Escrow and wallet operations are atomic | no partial state under forced failure |
| NFR-005 | Accessibility | Core journeys usable by keyboard and screen reader | no critical axe violations |
| NFR-006 | Responsiveness | Core journeys usable at 320px, 768px, 1440px | all three widths pass |
| NFR-007 | Maintainability | Business rules isolated from controllers | money logic covered by unit tests |
| NFR-008 | Deployability | Runs on shared cPanel without a Node runtime | verified on the actual host |
| NFR-009 | Privacy | Personal data minimised and never leaked to public endpoints | no email/phone in any public response |
| NFR-010 | Currency integrity | NGN unsupported; conversions reproducible from a stored rate | zero NGN occurrences in code, seeds, fixtures and rendered output |
| NFR-011 | Branding | Brand name and logo are data, never hard-coded | changing the setting changes every rendered surface |
| NFR-012 | Abuse resistance | Open reviews cannot be flooded | per-account and per-source throttling; flagged reviews visible to admin |

---

## Workflows and business rules

### Account states

```
pending â”€â”€â–¶ approved â”€â”€â–¶ suspended
   â”‚            â”‚             â”‚
   â””â”€â”€â–¶ rejectedâ””â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”´â”€â”€â–¶ (reinstated by admin)
```

- `pending`: can log in, browse, edit own profile. Cannot act.
- `approved`: full role permissions.
- `suspended`: cannot log in.
- Approval changes are admin-only and audited.

### Job lifecycle

```
draft â”€â”€fund escrowâ”€â”€â–¶ published â”€â”€hireâ”€â”€â–¶ in_progress â”€â”€all milestones releasedâ”€â”€â–¶ completed
  â”‚                        â”‚                                                          â–²
  â”‚                        â””â”€â”€(no applicants, admin refunds)â”€â”€â–¶ refunded              â”‚
  â””â”€â”€(abandoned)                                                                       â”‚
                          milestone: pending â–¶ delivered â–¶ accepted â”€â”€admin releaseâ”€â”€â”˜
```

**Rules:**

- A job is publicly visible **only** when `status = published` and escrow is funded.
- Hiring is exclusive: one freelancer, all other applications declined.
- `delivered` and `accepted` are attestations, not money movements.
- Only admin releases escrow (FR-021, per milestone). Only admin refunds (FR-026).
- A job cannot be both released and refunded.
- **Milestones release strictly in order.** Milestone N cannot be released while
  milestone N-1 is unreleased.
- A single-amount job is a one-milestone job. There is no separate code path.

### Milestone states

```
pending â”€â”€freelancer deliversâ”€â”€â–¶ delivered â”€â”€poster acceptsâ”€â”€â–¶ accepted â”€â”€admin releaseâ”€â”€â–¶ released
   â”‚
   â””â”€â”€(job refunded before any release)â”€â”€â–¶ refunded
```

- Milestone amounts must sum exactly to the job budget.
- A milestone can never be released twice.
- Refund returns only the **unreleased remainder**; a milestone already released
  stays paid. Refund is rejected when nothing remains unreleased.

### Escrow states

```
unfunded â”€â”€â–¶ funded â”€â”€â–¶ released (to freelancer)
                 â””â”€â”€â”€â”€â–¶ refunded (to poster)
```

- Escrow amount equals the job budget exactly.
- Escrow is never partially released in release 1.

### Wallet and ledger model

- Wallet currency: **USD**, stored as integer cents.
- Ledger row kinds: `deposit_credit` (admin-approved funding),
  `escrow_hold`, `escrow_release_credit`, `escrow_refund`,
  `withdrawal_hold`, `withdrawal_paid`, `withdrawal_rejected_return`,
  `admin_credit`, `admin_debit`, `job_payment_credit`.
- Balances are **derived**: `available = sum(credits) - sum(debits) - sum(holds)`.
- A ledger row is never updated or deleted.
- Each ledger row stores the FX rate that was current when it was created, so any
  historical display conversion is reproducible.

### Currency rules

- Base and only wallet currency: **USD** ($, cents).
- Display currencies (whitelist): `USD`, `EUR`, `GBP`, `CAD`.
- **`NGN` is not a supported currency.** It is rejected by validation, absent from
  seeds and fixtures, and must not appear in any rendered page or email.
- Jobs are priced in USD. A poster cannot choose a pricing currency.
- A display conversion uses a rate stored with the underlying record; it is never
  written into a ledger amount.

### Deposit lifecycle

```
poster requests â”€â”€â–¶ pending â”€â”€admin approves(amount)â”€â”€â–¶ credited
                        â””â”€â”€â”€â”€admin rejects(reason)â”€â”€â”€â”€â–¶ rejected
```

- A deposit request credits **nothing** until admin approves.
- Admin chooses the credited amount, so fees may be deducted.
- Approving writes exactly one `deposit_credit` ledger row.
- The payment method details are snapshotted on the request, so history is stable.

### The complete money journey

This is the whole flow the platform supports. Every arrow is a ledger row.

```
 1. Poster transfers money outside the app (bank or crypto)
         â”‚
         â–¼
 2. POSTER submits deposit request + proof â”€â”€â–¶ pending   [no ledger row]
         â”‚
         â–¼
 3. ADMIN approves, chooses amount â”€â”€â–¶ ledger: deposit_credit(+)
         â”‚
         â–¼
    Poster wallet available = sum(credits) - sum(debits) - sum(holds)
         â”‚
         â–¼
 4. POSTER funds a job â”€â”€â–¶ ledger: escrow_hold(âˆ’) + escrow row
         â”‚
         â–¼
 5. FREELANCER delivers milestone â”€â”€â–¶ no ledger row (attestation)
 6. POSTER accepts milestone     â”€â”€â–¶ no ledger row (attestation)
         â”‚
         â–¼
 7. ADMIN releases milestone â”€â”€â–¶ ledger: escrow_release_credit(+) for freelancer
         â”‚
         â–¼
    Freelancer wallet available increases
         â”‚
         â–¼
 8. FREELANCER requests withdrawal â”€â”€â–¶ ledger: withdrawal_hold(âˆ’)
         â”‚
         â–¼
 9. ADMIN pays outside the app, marks paid â”€â”€â–¶ ledger: withdrawal_paid(âˆ’)
```

**Why no balance column:** steps 3, 4, 7 and 8/9 each append a row. The balance is
whatever those rows add up to. If a poster's balance looks wrong, you list the rows
and the answer is there — you never have to guess what an overwrite did.

### Payment method rules

- Two types: `bank_transfer` and `crypto`.
- Bank fields: bank name, account name, account number, optional routing detail.
- Crypto fields: asset (e.g. USDT), network (e.g. ERC-20, TRC-20), wallet address.
- Admin may hold many methods of either type.
- Enabled methods appear on the poster funding page; disabled ones never do.
- A method's details are **copied onto each deposit request** at submission time.
- Deleting a method that has requests soft-deletes it; history is never orphaned.

### Review rules

- **Reviews attach to freelancer profiles only.** Posters do not receive reviews.
- **No job relationship is required.** Any signed-in user may review any
  freelancer, including one they have never worked with.
- The **displayed reviewer name is free text** and need not match the account name.
- **Admin-authored reviews have no linked user account**; they are attributed as
  posted by the platform and carry a free-text reviewer name.
- One active review per account per freelancer; re-reviewing updates it. (An
  anti-flood control — not a restriction on *who* may review.)
- Reviews publish **immediately**. There is no pre-publication approval queue.
- A user cannot review their own account.
- Ratings are integers 1–5. Comment length is bounded.
- Moderation states: `visible`, `hidden`, `deleted`. Hidden and deleted reviews are
  excluded from public display and from the average.
- Admin may author, edit, hide and delete; every action is audited with a reason.
- Flagged reviews rise to the top of the admin queue but are **not** auto-hidden.

### Withdrawal states

```
requested â”€â”€â–¶ paid
     â””â”€â”€â”€â”€â”€â”€â–¶ rejected (funds returned to available)
```

### Money arithmetic rules

1. All amounts are integers in cents. Never `float`, never `double`.
2. Rounding only at display, never in the ledger.
3. A currency conversion is display-only and never persisted as a ledger amount.
4. Every money movement requires a `reason` string of at least 5 characters.

### Permission rules

- Object-level ownership required on: applications, threads, wallet, jobs,
  portfolio items, withdrawal requests, KYC documents.
- Admin routes require the `admin` role on **every** action, enforced by middleware,
  not by a hidden menu.

### Notification rules

- One notification per recipient per event; never notify the actor.
- Email is queued, never sent inline in a request.

---

## Data and integrations

| Entity | Purpose | Sensitive data |
|---|---|---|
| users | identity, type, status | yes — email, password hash |
| freelancer_profiles | headline, bio, skills, rate | public subset only |
| portfolios | freelancer work samples | no |
| company_profiles | poster company details | partial |
| categories | job classification | no |
| jobs | listings and lifecycle | no |
| milestones | ordered payment slices per job | yes |
| reviews | ratings and comments on freelancer profiles; `reviewer_user_id` is **nullable** so admin-authored reviews need no account | yes |
| review_flags | abuse reports raised against a review | no |
| applications | freelancer â†’ job, with proposed amount | partial |
| threads, messages | per-application conversation | yes |
| notifications | per-user event log | no |
| wallets | one row per user; holds currency metadata only — **no balance column** | no |
| ledger_entries | immutable money movements | yes |
| payment_methods | admin-managed bank/crypto funding routes | yes — payout addresses |
| deposit_requests | poster funding requests with proof and approval state | yes |
| escrows | hold per job | yes |
| withdrawals | payout requests | yes — payout details |
| kyc_documents | identity verification | yes — highest sensitivity |
| audit_logs | admin action trail | yes |
| settings | CMS site copy | no |

**Integrations**

| Service | Purpose | Failure behaviour |
|---|---|---|
| SMTP provider | queued transactional email | job retries; user action still succeeds |

> **SMTP credentials live in `.env`, never in the database.** This was considered
> and rejected the alternative: an admin-editable SMTP form would let a
> compromised admin account point mail at a server they control and read every
> password-reset email — a path from "one admin account" to "every account".
> Keeping credentials in the environment means changing them requires deploy
> access, which is the right level of privilege for something that can intercept
> every password reset.
| FX rate source | display-only currency conversion | falls back to stored last-known rate; never blocks a page render |

**Explicitly not integrated (release 1):** real card processing, real payout
rails, KYC provider. Escrow funding and payout are admin-attested wallet
operations, and the UI must label them as simulated.

---

## Deployment constraints

- Runtime: **PHP 8.3** (verify on the host with `php -v`).
- Framework: **Laravel 12** (confirm the compatible version on install).
- Database: **MySQL 8 or MariaDB 10.6** — engine must be confirmed, they differ.
- Web server: **Apache 2.4**, document root pointed at `public/`.
- Hosting: **shared cPanel**.
- Shell access: **restricted SSH** — assume no long-running processes.
- Background jobs: **cron only**. No queue worker daemon; queue is drained by a
  scheduled `queue:work --stop-when-empty` tick.
- Frontend build: **built locally with XAMPP/Node, compiled assets uploaded**.
  Node is not assumed available on the host.
- Storage: symlink support must be verified (`storage:link` may fail on shared hosts).
- Email: SMTP provider with SPF/DKIM configured by the site owner.
- Payment callbacks: not applicable in release 1 (simulated escrow), but the
  admin money actions are the equivalent trust boundary and must be audited.
- Local development: **XAMPP** at `C:\xampp`.

> Compatibility is part of the definition of done. A feature that only works
> locally because XAMPP has Node and a queue worker is not done.

---

## Acceptance criteria

### AC-001 — A job cannot be seen publicly unless it is funded

- **Given** a poster who created a job but has not funded escrow
- **When** a visitor requests the landing page and the job's public URL
- **Then** the job is absent from the listing and the URL returns 404

### AC-002 — Funding escrow moves money exactly once

- **Given** a poster with 500.00 available
- **When** they fund a job with a 200.00 budget
- **Then** available becomes 300.00, escrow holds 200.00, and retrying the same
  funding creates no second hold

### AC-003 — Only admin releases escrow

- **Given** a delivered job with filled escrow
- **When** the poster or freelancer attempts to release it
- **Then** the request is denied and no ledger row is created

### AC-004 — Release credits the freelancer exactly once

- **Given** a delivered and accepted milestone of 200.00 within a funded job
- **When** admin releases that milestone
- **Then** the freelancer's available increases by 200.00, that milestone is
  `released`, and a second release of the same milestone is rejected

### AC-005 — Every balance reconciles to the ledger

- **Given** any sequence of funding, release, refund, adjustment and withdrawal
- **When** the balance is recomputed from ledger rows
- **Then** it equals the displayed balance to the cent

### AC-006 — Cross-user access is denied

- **Given** freelancer A is signed in
- **When** they request freelancer B's ledger, B's thread, or B's KYC document
- **Then** access is denied and the response contains no private detail

### AC-007 — Pending accounts cannot act

- **Given** a pending freelancer with a valid session
- **When** they submit an application
- **Then** no application exists and the response states approval is pending

### AC-008 — Withdrawal cannot exceed the balance

- **Given** a freelancer with 150.00 available and no open withdrawal
- **When** they request 200.00
- **Then** the request is rejected and available remains 150.00

### AC-009 — KYC gates withdrawal

- **Given** an approved freelancer with no approved KYC document
- **When** they request a withdrawal
- **Then** the request is rejected with a message naming the missing requirement

### AC-010 — CMS changes propagate

- **Given** admin changes the site name and contact email
- **When** a visitor loads the landing page and an email is dispatched
- **Then** both show the new values and no page contains the old value

### AC-011 — Admin actions are auditable

- **Given** admin performs a wallet adjustment
- **When** the audit log is viewed
- **Then** exactly one row exists naming the actor, target, amount, reason and timestamp

### AC-012 — Public endpoints leak nothing private

- **Given** any unauthenticated request to any public route
- **When** the responses are inspected
- **Then** no response contains an email address, wallet figure, phone number, or KYC path

### AC-013 — Milestone amounts must equal the budget

- **Given** a poster defining milestones of 100.00 and 150.00 for a job budgeted at 300.00
- **When** they submit the job
- **Then** the job is rejected and the error states the milestone total does not
  match the budget

### AC-014 — Milestones release in strict order

- **Given** a job with milestone 1 pending and milestone 2 accepted
- **When** admin attempts to release milestone 2
- **Then** the release is rejected and no ledger row is created

### AC-015 — One milestone release cannot exceed total escrow

- **Given** a job with 500.00 escrowed across three milestones
- **When** all three milestones are released
- **Then** the sum credited equals exactly 500.00 and the escrow balance is zero,
  and no further release is possible

### AC-015b — Partial refund respects released milestones

- **Given** a three-milestone job with 500.00 escrowed where milestone 1 (200.00)
  is released
- **When** admin refunds the job
- **Then** the poster receives exactly 300.00, the freelancer keeps 200.00, and the
  escrow balance is zero

### AC-016 — A reviewer cannot review twice

- **Given** account A has already reviewed freelancer B
- **When** A submits another review of B
- **Then** B's profile still shows exactly one review from A, with the updated rating

### AC-016b — No job relationship is required

- **Given** a signed-in poster who has never posted a job and never hired anyone
- **When** they review a freelancer
- **Then** the review publishes immediately and appears on that freelancer's profile

### AC-016c — Display name is free text

- **Given** a signed-in user whose account name is "Ada Okafor"
- **When** they submit a review with the display name "Brightline Studio Ltd"
- **Then** the profile shows "Brightline Studio Ltd", not "Ada Okafor"

### AC-016d — Admin can author a review with no linked user

- **Given** admin is creating a review for a freelancer
- **When** they supply a name, a rating and a comment without selecting any user
- **Then** the review publishes immediately, is attributed as posted by the platform,
  and no user account is referenced by the review

### AC-016e — Guests cannot submit

- **Given** a visitor who is not signed in
- **When** they attempt to submit a review
- **Then** the request is rejected and no review is created

### AC-017 — A user cannot review themselves

- **Given** an approved user
- **When** they submit a review targeting their own account
- **Then** the request is rejected and no review is created

### AC-018 — Hidden reviews leave the average

- **Given** a user with visible reviews averaging 4.0 and one 1-star review
- **When** admin hides the 1-star review
- **Then** the public average returns to 4.0 and the review count decreases by one

### AC-019 — Admin review actions are audited

- **Given** admin hides, edits, adds or deletes a review
- **When** the audit log is viewed
- **Then** exactly one row exists per action naming the actor, target review, action and reason

### AC-019b — Review throttling blocks flooding

- **Given** a signed-in user submitting reviews repeatedly across many freelancers
- **When** they exceed the configured submission rate
- **Then** further submissions are throttled, no additional reviews are created, and
  already-published reviews remain visible

### AC-020 — Branding changes propagate everywhere

- **Given** admin changes the site name and uploads a new logo
- **When** a visitor loads the landing page, a job page, and an outgoing email is dispatched
- **Then** all three show the new name and logo, and no rendered output contains the old brand

### AC-021 — NGN is rejected everywhere

- **Given** any form or endpoint that accepts a currency code
- **When** `NGN` is submitted
- **Then** validation rejects it, nothing is persisted, and no page, seed or
  fixture renders an NGN amount

### AC-022 — A deposit request credits nothing until approved

- **Given** a poster with a balance of 0.00 who submits a deposit request for 500.00
- **When** the request is saved
- **Then** the request is `pending` and the poster's derived balance is still 0.00

### AC-023 — Admin approval credits exactly the chosen amount

- **Given** a pending deposit request for 500.00 where 5.00 of fees were taken
- **When** admin approves it crediting 495.00
- **Then** exactly one `deposit_credit` ledger row of 495.00 exists and the
  poster's balance is 495.00, not 500.00

### AC-024 — Disabled payment methods are hidden

- **Given** admin has two enabled crypto methods and one disabled bank method
- **When** a poster opens the funding page
- **Then** only the two enabled methods are listed, and submitting a deposit request
  referencing the disabled method is rejected

### AC-025 — Deposit approval is idempotent

- **Given** an already-approved deposit request
- **When** admin attempts to approve it again
- **Then** the request is rejected and no second ledger row is created

### AC-026 — No stored balance can drift

- **Given** any sequence of deposits, escrow holds, releases, refunds,
  adjustments and withdrawals applied to a user
- **When** that user's balance is recomputed from ledger rows
- **Then** it equals the displayed figure to the cent, and no table contains a
  column that stores a balance

### AC-027 — Withdrawal requires funds that actually arrived

- **Given** a poster whose only funding came from an unapproved deposit request
- **When** they attempt to fund a job
- **Then** the request is rejected for insufficient balance, because pending
  deposits credit nothing

---

## Assumptions

| # | Assumption | Impact if wrong | Confirmed? |
|---|---|---|---|
| A1 | Demonstration project, no real money or real KYC obligation | regulatory work disappears; escrow can be simulated | yes — stated by owner |
| A2 | Single wallet currency USD, display conversion only | multi-currency wallets would need a ledger redesign | yes |
| A3 | Admin manually releases and refunds escrow | automation would need a dispute policy | yes |
| A4 | Async messaging is sufficient; no websockets | real-time would change hosting | yes |
| A5 | Account type is permanent per user | switching would complicate permissions | yes |
| A6 | Jobs publish immediately once funded; no moderation queue | moderation adds a status and admin workload | yes |
| A7 | Approval gates actions but not login | gating login would change the auth flow | yes |
| A8 | One freelancer hired per job | multi-hire would change escrow to per-hire holds | yes |
| A9 | Milestones exist and the **full** budget is funded upfront, earmarked across milestones | funding-per-milestone would allow a job to stall halfway | yes — revision 2 |
| A10 | Shared cPanel supports the required PHP extensions and cron | otherwise the host choice changes | not confirmed |
| A11 | Reviews are open testimonial-style: any signed-in user reviews any freelancer, no job relationship needed, display name is free text, and admin may author reviews with no linked user | requiring verified relationships would add a job link and remove guest-style reviews | yes — revision 3 |
| A12 | Jobs are priced in USD only; the display whitelist is USD/EUR/GBP/CAD | wider currency support changes validation and FX surface | yes — revision 2 |
| A13 | Escrow is a single USD hold per job, internally earmarked per milestone | per-milestone holds would change refund rules | yes — revision 2 |
| A14 | Reviews are visible to the public without login | gating reviews behind login would shrink social proof | yes — revision 3 |
| A15 | Reviews publish immediately with no approval queue, and abuse is handled reactively | a pre-moderation queue would change admin workload and add a pending state | yes — revision 3 |
| A16 | Wallet funding is **manual**: posters transfer outside the app and admin approves each deposit | an integrated gateway would need a provider, webhooks and PCI scope | yes — revision 4 |
| A17 | Admin may approve a deposit for a different amount than requested (e.g. net of fees) | crediting the requested amount would create money that never arrived | yes — revision 4 |
| A18 | Bank and crypto details are supplied by admin, not generated per poster | per-poster addresses would need a wallet provider | yes — revision 4 |

---

## Open questions

> `Deferred: non-blocking` means the item does not stop release 1. Anything else
> with status `open` blocks the spec gate by design.

| # | Question | Blocks | Status | Impact |
|---|---|---|---|---|
| Q1 | Is one freelancer hired per job, or can a poster hire several against one escrow? | data model, escrow | answered | A8: one hire per job in release 1 |
| Q2 | Does the poster see the freelancer's proposed amount before hiring, and does hiring at a different amount require re-funding escrow? | hiring flow | open | Deferred: non-blocking — release 1 pays the budgeted amount; proposed amount is informational |
| Q3 | Where does the FX rate come from, how often is it refreshed, and what happens if it is stale for days? | display conversion | open | Deferred: non-blocking — daily refresh, cache with last-known fallback, rate stored per record |
| Q4 | Which SMTP provider will the owner configure? | email | open | Deferred: non-blocking — any SMTP works; owner configures at deploy |
| Q5 | Should suspended users' open jobs be auto-paused? | job lifecycle | open | Deferred: non-blocking — release 1 leaves jobs visible and flags them in admin |
| Q6 | Is a soft-deleted account's public profile hidden immediately? | privacy | open | Deferred: non-blocking — default to hidden immediately |
| Q7 | Can a milestone be delivered retroactively out of order (e.g. freelancer does 1 and 3 first)? | milestone lifecycle | open | Deferred: non-blocking — release 1 requires strict order for release, but delivery may be marked in any order |
| Q8 | Should a review be deletable by the reviewer themselves? | reviews | open | Deferred: non-blocking — release 1 lets only admin delete; reviewers may edit their own || Q9 | What is the maximum logo file size and allowed formats? | branding | open | Deferred: non-blocking — PNG/JPG/SVG up to 2 MB |
| Q10 | Does a job with milestones show each milestone's amount publicly? | job display | open | Deferred: non-blocking — release 1 shows the total budget publicly and milestone breakdown only to the hired freelancer |
| Q11 | Should an unapproved deposit request expire after N days? | deposit flow | open | Deferred: non-blocking — release 1 leaves requests pending indefinitely rather than auto-rejecting |
| Q12 | May a poster submit multiple concurrent deposit requests? | deposit flow | open | Deferred: non-blocking — release 1 allows it; each is approved independently |
| Q13 | For crypto deposits, is the USD amount taken from the transfer-date rate or chosen by admin? | deposit approval | open | Deferred: non-blocking — release 1 lets admin choose the credited USD amount and record the reference |
| Q14 | Are posters allowed to fund wallets before account approval? | deposit flow | open | Deferred: non-blocking — release 1 requires an approved account to submit a deposit |

---

## Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Money arithmetic bugs (float, rounding, double-spend) | medium | high — corrupts all balances | integer cents, derived balances, ledger reconciliation test in CI |
| Escrow double-release or double-refund | medium | high — invents money | idempotency keys per **milestone** transition + unique constraints |
| Milestone total drifting from job budget | medium | high — unpayable job | validate sum at creation and re-verify before each release |
| Review abuse or spam (open review model) | **high** | medium — reputational damage | per-account + per-source throttling, user flagging, admin hide/delete, audit trail. **Accepted residual risk:** reviews are unverifiable by design — a reviewer need not have worked with the freelancer, so a rating is a social signal, not proof of a transaction. |
| Defamation or fake negative reviews from a competitor | **medium** | **high** — legal and reputational | immediate admin takedown path, full audit trail, terms of use stating reviews are user-submitted, prominent report button |
| Admin-authored reviews read as organic user reviews | medium | medium — trust erosion | permanently attributed as "posted by the platform"; never disguised as a user review |
| Cross-user data leak (thread, wallet, KYC) | medium | high — privacy breach | object-level policy tests for every entity |
| Branding hard-coded in a template | medium | low — inconsistent surface | settings-driven accessor only; test that no view contains the literal brand |
| NGN leaking in through seed data or a provider default | medium | medium — violates a stated requirement | validation whitelist + a test that greps rendered output for NGN |
| Shared-host limits (no queue worker, symlink failure) | high | medium — features silently dead | capability sheet verified before build; cron-driven queue proven in a milestone |
| Scope creep into disputes/ratings/real-time chat | high | medium — release never ships | explicit out-of-scope list; changes require a requirements update |
| KYC documents stored insecurely | medium | high — serious privacy harm | private disk outside webroot, owner+admin access only, never a public URL |
| Admin as escrow is a single point of failure | medium | medium — demo only, acceptable | every action audited with reason; reconcile report for admin |

---

## Definition of done for release 1

1. Every FR above has passing acceptance evidence in `.aidev/evidence/`.
2. `aidev gate` passes G0 through G8.
3. A full journey runs in a real browser: register â†’ approve â†’ fund escrow â†’
   publish â†’ apply â†’ hire â†’ message â†’ deliver milestone 1 â†’ accept â†’ admin
   release milestone 1 â†’ deliver milestone 2 â†’ accept â†’ admin release â†’
   withdraw â†’ admin pay â†’ leave a review.
4. Every wallet balance reconciles to the ledger, demonstrated by a test.
5. Milestone totals always equal the job budget, demonstrated by a test.
6. Changing the site name and logo updates every surface, demonstrated by a test.
7. No NGN value appears in any rendered page, seed or fixture, demonstrated by a test.
8. No Critical or High security finding is open.
9. A human has signed `human_approval` in the release checklist.
