## ADDED Requirements

### Requirement: shop-metapackage-ce is the single compilation

`o3-shop/shop-metapackage-ce` SHALL own the full pinned dependency list
for the shop compilation — the exact-version set of framework packages
(Symfony, Doctrine, Composer, Monolog, …) and o3-shop component packages
(shop-ce, themes, demodata, bundled modules) — that constitutes a tested
release.

#### Scenario: Metapackage pins the compilation

- **WHEN** a release is cut
- **THEN** `shop-metapackage-ce/composer.json` `require` contains the
  exact-pinned framework deps and the o3-shop component packages for that
  release

### Requirement: o3-shop is a thin project requiring the metapackage

`o3-shop/o3-shop` SHALL be a `type: project` whose `require` lists only
`o3-shop/shop-metapackage-ce` (plus dev-tooling in `require-dev`). It SHALL
NOT inline the compilation dependency list and SHALL NOT declare a
`replace` clause.

#### Scenario: create-project installs cleanly

- **WHEN** a user runs `composer create-project o3-shop/o3-shop`
- **THEN** the install resolves: the metapackage is the only package that
  `replace`s `oxid-esales/oxideshop-metapackage-ce`, so there is no
  "cannot coexist" conflict

### Requirement: Metapackage owns the OXID-lineage replace clause

`shop-metapackage-ce/composer.json` SHALL declare
`replace: oxid-esales/oxideshop-metapackage-ce` and SHALL be the only
package in the install graph that does so.

#### Scenario: No double-replace

- **WHEN** `o3-shop/o3-shop` is installed (which pulls the metapackage)
- **THEN** exactly one package replaces
  `oxid-esales/oxideshop-metapackage-ce` (the metapackage), and Composer
  does not reject the install

### Requirement: Metapackage stays published for upgrade compatibility

`o3-shop/shop-metapackage-ce` SHALL remain a published, version-advancing
package (NOT archived) for as long as in-place `composer update` from
installs that require it by name is supported. Deployed installs created
from `o3-shop/o3-shop` ≤ v1.6.0 have a root `composer.json` that requires
the metapackage; archiving it would freeze those installs.

#### Scenario: Existing install updates forward

- **WHEN** a project root requires `o3-shop/shop-metapackage-ce` and the
  constraint is raised to a newer release line
- **THEN** `composer update` resolves to the newer metapackage tag and
  pulls the corresponding compilation

### Requirement: Compilation excludes deprecated packages, keeps bundled modules

The metapackage's `require` list SHALL NOT include `flow-theme`,
`vortex-theme`, the o3-shop fork of `paypal-module`, or
`tests-deprecated-ce`-style entries. It SHALL include the bundled modules
`o3-shop/gdpr-optin-module`, `o3-shop/usercentrics`, and
`o3-shop/tinymce-editor`.

#### Scenario: Deprecated theme absent

- **WHEN** a release is cut
- **THEN** `shop-metapackage-ce/composer.json` does not require
  `o3-shop/flow-theme`

#### Scenario: Bundled module present

- **WHEN** a release is cut
- **THEN** `shop-metapackage-ce/composer.json` requires
  `o3-shop/gdpr-optin-module`
