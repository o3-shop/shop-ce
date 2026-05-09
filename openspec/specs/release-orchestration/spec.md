# release-orchestration Specification

## Purpose

Defines the maintainer-facing orchestration of the `bin/release` CLI:
its invocation contract, pre-flight gates, tier-by-tier execution
order, dry-run mode, draft-release behaviour, open-PR classification,
constraint-bump commit policy, merge-back PR automation, and the
local-only execution boundary. This is the outer shell that calls
into release-graph-derivation and release-notes-aggregation.

## Requirements

### Requirement: CLI invocation with mandatory --from and --to

The `bin/release` CLI SHALL require both `--from` and `--to` flags on
every invocation. Both values MUST be valid git tag names. The CLI SHALL
exit non-zero with a usage message when either flag is omitted or empty.

#### Scenario: Both flags provided

- **WHEN** the maintainer runs `bin/release --from v1.6.1 --to v1.6.2`
- **THEN** the CLI proceeds to Step 1 (snapshot resolution)

#### Scenario: --from omitted

- **WHEN** the maintainer runs `bin/release --to v1.6.2`
- **THEN** the CLI exits with non-zero status and prints a usage message
  identifying `--from` as a required flag

#### Scenario: --to omitted

- **WHEN** the maintainer runs `bin/release --from v1.6.1`
- **THEN** the CLI exits with non-zero status and prints a usage message
  identifying `--to` as a required flag

### Requirement: Pre-flight gates per release-eligible repo

Before performing any state-changing action (commit, push, tag, release creation), the CLI SHALL verify all pre-flight gates for every release-eligible repo. If any gate fails for any repo, the CLI SHALL abort with a clear diagnostic listing the failing repo(s) and gate(s). Pre-flight gates include: clean working tree, on the release branch, deps resolved to release versions, tests green, and no unmerged merge-back PR from a previous release.

#### Scenario: All gates pass

- **WHEN** every release-eligible repo has a clean tree, is on its
  release branch, has resolvable deps, has green tests, and has no
  unmerged merge-back PR
- **THEN** the CLI proceeds to the per-repo release flow

#### Scenario: Repo has uncommitted changes

- **WHEN** any release-eligible repo has uncommitted local changes
- **THEN** the CLI aborts and lists the affected repo(s) before
  performing any state-changing action

#### Scenario: Tests fail in a repo

- **WHEN** any release-eligible repo's test suite fails during pre-flight
- **THEN** the CLI aborts and prints the failing repo's test output
  location

#### Scenario: Unmerged merge-back PR from previous release

- **WHEN** any release-eligible repo has an open PR from its release
  branch into `main` matching the title pattern `Merge v<x>.<y>.<z>
  release into main`
- **THEN** the CLI aborts with a message listing the open merge-back PR
  URLs and the repos affected

### Requirement: Tier-by-tier walk in dependency order

The CLI SHALL process release-eligible repos in topological order:
tier 0 (leaves) first, then tier 1, then tier 2 (`o3-shop`). Within a
tier, order is unspecified. The CLI SHALL not begin tier N+1 work
until every tier-N repo has completed its release flow.

#### Scenario: Linear chain

- **WHEN** the dep graph is `o3-shop → shop-ce → smarty`
- **THEN** `smarty` completes (or skips) before `shop-ce` is processed,
  and `shop-ce` completes before `o3-shop` is processed

#### Scenario: Diamond dependency

- **WHEN** the dep graph has `shop-ce` and `testing-library` both
  requiring `shop-facts`
- **THEN** `shop-facts` completes before either `shop-ce` or
  `testing-library` starts

### Requirement: Dry-run mode

The CLI SHALL accept a `--dry-run` flag that prints the full release
plan (per-repo chosen versions, planned tags, planned constraint
edits, planned commits, planned releases) without performing any
state-changing action. Dry-run SHALL exercise the same algorithm as a
real run; the only difference is the absence of side effects.

#### Scenario: Dry-run with full release planned

- **WHEN** the maintainer runs `bin/release --from v1.6.1 --to v1.6.2
  --dry-run`
- **THEN** the CLI prints the planned tag, planned commit, and
  planned GitHub release for every release-eligible repo, and exits
  zero without modifying any repo, branch, tag, or release

### Requirement: Draft GitHub releases

When the CLI creates a GitHub release for a tag, it SHALL create the
release in **draft** state. The CLI SHALL not auto-publish releases.
The maintainer manually clicks "Publish" after reviewing.

#### Scenario: New tag triggers release creation

- **WHEN** the CLI cuts a new tag on a release-eligible repo
- **THEN** the corresponding GitHub release is created with
  `draft: true`

### Requirement: Open-PR detection with two outcomes

The CLI SHALL classify open PRs in each release-eligible repo into two
groups:
- **Incoming PRs** (open PRs targeting the release branch) → log a
  warning, proceed.
- **Outgoing merge-back PRs** (open PRs from the release branch into
  `main` matching `Merge v<x>.<y>.<z> release into main`) → abort
  the release.

#### Scenario: Incoming feature PR is open

- **WHEN** a release-eligible repo has an open PR targeting its
  release branch
- **THEN** the CLI logs a warning identifying the PR URL and proceeds
  with the release

#### Scenario: Outgoing merge-back PR is unmerged

