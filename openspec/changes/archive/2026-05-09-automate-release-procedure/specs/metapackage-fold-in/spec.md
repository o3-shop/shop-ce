## ADDED Requirements

### Requirement: shop-metapackage-ce require list moves into o3-shop

The fold-in SHALL move every entry from
`shop-metapackage-ce/composer.json` `require` into
`o3-shop/composer.json` `require`, with two exceptions enumerated by
the next two requirements.

#### Scenario: Framework deps move

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` `require` contains exact-pinned
  entries for every Symfony, Doctrine, Composer, and Monolog package
  that was previously in
  `shop-metapackage-ce/composer.json` `require`

### Requirement: Deprecated and removed entries dropped during fold-in

The fold-in SHALL NOT carry `flow-theme`, `vortex-theme`, the o3-shop
fork of `paypal-module`, or any `tests-deprecated-ce`-style entries
into `o3-shop/composer.json`. `flow-theme` and `paypal-module` were
already removed from every v1.6.0 RC tag and the v1.6.0 final tag of
the metapackage; the fold-in makes the removal permanent. Users who
still want any of these MAY install them via `composer require`
explicitly.

#### Scenario: flow-theme dropped

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` does not require `o3-shop/flow-theme`

#### Scenario: vortex-theme dropped

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` does not require
  `o3-shop/vortex-theme`

#### Scenario: o3-shop paypal-module dropped

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` does not require the o3-shop fork
  of `paypal-module`

### Requirement: Bundled modules preserved during fold-in

The fold-in SHALL preserve `o3-shop/gdpr-optin-module`,
`o3-shop/usercentrics`, and `o3-shop/tinymce-editor` from the v1.6.0
state of `shop-metapackage-ce/composer.json` `require` into
`o3-shop/composer.json` `require`. They become candidates for the
release-graph walk like any other tier-0 dep.

#### Scenario: gdpr-optin-module preserved

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` requires
  `o3-shop/gdpr-optin-module` with the version that was pinned in
  the metapackage's v1.6.0 tag

### Requirement: Replace clause moves to o3-shop

The fold-in SHALL move
`replace: oxid-esales/oxideshop-metapackage-ce` from
`shop-metapackage-ce/composer.json` to `o3-shop/composer.json`. The
clause SHALL preserve the OXID-lineage marker that prevents hybrid
OXID + o3-shop installs.

#### Scenario: Replace clause present after fold-in

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` contains a `replace` block with
  the key `oxid-esales/oxideshop-metapackage-ce`

#### Scenario: Hybrid install at the replaced version installs only o3-shop

- **WHEN** a project requires both `o3-shop/o3-shop` and
  `oxid-esales/oxideshop-metapackage-ce: 6.4.3` (the version named
  by the replace clause)
- **THEN** Composer satisfies the `oxid-esales` requirement via
  o3-shop's `replace`; only `o3-shop/o3-shop` is installed; no
  actual `oxid-esales/oxideshop-metapackage-ce` package is downloaded

#### Scenario: Hybrid install at a divergent version is rejected

- **WHEN** a project requires both `o3-shop/o3-shop` and
  `oxid-esales/oxideshop-metapackage-ce` at a version other than
  `6.4.3` (e.g. `^7.0`)
- **THEN** Composer's resolver rejects the install with a "replaces
  X and thus cannot coexist with it" diagnostic and a non-zero exit
  code

### Requirement: shop-metapackage-ce repo archived after fold-in

After the fold-in is applied and merged, the
`o3-shop/shop-metapackage-ce` GitHub repo SHALL be archived. A final
tag SHALL pin its current state. Its README SHALL be updated to
point new consumers at `o3-shop/o3-shop`.

#### Scenario: Repo flagged archived

- **WHEN** the fold-in change is merged
- **THEN** the `o3-shop/shop-metapackage-ce` GitHub repository is in
  the "archived" state and rejects new pushes/PRs

#### Scenario: README points at o3-shop

- **WHEN** a user visits the archived
  `shop-metapackage-ce` repo
- **THEN** the README explains that the package was folded into
  `o3-shop/o3-shop` and links to it

### Requirement: Release-eligible repos exclude .next-bump from dist archives

Every release-eligible repo's `composer.json` SHALL include
`"archive": { "exclude": [".next-bump"] }` (additive to any existing
archive config). This SHALL prevent the `.next-bump` marker from
being included in dist archives, including dev/branch installs.

#### Scenario: shop-ce composer.json carries the exclude

- **WHEN** the change ships
- **THEN** `shop-ce/composer.json` has an `archive` block whose
  `exclude` array contains `.next-bump`

#### Scenario: Dev install does not include .next-bump

- **WHEN** a consumer runs
  `composer require o3-shop/shop-ce:dev-b-1.6` while
  `shop-ce`'s release branch contains a `.next-bump` file
- **THEN** the resulting `vendor/o3-shop/shop-ce` directory does not
  contain `.next-bump`
