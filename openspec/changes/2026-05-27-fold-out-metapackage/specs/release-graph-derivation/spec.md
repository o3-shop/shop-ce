## MODIFIED Requirements

### Requirement: Build from_pin[] from --from snapshot

The CLI SHALL read `o3-shop/composer.json` at the `--from` tag and
recursively build a map `from_pin[repo]` of every `o3-shop/*` package to
its pinned version, walking through each package's `require`/`require-dev`
for further `o3-shop/*` deps. `o3-shop/shop-metapackage-ce` is an ordinary
node in this walk: when o3-shop requires it, it appears in `from_pin[]` as
a release candidate AND is recursed into so the packages it pins (shop-ce,
themes, bundled modules, framework deps) are harvested too. This map SHALL
be the per-repo anchor for the "did anything change?" check and the
starting point for cross-repo release notes.

#### Scenario: Thin o3-shop requires the metapackage

- **WHEN** `o3-shop/composer.json` at `--from` requires only
  `o3-shop/shop-metapackage-ce`, which in turn pins shop-ce, themes,
  demodata, and bundled modules
- **THEN** `from_pin[]` contains `shop-metapackage-ce` mapped to its
  pinned version AND an entry for each package the metapackage pins

#### Scenario: Fold-in-era o3-shop pins components directly

- **WHEN** `o3-shop/composer.json` at `--from` pins shop-ce, themes,
  demodata, and bundled modules directly (a fold-in-era tag with no
  metapackage require)
- **THEN** `from_pin[]` contains an entry for each pinned `o3-shop/*`
  package and no `shop-metapackage-ce` entry

### Requirement: Topological tier ordering

The CLI SHALL topologically sort the dep graph and assign tiers based on
depth (leaves at tier 0, `o3-shop` at the highest tier). Tier numbers are
not declared anywhere; they emerge from the sort.

#### Scenario: Leaf is tier 0

- **WHEN** `smarty` has no `o3-shop/*` deps
- **THEN** `smarty` is in tier 0

#### Scenario: o3-shop is the highest tier

- **WHEN** `o3-shop` requires `shop-metapackage-ce`, which requires
  `shop-ce` (tier 1) and tier-0 packages
- **THEN** `shop-metapackage-ce` is one tier below `o3-shop`, and
  `o3-shop` is at the highest tier

## REMOVED Requirements

### Requirement: Pre-fold-in `--from` triggers metapackage indirection

**Reason**: The fold-in is being reversed. `o3-shop/shop-metapackage-ce`
is now a permanent first-class node in the graph rather than a one-time
indirection anchor, so a `--from` that requires the metapackage is the
normal case (handled by the standard recursive harvest), not a
transitional special case. The metapackage is no longer dropped from
`from_pin[]`.
