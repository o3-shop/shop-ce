## Context

See `proposal.md`. The fold-in was applied to `o3-shop` (fat + `replace`)
but the metapackage was never archived and got re-added as an o3-shop
dependency, producing a double-`replace` that breaks `create-project`. The
`bin/release` tooling was written to assume the fold-in completed.

## Decisions

### Metapackage is the compilation; o3-shop is thin (revert to ≤ v1.6.0)

Two topologies fix the double-replace: (A) o3-shop is the sole compilation
and the metapackage is retired; (B) the metapackage is the sole
compilation and o3-shop is thin. (A) breaks in-place upgrades for deployed
installs that require the metapackage by name, so we choose **(B)** —
which is also exactly what shipped through v1.6.0, so it is a revert, not
a new design. One `replace` owner (the metapackage) serves both new
`create-project` installs and existing-install `composer update`.

### Adjust bin/release rather than revert it

`bin/release` was introduced together with the fold-in (v1.6.1-RC1), so a
git-revert would delete the tool. Instead we remove only the fold-in
assumptions. The change is almost entirely deletion:

- `FromSnapshotBuilder` no longer `unset`s `shop-metapackage-ce` from
  `from_pin[]` and no longer runs the synchronous "pre-fold-in
  indirection" pre-harvest. The metapackage is recursed into like any
  other node, so it lands in `from_pin[]` (a release candidate) and its
  children (shop-ce, themes, modules) are still harvested.
- First-write-wins ordering — the metapackage's exact `shop-ce` pin must
  beat testing-library's loose `^1.2` — is preserved **without** the
  pre-harvest because root `require` is merged before `require-dev` and
  the harvest queue is FIFO: the metapackage (in root `require`) is
  dequeued before testing-library (in root `require-dev`).
- `FromSnapshot` drops the `usedPreFoldInIndirection` /
  `preFoldInMetapackageVersion` metadata; `DryRunPrinter` drops the
  indirection log line.

The downstream machinery is unchanged: `DepTreeWalker` already recurses
into any `o3-shop/*` dep, and the pin-location → `ConstraintUpdater`
cascade already handles both directions (shop-ce re-tag → metapackage's
pin bumped; metapackage re-tag → o3-shop's pin bumped).

### Tooling handles both --from shapes

The recursion handles a thin `--from` (o3-shop requires the metapackage)
and a fold-in-era fat `--from` (o3-shop pins components directly) with no
branching. For the first folded-out cut, pass a pre-fold-in v1.6.0-era tag
as `--from` for the cleanest diff; do not pass a broken RC5+ tag.

## Risks

- Existing `v1.6.1-RC*` tags are broken; consumers pinning them still
  fail. Mitigation: delete the broken tags and re-cut — `create-project`
  takes the highest tag.
- The metapackage's component pins must be refreshed on `b-1.6` before the
  cut so the compilation is internally consistent.
