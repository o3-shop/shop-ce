---
name: release-tooling-intermediate-node-retag-gap
description: bin/release reuses an intermediate compilation node's latest tag even when it will bump that node's child pin → orphaned edit
type: reference
---

# bin/release: intermediate "fat" nodes can get an orphaned constraint edit

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
