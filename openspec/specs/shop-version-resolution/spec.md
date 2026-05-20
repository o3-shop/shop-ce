# shop-version-resolution Specification

## Purpose

Defines how `OxidEsales\EshopCommunity\Core\ShopVersion::getVersion()`
resolves the shop version at runtime instead of carrying a literal
string in committed source. Eliminates the per-release
`Update ShopVersion to v...` commits and lets the version follow the
installed shop-ce package metadata.

## Requirements

### Requirement: ShopVersion::getVersion() resolves at runtime

`OxidEsales\EshopCommunity\Core\ShopVersion::getVersion()` SHALL
return the shop version as resolved at runtime by walking a
3-step resolution chain in order. The committed source code of
`ShopVersion.php` SHALL NOT contain a literal version string.

#### Scenario: ShopVersion.php carries no version literal

- **WHEN** the codebase is inspected at any commit on a release
  branch
- **THEN** `source/Core/ShopVersion.php` does not contain a literal
  matching the pattern `v\d+\.\d+\.\d+(-[A-Za-z0-9]+)?`

### Requirement: Step 1 — version.generated.php

If `source/Core/version.generated.php` exists and returns a non-empty string, `getVersion()` SHALL return that string. The file SHALL be written by a composer post-install hook from the release artifact's metadata.

#### Scenario: version.generated.php present

- **WHEN** `source/Core/version.generated.php` exists and returns
  the string `"v1.6.1"`
- **THEN** `ShopVersion::getVersion()` returns `"v1.6.1"`

#### Scenario: Composer post-install creates the file

- **WHEN** a release artifact is installed via `composer install`
- **THEN** the post-install hook writes
  `source/Core/version.generated.php` containing the installed
  shop-ce version

### Requirement: Step 2 — Composer runtime API fallback

If Step 1 does not produce a version, `getVersion()` SHALL query
Composer's runtime API
(`Composer\InstalledVersions::getPrettyVersion('o3-shop/shop-ce')`)
and return its result if non-empty. The API reads from
`vendor/composer/installed.json`/`installed.php` and locates the
project root via Composer's autoloader, so the lookup works whether
shop-ce is the project root or a vendor dep of an o3-shop project.

#### Scenario: shop-ce installed as a vendor dep

- **WHEN** Composer's `installed.json` records
  `o3-shop/shop-ce` at `v1.6.1` and Step 1 does not apply
- **THEN** `Composer\InstalledVersions::getPrettyVersion('o3-shop/shop-ce')`
  returns `"v1.6.1"` and `ShopVersion::getVersion()` returns `"v1.6.1"`

#### Scenario: shop-ce not registered with Composer

- **WHEN** the `Composer\InstalledVersions` class is unavailable or
  raises `OutOfBoundsException` for `o3-shop/shop-ce` and Step 1
  does not apply
- **THEN** `ShopVersion::getVersion()` falls through to Step 3

### Requirement: Step 3 — dev fallback

If Steps 1 and 2 both fail to produce a non-empty version, `getVersion()` SHALL return the literal string `"dev"`. The CLI SHALL NOT shell out to `git describe` or any other external process to derive a version.

#### Scenario: Fresh git clone without composer install

- **WHEN** `version.generated.php` is absent and
  `Composer\InstalledVersions` cannot resolve `o3-shop/shop-ce`
  (e.g. a fresh git clone where `composer install` has not been
  run)
- **THEN** `ShopVersion::getVersion()` returns `"dev"`

#### Scenario: No process forks for version resolution

- **WHEN** any code path in `ShopVersion::getVersion()` is exercised
- **THEN** the implementation does not invoke `git`, `shell_exec`,
  `proc_open`, or any other process-spawning function

### Requirement: No per-release commits to ShopVersion.php

The `Update ShopVersion to v...` style commits SHALL stop being
created. After this change ships, the only commits touching
`ShopVersion.php` SHALL be ones that change its resolution logic,
not its returned literal.

#### Scenario: Release tag does not modify ShopVersion.php

- **WHEN** `bin/release` cuts a new shop-ce tag
- **THEN** the commit the tag points at does not modify
  `source/Core/ShopVersion.php`
