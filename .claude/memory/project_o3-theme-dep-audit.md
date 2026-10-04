---
name: project_o3-theme-dep-audit
description: The test-all gate audits only o3-theme's runtime npm deps (--omit=dev); build-tool advisories (gulp/braces) no longer block it. History of the audit blockers.
type: project
---

`./docker.sh test-all(-coverage)` runs `npm audit --omit=dev` on o3-theme before the PHP tests and aborts on any vulnerability **in the theme's production dependencies** (bootstrap, its peer @popperjs/core, splide — all bundled into `out/`). Build tooling (gulp, del, …) is not audited by the gate since o3-shop/o3-shop#243.

**Gap:** PhotoSwipe ships (product-details gallery, `tpl/page/details/inc/pictures.tpl`, `tpl/layout/base.tpl`) as a hand-vendored v4.1.1 in `out/o3-theme/src/js/libs/`, while `package.json` lists `photoswipe ^4.1.3` under devDependencies and gulp never copies it. No audit (old or new) checks the shipped copy. Tracked in o3-shop/o3-shop#245.

**Why:** on 2026-10-04 the full audit reported 12 high, all from one advisory, GHSA-vfj7-8cjw-p6xm (`braces` ≤ 3.0.3, via micromatch → fast-glob/chokidar → del/gulp). No patched `braces` exists; npm's only "fix" was downgrading gulp/del. `npm audit --omit=dev` was clean. The maintainer chose to scope the gate to runtime deps (option 1 on #243). Earlier (2026-05-21) a moderate brace-expansion advisory (GHSA-jxxr-4gwj-5jf2) had blocked the gate the same way.

**How to apply:** if the gate aborts at npm audit now, it is a runtime dependency — fix it in the o3-theme repo (`npm audit fix --omit=dev`, commit `package-lock.json`). Build-tool advisories show up only with a plain `npm audit` in the theme; handle them in o3-theme on their own schedule, not as a shop-ce gate blocker.

## `npx gulp prod` on o3-theme main regresses committed `out/` files (2026-09-28)
- The build overwrites `out/o3-theme/src/js/widget/{filter,rating,remove-from-notice,shippingaddress}.js` with older German-comment versions from the build sources — someone edited `out/` directly, so source and output have drifted. It also re-adds vendor prefixes to `main.css`.
- **How to apply:** after `npm audit fix` for build-only deps, commit just `package-lock.json` and discard the rebuild (`git checkout -- out/`). Only rebuild when shipped runtime code actually changed, and diff `out/` before committing. Done this way in o3-Theme#61.
