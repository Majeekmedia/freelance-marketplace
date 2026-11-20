# .aidev — Trust-Prime

This directory holds the **evidence** that the project behaves as intended.
It is deliberately committed to the repository: if it is not written down, it
does not count.

```
.aidev/
├── config.json                  project + profile + commands
├── evidence/
│   ├── 00-inspection.md         read-only baseline (gate G0)
│   ├── Mn-verification.md       per-milestone test tally (gate G6)
│   ├── security-review.md       findings table with owners (gate G7)
│   └── release-checklist.md     release record + human approval (gate G8)
└── state.json                   optional notes for tooling
```

## Rules

- Evidence is written **after** running the checks, never before.
- A tally line looks like: `passed: 12 · failed: 0 · blocked: 0`
- Blocked items must name a **Blocker** and an **Owner**.
- Never put secret values in these files.
- Never edit `approved_by`, `human_approval` or a security finding's status
  without actually doing the thing.

## Commands

```bash
aidev status
aidev gate
aidev gate G6
aidev next
aidev evidence new M2
```
