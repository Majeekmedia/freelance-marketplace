# Retrospective — Trust-Prime

> Written at the end of a phase (milestone, or the project). This is where raw
> friction becomes a lesson, and where a lesson either changes the kit or is
> honestly recorded as *not worth changing*.
>
> Raw in-the-moment notes live in `optimisation-log.md`. This file is the
> distilled version. Anything that changes the **kit** is promoted to
> `ai-dev-system/doctrine/LESSONS.md`.

---

## Retrospective 1 — Discovery → Design → Prototype (2026-11-20)

Covers: requirements revisions 1–4, design, tasks, milestones, steering, and the
landing-page prototype.

### What went well

| Thing | Why it worked |
|---|---|
| Discovery captured contradictions instead of smoothing them | The stack contradiction (cPanel vs Vercel) and the escrow ambiguity both surfaced *before* code existed. Cheap to fix, would have been expensive later. |
| Requirements returned to `draft` when money handling changed | Revision 4 added manual deposits. Treating that as a material change rather than a text edit is why the ledger design is coherent. |
| Rejected alternatives were written down | "Why not an SPA", "why not a balance column", "why not per-milestone escrow" are all answerable months from now. |
| The gate engine caught two of its own bugs | G6 accepting `0 passed` and G5 passing on nothing. Both were found because the system was used on a real project, not a demo. |
| Owner feedback was specific and fast | "Image shouldn't lead on mobile", "remove that panel", "hamburger for more pages" — each was a small, clear change. |

### What went badly

| Thing | Root cause | Cost |
|---|---|---|
| **A prototype file was deleted with no recovery** | No `git init` before editing; a `.\` path resolved to the same file | full rewrite of ~600 lines |
| A design mock contained a fake live feed | The agent produced "plausible-looking" content instead of honest placeholder | one wasted design iteration |
| Region-specific names appeared in a global product | Locale inferred from the developer's context, not the product's | one correction round |
| Four attempts spent trying to view an unrenderable image | Retried the same approach instead of reporting the limitation | wasted turns |
| Header overflowed at exactly 768px | Tested device widths, not breakpoint boundaries | one reactive fix |

### Keep

- Returning `requirements.md` to draft on material change.
- Rejected-alternatives section in the design.
- Numeric evidence in verification files (real measurements, real widths).
- Reporting blockers honestly in the tally rather than inflating `passed`.
- The `design-references/` folder and the `ANTI-` naming convention.

### Change

| Change | Where it went |
|---|---|
| G6 requires `passed > 0` | kit — `cli/lib/gates.js` + 3 self-tests |
| G5 requires at least one started milestone | kit — `cli/lib/gates.js` |
| `aidev new` + `aidev projects` | kit — CLI |
| Test adjacent breakpoint widths (720/721, 960/961) | kit — `doctrine/LESSONS.md` L-006 |
| Never hardcode a live-looking feed in a mock | kit — LESSONS L-004 |
| Demo copy must match the product's market, not the developer's | kit — LESSONS L-005 |
| State a tool limitation immediately instead of retrying | kit — LESSONS L-007 |

### Stop

- Writing "looks good" as a verification result. Every claim needs a number.
- Building UI before the requirements gate has genuinely passed.
- Creating a project without initialising git.

### Still open

| Item | Why it is not done | Risk if ignored |
|---|---|---|
| `aidev new` does not `git init` | needs a CLI change + a self-test | the exact data loss that already happened recurs on the next project |
| No `custody` gate for projects holding funds | needs a G7 rule + test | a future project holds user money with no recorded merchant-of-record decision |
| No retrospective requirement before G8 | needs a gate check | lessons stop being distilled once a project is "done" |

### Numbers

| Metric | Value |
|---|---|
| Requirements revisions | 4 |
| Functional requirements | 41 |
| Acceptance criteria | 33 |
| Tasks | 33 |
| Milestones | 12 |
| Kit bugs found by using the kit | 2 critical, 2 serious |
| Design iterations on the landing page | 4 |
| Files deleted and reconstructed | 1 (~600 lines) |
