## ADDED Requirements

### Requirement: Build from_pin[] from --from snapshot

The CLI SHALL read `o3-shop/composer.json` at the `--from` tag and
build a map `from_pin[repo]` of every `o3-shop/*` package to its
exact pinned version. This map SHALL be the per-repo anchor for the
"did anything change?" check and the starting point for cross-repo
release notes.

#### Scenario: Post-fold-in --from snapshot

- **WHEN** `o3-shop/composer.json` at `--from` directly pins shop-ce,
  themes, demodata, bundled modules, and dev-tooling deps
- **THEN** `from_pin[]` contains an entry for each pinned `o3-shop/*`
  package mapping to its exact version string

### Requirement: Pre-fold-in `--from` triggers metapackage indirection

If `o3-shop/composer.json` at the `--from` tag still requires
`o3-shop/shop-metapackage-ce` (the snapshot predates the metapackage
fold-in), the CLI SHALL recurse one level into the metapackage's
`composer.json` at the version pinned by `--from` and harvest the
per-tier-0 pins from there. The resulting per-tier-0 pins SHALL be
merged into `from_pin[]` and used identically to a post-fold-in
`from_pin[]` for every downstream step. The CLI SHALL log a single
informational line stating that pre-fold-in indirection was applied,
naming the metapackage tag consulted.

#### Scenario: Pre-fold-in --from at the v1.6.0 transition

- **WHEN** the maintainer runs
  `bin/release --from v1.6.0 --to v1.6.1-RC1`
  and `o3-shop@v1.6.0/composer.json` requires
  `o3-shop/shop-metapackage-ce: vX.Y.Z`
- **THEN** the CLI fetches `shop-metapackage-ce@vX.Y.Z/composer.json`,
  builds `from_pin[shop-ce]`, `from_pin[wave-theme]`,
  `from_pin[shop-demodata-ce]`, etc. from its `require` entries, and
  proceeds to Step 2 with the merged map

#### Scenario: Pre-fold-in indirection is logged

