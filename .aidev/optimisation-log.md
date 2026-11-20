# Optimisation log — Trust-Prime

> The point of this file: the kit tells you *what* to do next (`aidev next`), but
> it cannot tell you whether the system itself is improving. This log is where we
> record friction, fixes and measured improvements **while we build**, so the kit
> gets better instead of the same friction recurring on every project.

## How to use it

Append one entry per **friction event**. A friction event is anything that:

- cost you more than ~15 minutes to work around
- made you guess at what the system wanted
- a gate flagged that felt wrong (false positive)
- a gate missed something it should have caught (false negative)
- required editing a template or skill to proceed

Do not log "it worked fine". Only log friction and improvement.

## Entry format

```
### YYYY-MM-DD · <short title>
Type:     friction | false-positive | false-negative | improvement | decision
Area:     gate G? / skill <name> / template <file> / CLI / docs
Observed: what actually happened
Cost:     roughly how much time or confusion
Fix:      what we changed (or propose)
Measure:  how we will know it improved
```

---

## Entries

### 2026-11-20 · Kit had no standard project location
Type:     friction
Area:     CLI
Observed: `aidev init <path>` required the author to remember and type an absolute
          path, and nothing listed existing projects.
Cost:     would recur on every new project; risk of projects scattered across disk.
Fix:      added `aidev new <slug>` (writes to `../projects/<slug>`) and
          `aidev projects` (lists every project with its blocking gate).
Measure:  `aidev projects` shows every project in one place; `aidev new` never
          requires a path.

### 2026-11-20 · **G6 accepted "0 passed · 0 failed" as a green run**
Type:     **false-negative (critical)**
Area:     gate G6 / template evidence/M-verification.md
Observed: The scaffold ships `M1-verification.md` containing
          `passed: 0 · failed: 0 · blocked: 0`. G6 only asserted `failed === 0`,
          so **an untouched scaffold satisfied the verification gate**. The
          project reached "G6 pass" with zero tests ever written, while M1 was
          still `in-progress`.
Cost:     **This is the exact failure mode the system exists to prevent** —
          false completion certified by the tool itself. Had it gone unnoticed,
          every project would have started with a rubber-stamped evidence file.
Fix:      G6 now requires `passed > 0` as well as `failed === 0`, with an
          explicit message: "tally says 0 passed — no tests were actually run".
          Added three self-tests: all-zero tally blocks, raw verification
          template blocks, and the gate fails overall on a zero tally.
Measure:  `aidev gate G6` now blocks a fresh project; kit suite went 112 → 115.

### 2026-11-20 · G5 could pass on a milestone with no status at all
Type:     false-negative
Area:     gate G5
Observed: The scaffold's `milestones.md` ships every milestone as `todo`, which
          meant G5's definition-of-done checks never ran on anything, and G5
          reported pass on an untouched project.
Cost:     medium — a milestone could be "in progress" in reality while the gate
          saw nothing to check.
Fix:      G5 now requires at least one milestone to be `in-progress` or `done`
          before it will pass, so a template of all-`todo` milestones is not
          mistaken for progress.
Measure:  fresh scaffold blocks at G5; lifecycle test confirms ordering.

### 2026-11-20 · Scaffold passes off placeholders as an inspection
Type:     false-negative
Area:     gate G0 / template evidence/00-inspection.md
Observed: `aidev init` writes `00-inspection.md` containing only headings and
          HTML-comment guidance. On a greenfield project this reads like a
          completed inspection when nothing has been inspected.
Cost:     low this time (caught immediately), but it could let a scaffold appear
          to satisfy G0 on a project with existing code.
Fix:      `md.hasSubstantiveContent()` already rejects comment-only sections, so
          G0 correctly blocks. **Template reworded** to state the greenfield
          reality explicitly and list what was *not* inspected.
Measure:  A fresh scaffold still shows G0 blocking; the file now says why.

### 2026-11-20 · Money custody risk was not surfaced by any gate
Type:     false-negative
Area:     gate G7 / skill security-review
Observed: The brief involves holding user funds, but nothing in the gate list asks
          "does this platform hold other people's money?" — a materially different
          risk from "does this app take a payment".
Cost:     potentially very high (regulatory, not just technical).
Fix:      recorded here and promoted to a requirements question (Q2/Q3). Proposed
          kit change: add a `custody` flag to G7 requiring an explicit
          merchant-of-record decision when requirements mention wallets/balances.
Measure:  G7 fails a wallet-based project with no recorded custody decision.

### 2026-11-20 · Laravel 11 is a security liability, not a safe pin
Type:     false-negative (critical)
Area:     steering/tech-stack.md / hosting-capability.md / T-001
Observed: The plan pinned Laravel 11 to stay PHP 8.2-compatible. composer audit
          reported 5 advisories against laravel/framework, all with an affected
          range of <12.71.0. Laravel 11.57.1 sits entirely inside that range and
          is out of security support.
Cost:     would have shipped a framework with known CVEs. One (signed-URL path
          confusion, medium) is directly relevant: this app will use signed URLs.
Fix:      Re-pinned to Laravel 12.73.0 — requires php ^8.2 (same constraint) and
          reports 0 advisories. Requirements, design, tech-stack and hosting
          capability all updated.
Measure:  composer audit returns "No security vulnerability advisories found."
Generalisation: "pin an older major for compatibility" silently trades security
          for convenience. Compatibility is answered by the package require block;
          security by composer audit. Both are one command.

### 2026-11-20 · Local PHP was misidentified
Type:     false-positive
Area:     hosting-capability.md
Observed: The sheet recorded local PHP as 8.2.4 from XAMPP and treated 8.3 syntax
          as unavailable. In fact php on PATH is Laravel Herd 8.3.30 — matching
          the host target exactly. XAMPP 8.2.4 was only one of two runtimes.
Cost:     drove a wrong framework choice for a while.
Fix:      Both runtimes recorded explicitly; Laravel 12 (php ^8.2) satisfies both.
Measure:  php -v on PATH and C:\xampp\php\php.exe -v both recorded.

### 2026-11-20 · Laravel scaffold overwrote the hardened .gitignore
Type:     false-negative
Area:     .gitignore
Observed: composer create-project writes its own .gitignore, replacing the project
          one and silently removing the KYC and deposit-proof exclusions added an
          hour earlier.
Cost:     identity-document and financial-evidence paths became committable.
Fix:      Restored as a clearly marked "Project additions" block after the Laravel
          block, so a future diff makes the removal obvious.
Measure:  git check-ignore -v .env passes; .gitignore contains "HARD RULE".
Generalisation: any scaffolder that writes dotfiles clobbers project policy files.
          Re-check .gitignore after every scaffold, and verify with
          git check-ignore rather than by reading.

### 2026-11-20 · Composer create-project fails on Windows with a path lock
Type:     friction
Area:     toolchain
Observed: Two attempts failed with "Could not delete ...vendor\composer\tmp-*",
          "can be due to an antivirus or the Windows Search Indexer".
Cost:     ~15 minutes and two wasted installs.
Fix:      Install to a directory outside the project, then copy the result in,
          excluding .git. Also removed a stray Dev_workspace\vendor left by the
          first failed attempt.
Measure:  third attempt completed at 112/112.