- **WHEN** a release-eligible repo has an open PR from the release
  branch into `main` whose title matches `Merge v<x>.<y>.<z> release
  into main`
- **THEN** the CLI aborts before any state-changing action and lists
  the unmerged merge-back PR URLs

### Requirement: Constraint bumps pushed directly to release branch

When a dependent repo's `require` or `require-dev` constraint changes, the CLI SHALL commit and push the change **directly** to the release branch — no PR. The commit message SHALL identify the bumped package and version.

#### Scenario: Constraint bump for shop-ce

- **WHEN** the CLI updates `o3-shop/composer.json` to pin `shop-ce` to
  `v1.6.1`
- **THEN** the change is committed with a message identifying
  `o3-shop/shop-ce` as the bumped package, and pushed to the release
  branch without opening a PR

### Requirement: Auto-opened merge-back PR for final releases

For final shop releases (target tag without `-rc`/`-alpha`/`-beta` suffix), the CLI SHALL auto-open one PR per release-eligible repo titled `Merge v<x>.<y>.<z> release into main`, with `head` = the release branch and `base` = `main`. For pre-release shop targets, no merge-back PR is opened.

#### Scenario: Final release of v1.6.2

- **WHEN** the CLI completes a release with `--to v1.6.2`
- **THEN** every release-eligible repo has a new open PR titled
  `Merge v1.6.2 release into main` from the release branch into `main`

#### Scenario: RC release of v1.7.0-RC1

- **WHEN** the CLI completes a release with `--to v1.7.0-RC1`
- **THEN** no merge-back PRs are opened in any release-eligible repo

### Requirement: Local-only execution

The CLI SHALL run only on a maintainer's local machine. The CLI SHALL
NOT depend on or trigger GitHub Actions, scheduled jobs, or any
remote orchestration. All side effects SHALL be initiated by the
maintainer's invocation.

#### Scenario: No GHA workflow created

- **WHEN** the change ships
- **THEN** no `.github/workflows/release*.yml` is added to any
  release-eligible repo for the purpose of running `bin/release`

### Requirement: Auto-detect nested working trees inside shop-ce

The CLI's `RepoPathDiscovery` SHALL scan the running shop-ce working tree for nested git working trees and prefer them over the conventional sibling layout when resolving a package's local clone path. The scan SHALL read each found tree's `.git/config` `[remote "origin"]` URL, reverse-map the GitHub slug to a composer package name (honoring the `PackageRepoSlug::RENAMES` case-rename map), and use that path for any release-eligible package in the discovered map. Origins outside the `o3-shop` GitHub owner SHALL be ignored. The scan SHALL skip noise directories (`vendor/`, `node_modules/`, `cache/`, `tmp/`, `log/`, `logs/`, `out/`, `coverage/`, `tools/`, `docs/`, hidden directories) and SHALL bound recursion at depth 4 from shop-ce's root. A directory that exists at a known nested location but does NOT contain `.git/` (typical for composer-plugin install artifacts) SHALL fall through to the sibling-or-auto-clone path; it SHALL NOT abort discovery.

#### Scenario: ./docker.sh start creates nested theme clones

- **WHEN** the maintainer has run `./docker.sh start`, which clones
  `o3-Theme` into `<shop-ce>/source/Application/views/o3-theme` and
  `wave-theme` into `<shop-ce>/source/Application/views/wave`
- **AND** the maintainer runs `bin/release --from v1.6.1 --to v1.6.2`
- **THEN** discovery uses the nested clones for both themes,
  emitting `using nested clone (set up by ./docker.sh) at <path>`
- **AND** discovery does NOT auto-clone duplicate sibling copies

#### Scenario: Demodata satellite at shop-ce root

- **WHEN** `<shop-ce>/shop-demodata-ce/.git/config` has
  `url = https://github.com/o3-shop/shop-demodata-ce.git`
- **THEN** discovery resolves `o3-shop/shop-demodata-ce` to
  `<shop-ce>/shop-demodata-ce`

#### Scenario: Case-renamed package origin

- **WHEN** the nested clone's `.git/config` has
  `url = https://github.com/o3-shop/o3-Theme.git` (mixed-case
  GitHub repo name)
- **THEN** the scanner reverse-maps the slug to the composer
  package name `o3-shop/o3-theme` (lowercase) and records the
  nested path under that name

#### Scenario: Composer-plugin install artifact (no .git/)

- **WHEN** `<shop-ce>/source/Application/views/o3-theme` exists
  but contains no `.git/` directory (typical state when composer
  plugin has installed theme files but the maintainer has not run
  `./docker.sh start`)
- **THEN** discovery does NOT treat this path as a nested clone
- **AND** discovery falls through to sibling-layout resolution

#### Scenario: Non-o3-shop origin ignored

- **WHEN** a nested git tree's origin is
  `git@github.com:other-org/other-repo.git`
- **THEN** the scanner discards it (not under the `o3-shop` GitHub
  owner) and the path is NOT added to the discovered map

#### Scenario: vendor/ tree skipped

- **WHEN** a nested git tree exists at
  `<shop-ce>/vendor/o3-shop/o3-theme/.git`
- **THEN** the scanner does NOT descend into `vendor/` and the
  path is NOT added to the discovered map; resolution falls
  through to sibling layout / auto-clone