- **WHEN** Step 1 applies the metapackage indirection
- **THEN** the CLI emits a single informational log line identifying
  the metapackage tag consulted (e.g. "Step 1: pre-fold-in --from
  detected; harvested tier-0 pins from shop-metapackage-ce@vX.Y.Z")

### Requirement: Dependency walk includes require and require-dev

The CLI SHALL walk both `require` and `require-dev` recursively
starting from `o3-shop/composer.json` on the release branch. For
every `o3-shop/*` package found, the CLI SHALL recursively read that
package's composer.json and walk its `require` and `require-dev` for
further `o3-shop/*` deps. Non-`o3-shop/*` packages SHALL be ignored.

#### Scenario: Dev-tooling dep is reached via require-dev

- **WHEN** `o3-shop/composer.json` declares
  `o3-shop/testing-library` only in `require-dev`
- **THEN** `testing-library` appears as a candidate in the release
  graph

#### Scenario: Transitive dep through shop-ce

- **WHEN** `shop-ce/composer.json` requires `o3-shop/smarty`
- **THEN** `smarty` appears as a candidate in the release graph even
  though `o3-shop/composer.json` does not require it directly

### Requirement: Topological tier ordering

The CLI SHALL topologically sort the dep graph and assign tiers based
on depth (leaves at tier 0, `o3-shop` at the highest tier). Tier
numbers are not declared anywhere; they emerge from the sort.

#### Scenario: Leaf is tier 0

- **WHEN** `smarty` has no `o3-shop/*` deps
- **THEN** `smarty` is in tier 0

#### Scenario: o3-shop is the highest tier

- **WHEN** `o3-shop` requires `shop-ce` (tier 1) and tier-0 packages
- **THEN** `o3-shop` is at the highest tier (tier 2)

### Requirement: Cycle detection is a fatal error

If the dep walk discovers a cycle (e.g. A requires B requires A), the
CLI SHALL abort with a diagnostic listing the cycle.

#### Scenario: Two-package cycle

- **WHEN** `repo-a` requires `repo-b` and `repo-b` requires `repo-a`
- **THEN** the CLI aborts and prints both repos as participants in
  the cycle

### Requirement: Three-case version resolution per candidate

For each candidate in the walk, the CLI SHALL pick a version using
`from_pin[repo]` as anchor by applying these three cases in order,
taking the first that hits:

1. **Unchanged since from**: no commits or new tags on the
   candidate's release branch newer than `from_pin[repo]` →
   reuse `from_pin[repo]`. No new release on this repo.
2. **Changed with usable tag**: `latest_tag > from_pin[repo]` and the
   latest tag's stability is compatible with `--to`'s stability →
   use `latest_tag`. No new release on this repo.
3. **Changed without usable tag**: commits exist beyond the latest
   tag, or the latest tag is a pre-release while `--to` is a final
   → CLI cuts a new tag (per the tag-cutting policy) and uses it.

#### Scenario: Repo unchanged since --from

- **WHEN** a candidate's release branch has no commits or tags newer
  than `from_pin[repo]`
- **THEN** the CLI reuses `from_pin[repo]` and does not cut a new tag

#### Scenario: Maintainer pre-cut a newer tag

- **WHEN** a candidate's latest tag is `v1.0.2`, `from_pin[repo]` is
  `v1.0.1`, and `--to` is a final shop release
- **THEN** the CLI uses `v1.0.2` and does not cut a new tag

#### Scenario: Commits exist with no new tag

- **WHEN** a candidate has commits past its latest tag and no
  maintainer-cut newer tag
- **THEN** the CLI cuts a new tag per the tag-cutting policy

### Requirement: Stability check on dep tag selection

When `--to` is a final shop release (no `-rc`/`-alpha`/`-beta`
suffix), the CLI SHALL reject pre-release candidate tags. When `--to`
is a pre-release, the CLI SHALL accept either.

#### Scenario: Final shop release with RC dep tag available

- **WHEN** `--to` is `v1.7.0` (final), candidate's latest tag is
  `v1.7.0-RC3`, and `from_pin[repo]` is `v1.6.0`
- **THEN** the CLI does not select `v1.7.0-RC3`; it falls through to
  case 3 and cuts a new final tag

#### Scenario: RC shop release with final dep tag available

- **WHEN** `--to` is `v1.7.0-RC4` (pre-release), candidate's latest
  tag is `v1.7.0` (final), and `from_pin[repo]` is `v1.6.0`
- **THEN** the CLI selects `v1.7.0`

### Requirement: Tag-cutting policy — shop-ce uses --to verbatim

When the CLI cuts a new tag on `shop-ce` (case 3), the new tag SHALL
be exactly the `--to` value.

#### Scenario: Cutting shop-ce v1.6.2

- **WHEN** the CLI cuts a new tag on `shop-ce` during a release with
  `--to v1.6.2`
- **THEN** the new shop-ce tag is `v1.6.2`

### Requirement: Tag-cutting policy — every other repo bumps own line

When the CLI cuts a new tag on any repo other than `shop-ce`, the new
tag SHALL be a bump from the repo's current latest tag, on the repo's
existing version line. The bump level is resolved with this
precedence (first match wins):
1. `--bump <repo>=<level>` flag at invocation.
2. `.next-bump` file at the repo's release-branch root.
3. Default: patch.

The bump level vocabulary is `patch`, `minor`, `major`, or an exact
version of the form `v<major>.<minor>.<patch>` (with optional
pre-release suffix).

#### Scenario: Default patch bump

- **WHEN** the CLI cuts a new tag on `testing-library` whose latest
  tag is `v1.2.5`, no flag is passed for it, and no `.next-bump` file
  exists
- **THEN** the new tag is `v1.2.6`

#### Scenario: .next-bump file requests minor

- **WHEN** the CLI cuts a new tag on `gdpr-optin-module` whose latest
  tag is `v1.0.1`, no flag is passed for it, and `.next-bump` at the
  release-branch root contains `minor`
- **THEN** the new tag is `v1.1.0`

#### Scenario: Flag overrides .next-bump file

- **WHEN** the CLI cuts a new tag on `shop-facts` with
  `--bump shop-facts=v2.0.0` and `.next-bump` contains `minor`
- **THEN** the new tag is `v2.0.0` and the `.next-bump` file is
  not deleted

### Requirement: .next-bump file consumed on use

When the CLI uses a `.next-bump` file's value to compute a new tag,
it SHALL delete the file in the same commit it cuts the tag from, so
the released tag's tree does not contain the file.

#### Scenario: File deletion in tag commit

- **WHEN** the CLI uses `.next-bump: minor` to cut a new tag
- **THEN** the commit the tag points at does not contain
  `.next-bump`, and the file is deleted from the release branch

#### Scenario: Flag run leaves committed file untouched

- **WHEN** the CLI cuts a new tag using a `--bump` flag and the repo
  has a committed `.next-bump`
- **THEN** the `.next-bump` file remains on the release branch
  unchanged after the release

### Requirement: Constraint update only when the existing constraint does not satisfy the chosen version

For every spot in the dep tree where a candidate is pinned, the CLI
SHALL update the constraint **only when** the existing constraint
does not already satisfy the chosen version. Exact pins SHALL be
replaced with the chosen tag verbatim. Flexible constraints (caret,
tilde, range) SHALL be widened only when the chosen version does not
satisfy them.

#### Scenario: Caret already satisfies the chosen version

- **WHEN** a `require-dev` entry is `o3-shop/testing-library: ^1.2.0`
  and the chosen version is `v1.2.6`
- **THEN** the constraint is left unchanged

#### Scenario: Exact pin needs replacement

- **WHEN** a `require` entry is
  `o3-shop/shop-ce: v1.6.1` and the chosen version is `v1.6.2`
- **THEN** the constraint is replaced with `v1.6.2`

#### Scenario: Caret needs widening

- **WHEN** a `require-dev` entry is `o3-shop/testing-library: ^1.2.0`
  and the chosen version is `v2.0.0`
- **THEN** the constraint is widened to a form that includes
  `v2.0.0`
