---
name: module-versioning-convention
description: Shop-dependent modules get one release line per shop line (b-X.Y, tags vX.Y.n, shop-ce ~X.Y.0); main follows the newest line. paypal-checkout is the first; bin/release enforces it via ReleaseLine.
type: project
---

# Module versioning: one release line per shop line

Decided by Ralf Trapp on 2026-11-05 (o3-shop/o3-shop#274; tooling #275). Public
write-up: o3-shop/o3-documentation, "Module versioning" page (PR for #274, pending
2026-11-05).

**The standard**, for every module whose code depends on the shop version:

1. **Branch per shop line:** `b-1.5`, `b-1.6`, `b-1.7`, `b-2.0`, … Fixes land in the
   oldest affected line and are merged forward (1.5 → 1.6 → 1.7 → 2.0).
2. **Version names the shop line:** tags on `b-X.Y` are `vX.Y.n` (shop 1.7 → module
   `v1.7.0`, `v1.7.1`, …). Only the patch counts independently. Never a minor/major
   bump inside a line.
3. **Each line declares its shop:** `"o3-shop/shop-ce": "~X.Y.0"` in the module's
   `composer.json`; the metapackage on `b-X.Y` requires the module with `~X.Y.0`.
   Composer then picks the right module line for any shop.
4. **`main` = the newest line.** Protected, never committed to directly; moved by the
   merge-back PRs `bin/release` opens for final releases. Default branch = `main`.
5. **Shop-independent libraries** (e.g. `o3-shop/paypal-client`, a pure PayPal REST
   client) keep a single line, no shop constraint.

**Status (2026-11-05):** `o3-shop/paypal-checkout` (repo `paypal-module`, fork of OXID's
`osc_paypal`) is the first module on this convention; the others (gdpr-optin-module,
usercentrics, …) follow. Not done yet: paypal-module still releases from `b-1.0` (tags
`v1.0.x`), has shop-ce only in `require-dev` (`^1.7`), and the metapackage pins it exactly
(`v1.0.1`). GitHub default branches are still `b-1.0` (paypal-module) and `b-7.0.x`
(paypal-client), not `main`. paypal-client also has a divergent old `b-1.0` (4 ahead / 96
behind `b-7.0.x`; its `setTrackingId()` commit is not on `b-7.0.x`); never map it there. paypal-client's `main` is still the stale 2022 branch: reset it to `b-7.0.x`
before its next cut in a final release, or that release opens a merge-back into it.

**What bin/release enforces (tags only):** `ReleaseLine::LINE_VERSIONED_PACKAGES` is the
opt-in list. For those, on a `b-X.Y` branch only `vX.Y.*` tags count as the latest tag, and
`TagCutter` refuses any new tag outside the line or not above the line's latest tag. An
opted-in package mapped to anything but `b-X.Y` (`main` included, which is also the fallback
for a missing map key) is a `Plan failed:` error. paypal-checkout's `b-1.0` counts as line `v1.0.*` (patch bumps only). The composer constraints (shop-ce
`~X.Y.0`, the metapackage's `~X.Y.0`) are convention, not checked; `ConstraintUpdater` keeps
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
