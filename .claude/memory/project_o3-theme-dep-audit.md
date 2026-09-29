---
name: project_o3-theme-dep-audit
description: Pre-existing npm vulnerability in o3-theme (brace-expansion) that blocks test-all-coverage gate; tracked here so it's not re-investigated
type: project
---

`./docker.sh test-all-coverage` runs `npm audit` on o3-theme before the PHP tests and aborts on any vulnerability. As of 2026-05-21 there is one unfixed moderate vulnerability:

- **Package:** `brace-expansion 5.0.2–5.0.5`
- **Advisory:** GHSA-jxxr-4gwj-5jf2 — Large numeric range defeats documented `max` DoS protection
- **Fix:** `npm audit fix` (patch/minor bump available)

**Why not fixed yet:** o3-theme is an external git repo (not shop-ce). The fix must be committed inside the o3-theme repo's `package-lock.json` and its bundle rebuilt. This is tracked here until resolved.

**How to apply:** When running the full gate and it aborts at npm audit, verify this is the same pre-existing vulnerability and not a new one. Run the PHP tests directly with `./docker.sh test --fast` to bypass the audit gate for PHP-only changes.

## `npx gulp prod` on o3-theme main regresses committed `out/` files (2026-09-28)
- The build overwrites `out/o3-theme/src/js/widget/{filter,rating,remove-from-notice,shippingaddress}.js` with older German-comment versions from the build sources — someone edited `out/` directly, so source and output have drifted. It also re-adds vendor prefixes to `main.css`.
- **How to apply:** after `npm audit fix` for build-only deps, commit just `package-lock.json` and discard the rebuild (`git checkout -- out/`). Only rebuild when shipped runtime code actually changed, and diff `out/` before committing. Done this way in o3-Theme#61.
