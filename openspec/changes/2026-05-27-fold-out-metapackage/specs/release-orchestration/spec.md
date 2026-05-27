## MODIFIED Requirements

### Requirement: Tier-by-tier walk in dependency order

The CLI SHALL process release-eligible repos in topological order: tier 0
(leaves) first, then each successive tier, ending at `o3-shop` (the
highest tier). With `o3-shop/shop-metapackage-ce` as the compilation, the
order is leaves → `shop-ce` → `shop-metapackage-ce` → `o3-shop`. Within a
tier, order is unspecified. The CLI SHALL not begin tier N+1 work until
every tier-N repo has completed its release flow.

#### Scenario: Linear chain

- **WHEN** the dep graph is
  `o3-shop → shop-metapackage-ce → shop-ce → smarty`
- **THEN** `smarty` completes (or skips) before `shop-ce`, `shop-ce`
  before `shop-metapackage-ce`, and `shop-metapackage-ce` before `o3-shop`

#### Scenario: Diamond dependency

- **WHEN** the dep graph has `shop-ce` and `testing-library` both
  requiring `shop-facts`
- **THEN** `shop-facts` completes before either `shop-ce` or
  `testing-library` starts

## ADDED Requirements

### Requirement: Release-eligible repos exclude .next-bump from dist archives

Every release-eligible repo's `composer.json` SHALL include
`"archive": { "exclude": [".next-bump"] }` (additive to any existing
archive config). This SHALL prevent the `.next-bump` marker from being
included in dist archives, including dev/branch installs.

#### Scenario: shop-ce composer.json carries the exclude

- **WHEN** the change ships
- **THEN** `shop-ce/composer.json` has an `archive` block whose `exclude`
  array contains `.next-bump`

#### Scenario: Dev install does not include .next-bump

- **WHEN** a consumer runs `composer require o3-shop/shop-ce:dev-b-1.6`
  while `shop-ce`'s release branch contains a `.next-bump` file
- **THEN** the resulting `vendor/o3-shop/shop-ce` directory does not
  contain `.next-bump`
