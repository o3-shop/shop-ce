## Why

Releasing the o3-shop today means walking a 16-repo dependency chain by hand,
editing `source/Core/ShopVersion.php` per release, and following a wiki page
that only documents the last three (shop-ce → metapackage → o3-shop).
Forgetting a satellite repo (e.g. `shop-demodata-ce`, the themes, or any of the
leaf libs) is trivial — issue #136 was a direct consequence of a stale
demodata in v1.6.0-RC1. We need a maintainer-driven CLI that knows the
dependency graph, walks it top-down, and removes the manual `ShopVersion.php`
edit by deriving the version from the git tag at install/build time.

Less ceremony also means fewer things that can go wrong. A feasibility audit
done while scoping this change (Packagist + GitHub code search + walk over
every active o3-shop repo's composer.json) shows that
`o3-shop/shop-metapackage-ce` has **zero external consumers and zero internal
tooling consumers** — it is required only by `o3-shop/o3-shop` itself. Its
release-time job (pinning the exact compatibility set) duplicates what the
project root composer.json already does for its single consumer, so it is a
release-graph tier with no current load-bearing function. Folding it into
`o3-shop/composer.json` while we rebuild the release process drops one tier,
one repo, one tag-and-publish step, and one source of drift — at no cost,
because nothing depends on its package shape today.

## Prerequisite — Metapackage fold-in

**`o3-shop/shop-metapackage-ce` must be folded into `o3-shop` before
`bin/release` can drive any release.** The CLI's Step 1 reads
`o3-shop/composer.json` and expects every `o3-shop/*` package pinned
directly there (so it can build `from_pin[]`). Pre-fold-in, that file
only requires `o3-shop/shop-metapackage-ce`; the actual pins live one
level deeper.

The fold-in is mechanical — composer-metadata only, no code:

1. Move the metapackage's `require` list into `o3-shop/composer.json`,
   with two deletions on the way:
   - **Drop deprecated entries** (`flow-theme`, `vortex-theme`, and any
     `tests-deprecated-ce`-style references). They no longer ship by
     default; users who still want them can `composer require` them
     explicitly.
   - **Keep bundled modules** (`gdpr-optin-module`, the o3-shop
     `paypal-module`, `usercentrics`, `tinymce-editor`) — these ship with
     the shop and become candidates for the release-graph walk.
2. Move the `replace: oxid-esales/oxideshop-metapackage-ce` clause to
   `o3-shop/composer.json` (preserving the OXID-lineage marker that blocks
   hybrid OXID + o3-shop installs).
3. Archive `shop-metapackage-ce` with a final tag pinning current state
   and a README pointer to `o3-shop/o3-shop`. No further releases.

Feasibility was confirmed before scoping this in: 0 external Packagist
dependents, 0 internal tooling consumers, and `replace` clauses are
Composer-supported on `type: project` roots (precedent inside o3-shop:
`testing-library` carries `replace: oxid-esales/testing-library`).

Two acceptable sequencings:

1. **Inline** (default): treat the fold-in as the first task in this
   change's task list. The CLI is built against the post-fold-in shape
   from day one. The first CLI-driven release is the one that ships the
   fold-in.
2. **Separate change**: lift the fold-in into its own openspec change,
   merge and apply it manually first (e.g. as part of an RC cut), then
   start work on the CLI against the now-stable folded-in shape. Use this
   path if you'd rather keep the two concerns reviewable independently or
   if the fold-in needs to ship on a different cadence.

Either way: every `--from` reference passed to `bin/release` must point at
a snapshot where the fold-in already happened. Backwards-looking notes
(e.g. `--from v1.5.4 --to v1.6.0`) where the from-snapshot predates the
fold-in are out of scope; the first machine-generated release notes start
from the first post-fold-in tag.

## What Changes

- **NEW** `bin/release` CLI in `shop-ce` that drives a tier-by-tier release
  across the o3-shop repo network. Invocation:
  ```
  bin/release --from v1.5.4 --to v1.6.0 [--bump testing-library=minor ...] [--dry-run]
  ```
  - `--to` — **required**. The shop version being cut (final or
    pre-release: `v1.6.0`, `v1.6.0-RC4`, etc.).
  - `--from` — **required**. The previous shop release this one ships
    *over*. Anchors the "what changed?" check per repo and the cross-repo
    release-notes generation. The CLI does not auto-detect — every release
    explicitly states what it ships over, no guesswork.
  - Missing either flag → CLI exits non-zero with a usage message.
  - The CLI is local-only — no GitHub Actions trigger in this iteration.
- **NEW** Zero-config derivation — there is no release manifest. The release
  set, the dependency graph, the tier ordering, and the per-repo version
  decisions are all derived at runtime:
  - **Step 1 — Snapshot `from`.** Resolve `--from` to a tagged commit on
    `o3-shop` and read its `composer.json`. For each `o3-shop/*` entry,
    record `from_pin[repo]` — the exact version that shipped in `--from`.
    This map is the per-repo anchor for "did anything change?" and the
    starting point for release notes. **Assumes the fold-in is already
    in place at the `--from` tag** (see "Prerequisite" above); the CLI
    aborts with a clear error if `o3-shop/composer.json` at `--from` still
    requires `o3-shop/shop-metapackage-ce`.
  - **Step 2 — Walk the dep tree** from `o3-shop/composer.json` on the
    target release branch, recursively through `require` and `require-dev`.
    Collect every `o3-shop/*` package and remember each spot where it's
    pinned (so Step 4 knows where to write). The walk includes runtime
    deps (shop-ce, themes, demodata, bundled modules like
    `gdpr-optin-module` / `paypal-module` / `usercentrics` /
    `tinymce-editor`) and dev-tooling deps (`testing-library`,
    `shop-ide-helper`, `codeception-modules`, …) — anything we ship.
    Non-bundled modules (captcha, amazon-pay, country-vat, …) are
    opt-in installs via composer scripts and never appear in this tree.
  - **Step 3 — Pick a version per candidate.** For each repo in the walk,
    using `from_pin[repo]` as anchor:
    1. **Unchanged since `from`** — no commits or new tags on the
       candidate's release branch newer than `from_pin[repo]` →
       reuse `from_pin[repo]`. Nothing to test, nothing to publish on this
       repo.
    2. **Changed, and a usable tag already exists** —
       `latest_tag > from_pin[repo]` and the latest tag's stability is
       compatible with the shop target (a final shop release requires final
       dep tags; an RC shop release accepts either) → use `latest_tag`.
       This covers both target-already-pre-cut and maintainer-cut
       intermediates with one rule.
    3. **Changed, but no usable tag yet** — commits exist beyond the latest
       tag, or the latest tag is a pre-release while we're cutting a final
       → CLI cuts a new tag (per Step 4) and uses it.
  - **Step 4 — What tag does the CLI cut?** Hybrid scheme (Q1 = c):
    - **shop-ce** uses `--to` verbatim. The shop's release tag *is* the
      shop's version.
    - **Every other repo** stays on its own version line. Default: patch
      bump on its current latest tag (e.g. `testing-library v1.2.5` →
      `v1.2.6`, `gdpr-optin-module v1.0.1` → `v1.0.2`). Override per repo
      with `--bump <repo>=<minor|major|exact-version>` at invocation. Patch
      is the safe default — never accidentally signals an API break.
  - **Step 5 — Update dependent constraints** at every spot recorded in
    Step 2, but only when the existing constraint doesn't already satisfy
    the chosen version. Caret constraints in `require-dev` typically still
    satisfy minor-version bumps and need no touching. Exact pins get
    replaced with the chosen tag verbatim; flexible constraints get widened
    only when needed.
  - **Step 6 — Aggregate release notes.** For every repo where the chosen
    version differs from `from_pin[repo]`, the CLI generates a per-repo
    section using the `from_pin..chosen` commit range (titles, GitHub PR
    refs, contributor list). The aggregated markdown is attached to the
    o3-shop draft GitHub release as the cross-repo changelog —
    "Releasing v1.6.0 over v1.5.4" with one section per changed repo, and
    a list of repos that were unchanged.
  - Tiers fall out of the topological sort of the resulting DAG. Cycles are
    a fatal error.
- **NEW** Three release tiers, derived from the DAG:
  - tier 0 — leaf libs / asset packages / bundled modules / dev-tooling
    leaves: `smarty`, `shop-composer-plugin`, `shop-facts`,
    `shop-unified-namespace-generator`, `o3-Theme`, `wave-theme`,
    `shop-demodata-ce`, `gdpr-optin-module`, `paypal-module` (the o3-shop
    fork), `usercentrics`, `tinymce-editor`, `shop-ide-helper`,
    `developer-tools`, `codeception-modules`, `codeception-page-objects`,
    `MinkSeleniumDriver`
  - tier 1 — `shop-ce` and `testing-library` (each pins various tier-0 deps)
  - tier 2 — `o3-shop` (pins shop-ce + tier 0 directly via `require`, plus
    testing-library + ide-helper via `require-dev`; the former metapackage
    tier is folded in here — see the Prerequisite section)
  Tiers are not declared anywhere; they emerge from the topo sort. Adding a
  new release-eligible repo = add it to a `require` or `require-dev` list.
  Nothing else.
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
- **BREAKING (theoretical)** for any third party that requires
  `o3-shop/shop-metapackage-ce` directly — none found at proposal time on
  Packagist (1 dependent = `o3-shop/o3-shop` itself) or via authenticated
  GitHub code search (2 hits, both inside the o3-shop org). Such consumers,
  if any, must switch to requiring `o3-shop/o3-shop` or `o3-shop/shop-ce`
  directly. Documented in the archived repo's README.
- **OUT OF SCOPE** Non-bundled modules (`captcha-module`, `amazon-pay`,
  `country-vat`, …), documentation repos, deprecated repos (`flow-theme`,
  `vortex-theme`, `tests-deprecated-ce`), and the `update` webservice —
  they don't appear in `o3-shop/composer.json`'s require list (or are being
  dropped from it during the fold-in), so the dep walk never sees them and
  `bin/release` doesn't touch them. Bundled modules (`gdpr-optin-module`,
  `paypal-module`, `usercentrics`, `tinymce-editor`) **are** in scope and
  are processed by the same algorithm as every other tier-0 dep.
