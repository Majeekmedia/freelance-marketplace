# Design references — Trust-Prime

Drop reference images in this folder. The agent reads them **before** writing any
UI code, so the design is grounded in something you actually like rather than a
default AI aesthetic.

## How to add references

1. Save images into this folder (any of `.png`, `.jpg`, `.jpeg`, `.webp`).
2. Name them so the intent is obvious — the filename becomes part of the brief:

```
01-hero-typography-pinterest.png
02-marketplace-card-layout.png
03-dashboard-density-dribbble.jpg
04-color-palette-muted.png
ANTI-05-too-generic-dont-do-this.png
```

3. Then say: *"read the design references"* — or just ask me to build the next UI
   milestone and I will read this folder first.

## Naming conventions that help

| Prefix | Meaning |
|---|---|
| `01-`, `02-` … | ordered by importance |
| `hero-`, `card-`, `dashboard-`, `form-`, `empty-state-`, `mobile-` | which surface it informs |
| `LIKE-` | I want this feel |
| `ANTI-` | do **not** do this — useful for ruling out AI-slop patterns |
| `palette-`, `type-`, `spacing-` | specific system inputs |

`ANTI-` files are genuinely useful. If you show me three Pinterest shots you love
and one you hate, the "hate" one often teaches more.

## What to note about each reference

When you add images, add one line per image below. If you skip this, I will infer
from the image alone — and inference is where generic output creeps in.

| File | What you like about it | Where it applies |
|---|---|---|
| `LIKE-01-landing-hero-and-jobs.jpg` | Owner: "I like the way this one hero section is, then the Find your favourite jobs section that lists the available jobs." Confirmed intent: hero section + a job-listing section on the landing page. | Landing page only. Dashboards are to be designed independently and more freely once the landing page is right. |

## What I will NOT do with these

- I will not copy another site's layout wholesale.
- I will not reproduce licensed illustrations or copyrighted artwork.
- I will not assume a colour or typeface is available just because it appears in a
  screenshot — I will pick an equivalent that is actually licensable and
  performance-safe on shared hosting.

## What I will extract from a reference

- **Type scale** — relative sizes, weight contrast, tracking
- **Layout rhythm** — grid, whitespace density, alignment discipline
- **Colour relationships** — not exact hex values, but the *system* (what is
  primary, what is surface, what is accent, how contrast is used)
- **Component shapes** — radius, border treatment, elevation strategy
- **Motion hints** — how things appear and respond
- **Density** — how much is on screen at once

---

## Standing design brief for Trust-Prime

Recorded now so the design has a spine even before references arrive.

**Page kind:** marketplace — a public landing/job-browse surface plus three
distinct authenticated dashboards (freelancer, poster, admin).

**Audience:** two-sided marketplace users who need to trust each other with money.
Freelancers scan for legitimacy signals. Posters scan for competence signals.
Both are evaluating **trust**, not delight.

**Vibe words:** trustworthy, dense-but-calm, editorial, credible. NOT playful,
NOT glassy, NOT gradient-heavy.

**Dial settings:** `DESIGN_VARIANCE: 6` · `MOTION_INTENSITY: 3` · `VISUAL_DENSITY: 5`

Rationale: this is trust-first transactional UI, not a marketing landing page. Per
the `design-taste-frontend` skill's inference table, "trust-first / regulated /
accessibility-critical" lands at `3-4 / 2-3 / 4-5`. I nudged variance to 6 because
the public job board benefits from a little editorial character, but kept motion
low — money screens should not animate.

**Anti-slop constraints for this project (non-negotiable):**

| Not allowed | Why |
|---|---|
| Purple/violet gradient hero on dark mesh | the single most recognisable AI default |
| Three equal feature cards in a row | templated and says nothing |
| Glassmorphism on cards | reads as decoration on a trust product |
| Inter + `slate-900` for everything | default stack, no brand |
| Infinite-loop micro-animations | distracting on money screens; wastes battery |
| Icon-only buttons in money flows | ambiguous and unsafe |
| Centred hero with a giant italic serif and a floating phone mockup | stock |

**Where character *should* come from instead:**
editorial typographic hierarchy · a restrained but distinctive accent colour ·
generous whitespace on the job board · strong status/badge language for escrow and
milestone states · a data-dense but disciplined admin table.

**Accessibility is a hard constraint here, not a preference** (NFR-005): all money
and status information must be legible without colour alone, so every state needs
a text or icon affordance in addition to colour.
