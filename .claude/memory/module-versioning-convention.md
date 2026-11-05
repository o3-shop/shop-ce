---
name: module-versioning-convention
description: Shop-dependent modules get one release line per shop line (b-X.Y, tags vX.Y.n, shop-ce ~X.Y.0); main follows the newest line. paypal-checkout is the first; bin/release enforces it via ReleaseLine.
type: project
---

# Module versioning: one release line per shop line

Decided by Ralf Trapp on 2026-11-05 (o3-shop/o3-shop#274; tooling #275). Public
write-up: docs.o3-shop.com "Module versioning" (o3-shop/o3-documentation#13, #276; open
2026-11-05).

**The standard**, for every module whose code depends on the shop version:

1. **Branch per shop line:** `b-1.5`, `b-1.6`, `b-1.7`, `b-2.0`, … Fixes land in the
   oldest affected line and are merged forward (1.5 → 1.6 → 1.7 → 2.0).
2. **Version names the shop line:** tags on `b-X.Y` are `vX.Y.n` (shop 1.7 → module
   `v1.7.0`, `v1.7.1`, …). Only the patch counts independently. Never a minor/major
   bump inside a line.
3. **Each line declares its shop:** `"o3-shop/shop-ce": "~X.Y.0"` in the module's
   `composer.json` (in `require`), so Composer refuses a module line that doesn't fit the
   shop. The metapackage keeps **exact** pins (`"o3-shop/paypal-checkout": "v1.7.4"`),
   rewritten by `bin/release`: decided on #274 because a `~X.Y.0` pin is never rewritten
   by `ConstraintUpdater` (shop tags would stop recording the shipped module version) and
   `TagFromConstraint` would read it as `vX.Y.0` (release notes always from `.0`).
   Older lines' merge-back PRs into `main` are closed unmerged; only the newest line's is
   merged.
4. **`main` = the newest line.** Protected, never committed to directly; moved by the
   merge-back PRs `bin/release` opens for final releases. Default branch = `main`.
5. **Shop-independent libraries** (e.g. `o3-shop/paypal-client`, a pure PayPal REST
   client) keep a single line, no shop constraint.

**Status (2026-11-05):** `o3-shop/paypal-checkout` (repo `paypal-module`, fork of OXID's
`osc_paypal`) is the first module on this convention; the others (gdpr-optin-module,
usercentrics, …) follow. Done 2026-11-05: paypal-module has protected `b-1.7` (shop-ce
`~1.7.0` in `require`, `metadata.php` 1.7.0) and `b-2.0` (`~2.0.0`, 2.0.0, untested on a 2.0
shop); default branch `main` (still `v1.0.1` = `b-1.0` until `v1.7.0`'s merge-back); the map
points at `b-1.7`. **The first release after that needs `--bump paypal-checkout=v1.7.0`**
(no `v1.7.*` tag yet → `Plan failed:` otherwise). The metapackage pins it exactly (`v1.0.1`
until then). No `b-1.6`/`b-1.5` yet: shop 1.5 ships the classic `o3-shop/paypal-module`
(repo `o3-shop/paypal`), the 1.6 metapackage has no PayPal Checkout; `b-1.0-1.6-support`
holds the old 1.6 patch. paypal-client also has a divergent old `b-1.0` (4 ahead / 96
behind `b-7.0.x`; its `setTrackingId()` commit is not on `b-7.0.x`); never map it there. paypal-client's `main` was reset to `b-7.0.x` on 2026-11-05 (the stale 2022 commit is
kept as `archive/main-2022`); paypal-module's 58 upstream-only branches were pruned (#274).

**What bin/release enforces (tags only):** `ReleaseLine::LINE_VERSIONED_PACKAGES` is the
opt-in list. For those, on a `b-X.Y` branch only `vX.Y.*` tags count as the latest tag, and
`TagCutter` refuses any new tag outside the line or not above the line's latest tag. An
opted-in package mapped to anything but `b-X.Y` (`main` included, which is also the fallback
for a missing map key) is a `Plan failed:` error. The module's shop-ce
`~X.Y.0` constraint is convention, not checked; `ConstraintUpdater` keeps
whatever operator a pin already has. A line that starts with an RC (`v1.7.0-RC1`) gets
`v1.7.1` as its first final from a patch bump; use `--bump <slug>=v1.7.0` for `v1.7.0`.
The first tag of a new line is an exact bump: `--bump paypal-checkout=v2.0.0`. Moving a module onto the convention =
create its `b-X.Y` branches, add it to that list, and map it in `DefaultBranchResolver` to the
line of the shop being released (the same `b-X.Y` as shop-ce), never to a newer line.

**Why opt-in, not derived:** deriving "line-versioned" from existing `vX.Y.*` tags
would treat a brand-new line (no tags yet) as a normal repo and bump from the previous
line's highest tag. The shop-line repos (testing-library, shop-facts, …) branch `b-1.7`
but tag their own versions (`v1.2.x`), so they must stay off the list.

**Open:** a final release from an older line also opens a merge-back into `main`; under
this convention only the newest line's merge-back belongs there.

See also [[release-tooling-branch-selection]].