- **OUT OF SCOPE** Further consolidation (e.g. folding `shop-ce` into
  `o3-shop`, merging tier-0 leaf libs). The metapackage fold-in (see
  Prerequisite) is the only consolidation included here because (a) the
  feasibility audit confirmed it is unblocked and (b) doing it before the
  CLI ships prevents the 4-tier graph from calcifying into the algorithm.
  Any further consolidation would change the public API of `shop-ce` and
  is its own decision.

## Capabilities

### New Capabilities

- `release-orchestration`: dependency-graph-driven release CLI (`bin/release`).
  Pre-flight gates, tier-by-tier walk (3 tiers post-fold-in), composer-constraint
  bumps, tag/release creation, draft-release publishing, open-PR warnings,
  dry-run mode. **No configuration files** — all behavior is convention- or
  derivation-driven.
- `release-graph-derivation`: the algorithm that builds the release DAG by
  recursively walking **`require` and `require-dev`** starting from
  `o3-shop/composer.json`, topologically sorting into tiers, and identifying
  the downstream constraint keys (the `require`/`require-dev` entries on
  each dependent repo). Covers cycle detection, the `from`-anchored
  three-case version-resolution chain
  (unchanged-since-from → reuse / changed-with-usable-tag → use-latest /
  changed-without-usable-tag → CLI-cuts), the hybrid tag-cutting policy
  (shop-ce uses `--to`; every other repo bumps its own version line,
  default patch, overridable per-repo via
  `--bump <repo>=<minor|major|exact>`), the stability check
  (final shop releases reject pre-release dep tags; RC shop releases
  accept either), and the constraint-update logic (only touch a dependent's
  constraint when the existing one doesn't already satisfy the chosen tag).
- `release-notes-aggregation`: per-repo changelog generation using the
  `from_pin..chosen` commit range as anchor, aggregated into a single
  cross-repo markdown attached to the o3-shop draft GitHub release. Lists
  changed repos with their commit/PR/contributor summaries and the set of
  unchanged repos.
- `shop-version-resolution`: the runtime resolution chain replacing the
  hardcoded literal in `ShopVersion::getVersion()` (generated file →
  installed.json → git describe → fallback).
- `metapackage-fold-in`: the one-time prerequisite migration (see the
  Prerequisite section) of `shop-metapackage-ce`'s `require` list and
  `replace` clause into `o3-shop/composer.json`, plus archival of the
  source repo with a final tag and a README redirect. Modeled as a
  capability of this change when sequencing (1) (inline) is chosen; lifted
  to its own change when sequencing (2) is chosen.

### Modified Capabilities

(none — no existing spec covers release tooling or version resolution)

## Impact

- **shop-ce**
  - new `bin/release` CLI (PHP, Symfony Console — already a dep) and tests
  - `source/Core/ShopVersion.php` rewritten to resolve at runtime
  - `composer.json` post-install hook to materialize `version.generated.php`
- **o3-shop** repo
  - **no manifest file** — release behavior is fully derived from the
    composer.json dep-tree walk
  - `composer.json` absorbs the metapackage's `require` list (Symfony /
    Doctrine / shop-ce / themes / bundled modules / asset packages),
    minus deprecated entries (`flow-theme`, `vortex-theme`,
    `tests-deprecated-ce`) which are dropped during the fold-in, plus the
    `replace: oxid-esales/oxideshop-metapackage-ce` clause
