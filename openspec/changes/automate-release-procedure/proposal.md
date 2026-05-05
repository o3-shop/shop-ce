## Why

Releasing the o3-shop today means walking a 16-repo dependency chain by hand, editing
`source/Core/ShopVersion.php` per release, and following a wiki page that only
documents the last three (shop-ce → metapackage → o3-shop). Forgetting a
satellite repo (e.g. `shop-demodata-ce`, the themes, or any of the leaf libs) is
trivial — issue #136 was a direct consequence of a stale demodata in v1.6.0-RC1.
We need a maintainer-driven CLI that knows the dependency graph, walks it
top-down, and removes the manual `ShopVersion.php` edit by deriving the version
from the git tag at install/build time.

## What Changes

- **NEW** `bin/release` CLI in `shop-ce` that drives a tier-by-tier release
  across the o3-shop repo network. Single command:
  `bin/release v1.6.0` (final) or `bin/release v1.6.0-RC4` (pre-release).
  The CLI is local-only — no GitHub Actions trigger in this iteration.
- **NEW** `release.manifest.yaml` in the **`o3-shop`** repo (the maintainer's
  single place to configure everything). It enumerates every release-eligible
  repo, its tier, the release branch pattern, and the composer-constraint key
  to update downstream. Manifest is read remotely by `bin/release` so any repo
  can drive the release without checking out o3-shop locally.
- **NEW** Three release tiers in the manifest, walked in dependency order:
  - tier 0 — leaf libs (`smarty`, `shop-composer-plugin`, …) and asset
    packages (`o3-Theme`, `wave-theme`, `shop-demodata-ce`)
  - tier 1 — `shop-ce` (composer-pins all of tier 0)
  - tier 2 — `shop-metapackage-ce` (pins shop-ce + tier 0)
  - tier 3 — `o3-shop` (pins metapackage; pins shop-ce additionally for
    pre-releases, per existing wiki rule)
- **NEW** Per-repo release flow:
  1. Pre-flight gates (clean tree, on release branch, deps resolved to release
     versions, tests green, currency rates fresh)
  2. Composer-constraint bump (downstream tiers only) committed and pushed
     **directly** to the release branch — no PRs for the bumps
  3. Tag created + GitHub release published as **draft** (maintainer clicks
     "Publish" manually, with the auto-generated release notes)
  4. Pre-release flag matches the tag suffix (`-rc`, `-beta`, `-alpha`)
  5. For final releases only, an auto-opened PR `Merge v<x>.<y>.<z> release
     into main` per repo (existing wiki rule, kept identical)
- **NEW** Open-PR detection — the CLI warns (does not abort) when a repo has
  open PRs against the release branch.
- **NEW** Dry-run mode (`--dry-run`) prints the planned tag/commit/release
  for every repo without touching anything.
- **MODIFIED** `source/Core/ShopVersion.php` resolves at runtime in this
  order:
  1. `source/Core/version.generated.php` (created by a composer post-install
     hook from the release artifact)
  2. `vendor/composer/installed.json` lookup of `o3-shop/shop-ce`
  3. `git describe --tags --always` for dev checkouts
  4. Hard-coded `dev` fallback
  The committed file no longer carries the literal version string. The
  `Update ShopVersion to v...` commits stop happening.
- **BREAKING** for downstream tooling that scrapes `ShopVersion.php` for a
  literal — none known internally; admins still see the right version in the
  admin UI because `getVersion()` returns the same string at runtime.
- **OUT OF SCOPE** Modules (paypal, captcha, gdpr-optin, country-vat, amazon-pay,
  usercentrics, tinymce-editor, …), documentation repos, deprecated repos
  (flow-theme, vortex-theme, tests-deprecated-ce), and the `update`
  webservice — they ship on independent cadences and do not block a shop
  release.

## Capabilities

### New Capabilities

- `release-orchestration`: dependency-graph-driven release CLI (`bin/release`).
  Pre-flight gates, tier-by-tier walk, composer-constraint bumps, tag/release
  creation, draft-release publishing, open-PR warnings, dry-run mode.
- `release-manifest`: the YAML schema and resolution rules for the manifest in
  `o3-shop/release.manifest.yaml` — the single source of truth for the
  dependency graph and per-repo release configuration.
- `shop-version-resolution`: the runtime resolution chain replacing the
  hardcoded literal in `ShopVersion::getVersion()` (generated file →
  installed.json → git describe → fallback).

### Modified Capabilities

(none — no existing spec covers release tooling or version resolution)

## Impact

- **shop-ce**
  - new `bin/release` CLI (PHP, Symfony Console — already a dep) and tests
  - `source/Core/ShopVersion.php` rewritten to resolve at runtime
  - `composer.json` post-install hook to materialize `version.generated.php`
- **o3-shop** repo
  - new `release.manifest.yaml`
- **Every release-eligible repo** (per the manifest)
  - composer.json constraint bumps happen directly on the release branch
- **Wiki**
  - https://github.com/o3-shop/o3-shop/wiki/Create-a-Release rewritten to
    "run `bin/release <tag>` and publish the resulting drafts" plus a manual
    fallback for the case where the CLI is unavailable
- **No new runtime dependencies** for the shop itself; the CLI relies only on
  existing Symfony Console, the GitHub CLI (`gh`, already required by the
  current manual flow), and `git`.
- **Test impact** — new unit tests for `bin/release` orchestration and for the
  three-step `ShopVersion` resolution chain. No existing tests should change
  behaviour beyond the version-string source.
