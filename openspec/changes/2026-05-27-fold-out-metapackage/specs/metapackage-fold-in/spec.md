## REMOVED Requirements

### Requirement: shop-metapackage-ce require list moves into o3-shop

**Reason**: The fold-in is reversed. The require list stays in
`shop-metapackage-ce`; `o3-shop` becomes a thin project requiring it. See
the new `metapackage-compilation` capability.

### Requirement: Deprecated and removed entries dropped during fold-in

**Reason**: Reversed. The dropped/kept-entries decision now applies to the
metapackage's own require list and is captured by
`metapackage-compilation`.

### Requirement: Bundled modules preserved during fold-in

**Reason**: Reversed. Captured by `metapackage-compilation` against the
metapackage's require list.

### Requirement: Replace clause moves to o3-shop

**Reason**: Reversed. The `replace: oxid-esales/oxideshop-metapackage-ce`
clause stays on `shop-metapackage-ce` (the single replace owner); o3-shop
has no `replace`.

### Requirement: shop-metapackage-ce repo archived after fold-in

**Reason**: Reversed. The metapackage stays published and advancing so
existing installs can `composer update` forward. Long-term removal is
rerouted to a phased procedure (issue #149), not archival.

### Requirement: Release-eligible repos exclude .next-bump from dist archives

**Reason**: Re-homed to `release-orchestration` (it is independent of the
fold direction).
