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

### Requirement: Deprecated entries dropped during fold-in

The fold-in SHALL NOT carry `flow-theme`, `vortex-theme`, or any
`tests-deprecated-ce`-style entries from the metapackage's `require`
list into `o3-shop/composer.json`. Users who still want them MAY
install them via `composer require` explicitly.

#### Scenario: flow-theme dropped

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` does not require `o3-shop/flow-theme`

#### Scenario: vortex-theme dropped

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` does not require
  `o3-shop/vortex-theme`

### Requirement: Bundled modules preserved during fold-in

The fold-in SHALL preserve `o3-shop/gdpr-optin-module`, the o3-shop
fork of `paypal-module`, `o3-shop/usercentrics`, and
`o3-shop/tinymce-editor` from the metapackage's `require` list into
`o3-shop/composer.json` `require`. They become candidates for the
release-graph walk like any other tier-0 dep.

#### Scenario: gdpr-optin-module preserved

- **WHEN** the fold-in is applied
- **THEN** `o3-shop/composer.json` requires
  `o3-shop/gdpr-optin-module` with the version that was pinned in
  the metapackage

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

#### Scenario: Hybrid install still rejected

- **WHEN** a project requires both `o3-shop/o3-shop` and
  `oxid-esales/oxideshop-metapackage-ce`
- **THEN** Composer's resolver rejects the install (the `replace`
  marker still does its job)

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
