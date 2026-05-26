# CI Notice — Composer audit baseline drift after metapackage update

**Date:** 2026-05-26  
**Analyst:** Nick Lorenz  
**Severity:** Low — no vulnerability introduced; CI gates that diff against a stored baseline will false-positive

---

## What changed

The metapackage update resolved previously open `composer/composer` advisories. The `composer audit` output now contains **fewer entries** than the stored baseline that was recorded before this release.

---

## Why this matters

CI pipelines that enforce audit cleanliness by diffing the current `composer audit` output against a committed baseline file will detect a non-empty diff and abort — even though the diff represents **removed advisories** (an improvement, not a regression).

This is a known failure mode of diff-based audit gating: the gate cannot distinguish between "new vulnerability appeared" and "old vulnerability was resolved". Any reduction in the advisory list looks like a change and triggers the abort.

---

## Impact

| Audience | Impact |
|---|---|
| O3-Shop own CI | Affected — baseline must be re-recorded after this release |
| Downstream module authors using diff-based audit gating | Affected — same re-baseline required |
| Downstream module authors using exit-code-only gating | Unaffected |
| End users / shop operators | None |

---

## Required action

**Re-baseline your audit report** after pulling this release:

```bash
composer audit --no-dev --format=json > .composer-audit-baseline.json
# commit the updated baseline
```

Or, if your CI gate uses plain-text output:

```bash
composer audit --no-dev > .composer-audit-baseline.txt
git add .composer-audit-baseline.txt && git commit -m "chore: re-baseline composer audit after 1.6.x metapackage update"
```

---

## Changelog note

> This release resolves open `composer/composer` security advisories via the metapackage update. If your CI pipeline diffs `composer audit` output against a stored baseline, re-baseline after upgrading — the advisory count has decreased.
