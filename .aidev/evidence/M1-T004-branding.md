# T-004 Verification — Settings and branding

- **Requirement IDs:** FR-027 (CMS site settings), FR-035 (brand name + logo), AC-020
- **Environment:** local — PHP 8.3.30, Laravel 12.73.0, Pest 4.4.1, SQLite
- **Date:** 2026-11-20

## Tally

```
passed: 129 · failed: 0 · blocked: 0
```

## Results

| # | Test | Expected | Actual | Status |
|---|---|---|---|---|
| 1 | Falls back to defaults when nothing configured | Trust-Prime + tagline | exact | passed |
| 2 | Persists and returns a changed brand name | new name | new name | passed |
| 3 | Survives a fresh read from the database | new name after cache flush | new name | passed |
| 4 | **Refuses an unknown setting key** | throws | `InvalidArgumentException` | passed |
| 5 | Derives initials for the wordmark fallback | TP / A / NW | exact | passed |
| 6 | Stores an uploaded logo and points the setting at it | `brand/…` path | exact | passed |
| 7 | **Randomises the stored filename** (client name untrusted) | no "evil", no ".." | clean | passed |
| 8 | Deletes the previous logo on re-upload | old missing, new exists | exact | passed |
| 9 | Restores the wordmark when the logo is removed | `hasLogo` false | false | passed |
| 10 | **Rejects an SVG logo** | throws | throws | passed |
| 11 | Rejects a non-image file | throws | throws | passed |
| 12 | Rejects an oversized image | throws | throws | passed |
| 13 | Validation rules match the enforced limits | contains max + mimes, no svg | exact | passed |
| 14 | Provides a public URL only when a logo exists | null → url | exact | passed |
| 15 | **Static guard: no hardcoded brand in any Blade file** | 0 offenders | 0 | passed |
| 16 | **Static guard: no hardcoded email in any Blade file** | 0 offenders | 0 | passed |
| 17 | The settings surface is deliberate (exact key list) | 6 named keys | exact | passed |
| 18 | Component renders the brand as a wordmark with no logo | name + initials, no img | exact | passed |
| 19 | Component renders the uploaded logo instead of the wordmark | img with alt | exact | passed |
| 20 | Output changes when the name changes | before ≠ after | exact | passed |
| 21 | Falls back to the wordmark after logo removal | no img, has initials | exact | passed |

## Both guards were tested by breaking them

Injected `resources/views/_violation.blade.php` containing a literal brand name
and a literal email, then ran the suite:

```
FAIL  it contains no hardcoded brand name in any blade template
Hardcoded brand name found. Use SiteSettings::brandName() instead:
resources/views/_violation.blade.php

FAIL  it contains no hardcoded contact details in any blade template
```

Both fired, both named the file. Removed the file; suite returned to 129 passing.

## Why SVG is rejected

An SVG is an XML document that can carry `<script>`. A logo is served from the
site's own origin on every page, so an uploaded SVG is a **stored-XSS vector**
that an admin could introduce accidentally by uploading an innocent-looking
asset from an untrusted source.

Rejecting SVG costs nothing here — a raster logo at 2× density is visually
equivalent for a header mark.

## Design decisions recorded

1. **Key/value table, not a wide column-per-setting row.** Settings are read far
   more often than written, and adding one should not need a migration.
2. **Unknown keys are refused.** `SiteSettings::set()` validates against a
   declared list, so the setting surface stays deliberate rather than becoming an
   untyped store that silently accepts anything.
3. **Per-request cache, flushed on write.** The brand appears in the header,
   footer and page titles; hitting the database several times per render is waste
   on shared hosting. `flush()` exists so tests and new requests see reality.
4. **Previous logo deleted only after the new one is stored.** A failed upload
   must never leave the site with no logo at all.
5. **Text wordmark fallback is mandatory.** The site is never brandless between
   first deploy and first upload — initials derive from the brand name so the
   fallback adapts automatically when the name changes.
6. **Static scan instead of a runtime test for "everywhere".** A runtime test can
   only observe pages that happen to render during the run. A static scan
   catches the template nobody visited.

## Sanitation

```
composer audit → No security vulnerability advisories found
```

## Reproduction

```
php artisan migrate
./vendor/bin/pest
```

Expected: 129 passed (224 assertions).

## What is NOT yet proven

- An actual admin **UI** for editing settings (the service and validation exist;
  the controller and Blade form are part of the admin milestone, T-022)
- Email rendering using the brand (no mailables yet — T-018)
- That the uploaded logo survives a deploy (storage persistence is a T-030 concern)

## Note for later

`LogoUploader::store()` writes to the `public` disk. On the shared host,
`storage:link` may not work (recorded in `hosting-capability.md`). If it fails,
the logo URL must be served through a controller route instead. That fallback is
already planned for KYC and deposit proofs, so the pattern exists — but it is
**not yet implemented for the logo** and must be verified at deploy.