- **shop-metapackage-ce** repo
  - **archived** with a final tag pinning current state and a README pointer
    to `o3-shop/o3-shop`. No further releases.
- **Every release-eligible repo** (everything reached by the dep-tree walk)
  - composer.json constraint bumps happen directly on the release branch
- **Wiki**
  - https://github.com/o3-shop/o3-shop/wiki/Create-a-Release rewritten to
    "run `bin/release <tag>` and publish the resulting drafts" plus a manual
    fallback for the case where the CLI is unavailable. Reflects the 3-tier
    graph; the metapackage step is removed.
- **No new runtime dependencies** for the shop itself; the CLI relies only on
  existing Symfony Console, the GitHub CLI (`gh`, already required by the
  current manual flow), and `git`. Graph derivation is plain HTTPS fetches of
  `raw.githubusercontent.com/.../composer.json` — no extra package needed.
- **Test impact** — new unit tests for `bin/release` orchestration, for the
  graph-derivation algorithm (synthetic composer.json fixtures: linear chain,
  diamond, missing dep, cycle, require-dev-only candidate,
  unchanged-since-from-reuses-pin, changed-with-newer-tag-uses-it,
  changed-without-tag-CLI-cuts, RC-target-accepts-final-dep,
  final-target-rejects-RC-dep, CLI-cut patch / minor / major / exact,
  missing-from-flag-errors, missing-to-flag-errors,
  pre-fold-in-from-snapshot-aborts,
  constraint-already-satisfies vs. needs-update),
  for release-notes aggregation (changed-repo / unchanged-repo /
  multi-repo summary), and for the three-step `ShopVersion` resolution
  chain. No existing tests
  should change behaviour beyond the version-string source. The metapackage
  fold-in is composer-metadata-only and exercised by an integration test that
  resolves `composer install` against the rewritten `o3-shop/composer.json`.
