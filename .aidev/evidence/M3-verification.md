# M3 Verification — Milestone 3

- **Requirement IDs:** set the requirement ids
- **Environment:** <!-- local / staging / production-smoke -->
- **Browser(s):** <!-- Chromium 1xx, Firefox 1xx, WebKit -->
- **Date:**

## Tally

```
passed: 0 · failed: 0 · blocked: 0
```

## Results

| # | Test | Level | Expected | Actual | Status |
|---|---|---|---|---|---|
| 1 | | unit | | | passed |
| 2 | | integration | | | passed |
| 3 | | api | | | passed |
| 4 | | e2e | | | passed |

**Status values:** `passed` · `failed` · `blocked`

## Blocked items

> A blocked test is not a pass. Each row must name what blocks it and who owns
> unblocking it.

| Test | Blocker | Owner | Impact |
|---|---|---|---|
| | | | |

## Evidence artefacts

| Artefact | Path |
|---|---|
| Screenshot (failure or key state) | |
| Playwright trace | |
| Console/network log | |
| Command output | |

## Permitted and forbidden actions tested

> A login success alone is not access-control evidence.

| Case | Role | Expected | Observed | Status |
|---|---|---|---|---|
| Direct URL to /admin | customer | denied | | |
| Another customer's order by ID | customer | denied, no data | | |
| Price/stock tampering in cart POST | customer | server recomputes | | |

## Reproduction steps for any failure

```
1.
2.
3.
```

## Root cause (if a failure exists)

- **Hypothesis:**
- **Evidence:**
- **Smallest fix:**
- **Regression test added:**
