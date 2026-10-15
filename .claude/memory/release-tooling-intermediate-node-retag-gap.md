---
name: release-tooling-intermediate-node-retag-gap
description: FIXED in shop-ce#157 — bin/release used to reuse an intermediate node's latest tag while bumping that node's child pin → orphaned edit; the planner now forces a cut
type: reference
---

# bin/release: intermediate "fat" nodes can get an orphaned constraint edit

> **Fixed in shop-ce#157 (2026-05-27).** `ReleasePlanner` now forces a cut
> when a package receives a downstream constraint edit
> (`willReceiveDownstreamEdit()`); the dry-run labels it
> `cut-new-tag (downstream changed)`. Covered by `ReleasePlannerTest`.
> Exception: a child reached only over a back-edge is resolved after its
> parent, so it can't force the parent's cut (harmless today: the leaves pin
> `shop-ce` `^1.2`, which every 1.x satisfies). The `/release` skill still
> checks every edited package has a cut row, as a safety net. The analysis
> below is kept as background. Its `--bump shop-metapackage-ce=<version>`
> remedy never worked: `--bump` only sets the version
> of a cut that already happens (it never forces one), and `TagCutter` tags
> shop-ce and the metapackage at `--to` unconditionally.

Discovered during the metapackage fold-OUT (issue o3-shop/o3-shop#169), where
`o3-shop/shop-metapackage-ce` became a first-class intermediate node again
(graph: `o3-shop → shop-metapackage-ce → shop-ce → leaves`).

`CandidateVersionResolver` decides reuse-vs-cut per package from the
**pre-edit** state: if a package's latest tag is newer than its `from_pin`
and stability-compatible, it returns `CASE_USABLE_TAG` ("reuse-latest-tag")
— *without* considering that a downstream constraint edit will change that
package's own `composer.json`.

Concrete symptom in the `--from v1.6.0 --to v1.6.1-RC8` dry-run:
- `shop-ce` → reuse `v1.6.1-RC6`.
- `shop-metapackage-ce` → reuse `v1.6.1-RC5`, **but** the plan also emits a
  constraint edit `metapackage composer.json: shop-ce v1.6.1-RC5 → v1.6.1-RC6`.
- That edit would commit to the metapackage's `b-1.6` but **no new metapackage
  tag is cut**, so the published `RC5` still pins shop-ce `RC5`. `o3-shop`
  keeps pinning metapackage `RC5`. The shop-ce bump is **orphaned** — never
  reaches an installable artifact.

In the folded-IN world this never bit because `o3-shop` (the only "fat" node)
is the root and is skipped by the candidate loop (`ReleasePlanner` line ~111),
so it was never "reused". An intermediate fat node (the metapackage) exposes
the gap.

**Implication for a clean cut:** force the metapackage (and decide shop-ce /
the o3-shop tag) explicitly — e.g. `--bump shop-metapackage-ce=<version>` —
or enhance the resolver so a package with a pending constraint edit is treated
as `NEEDS_NEW_TAG` rather than reused. Also open: `o3-shop/o3-shop` is NOT a
candidate (skipped), so how the thin o3-shop project itself gets its release
tag needs confirming in `LiveExecutor`.

See also [[project_metapackage-fold-out]].
