## Context

The o3-shop release today is a manual walk across ~16 repos in the
`o3-shop` GitHub org, governed by a wiki page that only documents the
final three steps (shop-ce → metapackage → o3-shop). Each release also
requires a hand-edit of `source/Core/ShopVersion.php` and a coordinated
set of composer-constraint bumps. Issue #136 (RC1 shipped with stale
demodata) is a direct consequence of how easy it is to skip a satellite
repo.

This design specifies the implementation behind the proposal:

- a local PHP CLI (`bin/release`) inside `shop-ce` that drives the entire
  multi-repo flow from a single command,
- a runtime resolution chain for `ShopVersion::getVersion()` that removes
  the per-release file edit,
- the metapackage fold-in (executed as a prerequisite, see proposal) that
  collapses the release graph from four tiers to three.

The audience is the future maintainer reading the code and wondering
"why was this built this way?". For motivation, see `proposal.md`.

## Goals / Non-Goals

**Goals:**

- One command cuts a complete shop release across every release-eligible
  repo, with explicit `--from` and `--to` anchors.
- Zero per-release edits in committed source files (no more "Update
  ShopVersion to v..." commits).
- No release manifest to maintain — the dep graph derives mechanically
  from composer.json files; tiers fall out of the topological sort.
- Skip-unchanged is the natural default: a repo with no commits since
  `--from` ships its from-pin verbatim. No new tag, no new release.
- Maintainer-cut tags between releases are respected, not overwritten.
- Cross-repo release notes generated from the per-repo `from..to` commit
  ranges, attached to the o3-shop draft release.

**Non-Goals:**

- GitHub Actions integration. The CLI runs on a maintainer's machine
  only; remote triggers are deferred.
- Automatic `--from` detection. Both `--from` and `--to` are required;
  guessing introduces ambiguity and is not worth the marginal convenience.
- Releasing non-bundled modules (captcha, amazon-pay, country-vat, …).
  These ship on independent cadences as opt-in installs.
- Persistent backwards-looking releases that span arbitrary
  pre-fold-in `--from` tags. The CLI handles exactly one transitional
  pre-fold-in path (v1.6.0 → v1.6.1-RC1) via metapackage indirection
  in Step 1; no support for pre-1.6.0 `--from` tags.
- Conventional-commits parsing. Bump level is explicit per repo via
  `--bump` flags; commit-message inference is a future possibility.

## Decisions

### Local PHP CLI inside shop-ce, not GitHub Actions

The CLI is a Symfony Console command in `bin/release`, shipped with
shop-ce. Maintainers run it from a local clone.

**Rationale:** shop-ce already depends on Symfony Console (the same
framework that powers `bin/oe-console`), so no new runtime dependency.
Keeping the orchestration local means the maintainer's `gh auth status`,
`git config user.signingkey`, and SSH keys all already work — no need
to provision GitHub Actions secrets across 16 repos. A future GHA
wrapper can call `bin/release` non-interactively without redesign.

**Alternatives considered:**

- A standalone repo for release tooling: rejected — extra release
  surface for a tool that ships with the shop anyway.
- Bash script: rejected — branching logic, JSON parsing, and HTTPS
  fetches favor PHP/Console; we already test PHP code in this repo.
- GitHub Actions only: rejected — couples release availability to
  org-wide GHA budget, harder to debug locally, would require
  reauthenticating every secret in every repo.

### Mandatory `--from` and `--to`, no auto-detect

Both flags must be passed. The CLI errors out with a usage message
otherwise.

**Rationale:** auto-detecting "the previous shop release" requires
heuristics (final-only? same minor line? exclude RCs?) that quietly
disagree across maintainers. An explicit `--from v1.6.0 --to v1.6.1-RC1`
removes the guesswork, gives release notes a stable anchor, and makes
release transcripts self-documenting in shell history.

**Alternatives considered:**

- Auto-detect as default with override flag: rejected — a release
  tool's defaults are load-bearing; quiet wrong defaults are worse than
  loud explicit invocations.

### Zero release manifest; derive everything from composer.json

The release set, dep graph, tier ordering, and downstream constraint
keys are all derived at runtime from composer.json files. No
`release.manifest.yaml` exists.

**Rationale:** composer.json is already the source of truth for the
dep graph — a manifest can only transcribe it. Transcription is a
drift opportunity (see proposal for the audit confirming this).
Modules excluded from the fold-in (captcha, amazon-pay, …) never enter
the dep tree, so the algorithm doesn't need a release-eligibility list
either.

**Alternatives considered:**

- Central manifest in o3-shop: rejected after the audit (proposal,
  Why section).
- Per-repo `.release.yaml` files: rejected — adds 12 files to maintain
  for what derivation already gives us.
- GitHub topic (`release-chain`): rejected — depends on out-of-band
  GitHub config that's invisible to git history.

### Hybrid tag-cutting policy

When the CLI cuts a new tag (Step 3 case 3 in the proposal):

- `shop-ce` uses `--to` verbatim. The shop's release tag *is* the
  shop's version.
- Every other repo bumps its own version line, with this precedence
  for picking the bump level:
  1. `--bump <repo>=<level>` flag at invocation (ad-hoc override).
  2. `.next-bump` file at the repo root on the release branch
     (maintainer-declared, persistent in git). Single line:
     `patch` / `minor` / `major` / `v<exact-version>`. Consumed by
     the release — deleted in the same commit that bumps downstream
     constraints, so the next release defaults back to patch unless
     the maintainer commits a fresh marker.
  3. Default: patch.

**Rationale:** the existing version histories diverge (shop-ce on
`v1.5.x`, testing-library on `v1.2.x`, gdpr-optin-module on `v1.0.x`,
etc.). Forcing a lockstep version would discard that history without
benefit. Patch as the safe default never accidentally signals an API
break; minor/major is explicit when intended.

The two-channel override mirrors the algorithm's "use existing tag
if present" pattern: `.next-bump` is the persistent maintainer-side
declaration; the flag is the ad-hoc invocation-side override. Either
can be missing, both can be present (flag wins), and the release that
honors the file consumes it so the steady state is always "patch by
default, no marker present."

`.next-bump` never reaches a consumer's vendor directory. Two layers:
the CLI deletes the file in the same commit it cuts the tag from (so
the tagged state never contains it), and every release-eligible
`composer.json` carries `"archive": { "exclude": [".next-bump"] }`
so dev/branch installs (`composer require <pkg>:dev-b-1.6`) also skip
it for the in-between window when a maintainer has committed a marker
but the next release hasn't run yet.

**Alternatives considered:**

- Lockstep versioning (every repo jumps to `--to`): rejected — breaks
  semver history of every sub-repo and conflates "shop release" with
  "library release".
- Conventional-commits inference: deferred — useful, but requires
  enforcing commit-message format across the org. Compatible future
  work.
- Always-patch with no override: rejected — minor/major bumps do
  legitimately occur; forcing them out of band defeats the
  one-command goal.
- Flag-only (no file): rejected — for releases with 5+ non-patch
  bumps, remembering every flag at invocation time is error-prone
  and hides intent in shell history. The file lives in the repo
  with the change that prompted the bump.
- File-only (no flag): rejected — emergency overrides shouldn't
  require a commit-and-push round trip on the affected repo.

### Pre-fold-in `--from` supported via metapackage indirection

If `o3-shop/composer.json` at the `--from` tag still requires
`o3-shop/shop-metapackage-ce`, Step 1 recurses one level into the
metapackage's `composer.json` at the version pinned by `--from` and
harvests the per-tier-0 pins from there. The merged map becomes
`from_pin[]`.

**Rationale:** v1.6.0 shipped pre-fold-in (the original plan to
land the fold-in in v1.6.0 slipped). The first machine-driven
release is `--from v1.6.0 --to v1.6.1-RC1` — that run cuts RC1 from
a pre-fold-in `--from`. Aborting on pre-fold-in `--from` would
require a separate manual v1.6.1 release path, doubling the
release-tooling work. Reading the metapackage one level deeper is
~10 lines of additional code in Step 1 and reuses the existing
HTTPS fetcher. After v1.6.1-RC1 ships, every subsequent `--from` is
post-fold-in and the indirection branch is bypassed; the code stays
to handle the rare hypothetical of someone replaying the v1.6.0
transition (e.g. for forensics).

**Alternatives considered:**

- Abort on pre-fold-in `--from`: rejected — would force a separate
  manual v1.6.1 release path just to set up a post-fold-in `--from`
  for the first CLI-driven cut. Net cost (extra manual release flow,
  doubled release effort, more places for mistakes) exceeds the cost
  of the indirection branch.
- Special-case the indirection only for `v1.6.0` literal: rejected —
  more brittle than detecting "pre-fold-in" structurally
  (composer.json still requires the metapackage). The structural
  detection cleanly handles any pre-fold-in tag without hardcoding
  a version.
- Persistent backwards-looking releases for pre-1.6.0 tags: out of
  scope — the v1.5 → v1.6 boundary is a separate manual transition
  the CLI never crossed and never will.

### `from_pin[]` as the per-repo anchor

The CLI reads `o3-shop/composer.json` at the `--from` tag, builds a map
of `o3-shop/* package → exact pinned version`, and uses that as the
"what shipped last time?" anchor for every per-repo decision.

**Rationale:** every other anchor candidate (last tag of repo, last
date of shop release, latest tag matching minor line) breaks at edge
cases — RC tags, hotfix tags between shop releases, cross-line
backports. The shop's previous composer.json artifact is unambiguous
by construction.

**Alternatives considered:**

- Per-repo last-tag anchor: rejected — conflates "released with the
  shop" and "released independently between shop releases".
- Date anchor (commits after a timestamp): rejected — replays the same
  ambiguity as above and drifts under timezone semantics.

### Stability check on dep tag selection

When `--to` is a final shop release, the CLI rejects pre-release dep
tags (RC/alpha/beta). When `--to` is a pre-release, it accepts either.

**Rationale:** shipping a final shop with an RC dep would surprise
consumers — the shop is "stable", but a transitive lib is not. The
asymmetric rule lets RC shop releases pin to whichever dep state is
ready.

**Alternatives considered:**

- Accept any dep tag with a higher base version: rejected — silently
  ships RC libs in finals.
- Reject any pre-release dep regardless of shop stability: rejected —
  blocks valid RC shop releases that need to ship with RC libs.

### `ShopVersion` resolution chain

`ShopVersion::getVersion()` resolves at runtime, in this order:

1. `source/Core/version.generated.php` — written by a composer
   post-install hook from the release artifact metadata.
2. `Composer\InstalledVersions::getPrettyVersion('o3-shop/shop-ce')`
   — Composer's runtime API. Reads from
   `vendor/composer/installed.json`/`installed.php` and locates the
   project root via the autoloader, so the call works whether shop-ce
   is the project root or a vendor dep of an `o3-shop` project.
3. Hard-coded `dev` literal — for fresh git clones that haven't run
   `composer install` yet.

**Rationale:** the committed `ShopVersion.php` no longer carries a
literal version string, so the per-release commit (`Update ShopVersion
to v...`) goes away. The two real-world deployment shapes — release
zip / `composer install` deploy — are both covered by Step 1 (the
post-install hook fires) or Step 2 (Composer-aware checkout). Step 3
is honest output for a not-yet-installed checkout. No process forks,
no binary dependencies, no fragile path-walking.

**Alternatives considered:**

- Inject from environment variable: rejected — admins viewing the
  shop shouldn't need OPS-managed env vars to see a version number.
- Single source via composer post-install only: rejected — breaks
  composer-aware checkouts where the hook didn't fire (`--no-scripts`,
  hook errors).
- `git describe --tags --always` as a 3rd-step fallback: rejected —
  hits a fresh-clone-with-no-composer-install case that's
  not-quite-deployed anyway, while costing a process fork, a hard
  dependency on the `git` binary being present (some hardened
  production containers strip it), and a non-standard output format
  (`v1.6.0-3-gabc123`) that doesn't fit normal version-string
  consumers. `"dev"` is the more honest answer for that state.
- Read `composer.lock` instead of `installed.json`: rejected —
  `composer.lock` lives at the project root only, so the lookup from
  inside `vendor/o3-shop/shop-ce/` would have to path-walk for it
  (`../../` when shop-ce is a vendor dep, `./` when it's the project
  root — fragile). `installed.json` and the `Composer\InstalledVersions`
  API are the canonical Composer-runtime answer to "what version is
  installed?" — purpose-built, autoloader-resolved, and reflect the
  loaded code rather than the lockfile's intent.

### Constraint-update parsimony

Step 4 of the algorithm only modifies a dependent's `require` /
`require-dev` entry when the existing constraint does NOT already
satisfy the chosen tag. Caret/tilde constraints typically continue to
satisfy minor-version dep bumps and are left untouched; exact pins
get replaced verbatim.

**Rationale:** unnecessary constraint edits create noise commits that
muddy `git blame` and increase the diff every release. Only-write-on-
change matches how a careful maintainer would edit by hand.

**Alternatives considered:**

- Always rewrite to exact pin: rejected — strips intent from
  flexible constraints (the maintainer chose `^1.2.0` for a reason).
- Always pin to chosen tag in `require`, leave `require-dev` alone:
  rejected — adds asymmetry without principle.

### HTTPS fetches over `raw.githubusercontent.com` for graph derivation

The CLI fetches each repo's `composer.json` via plain HTTPS, not via
the GitHub API.

**Rationale:** zero-rate-limit (no auth required for public repos),
no API-version churn, no gh-CLI dependency for the graph walk. The
only API calls (`gh release create`, `gh pr create`) are for the
release flow itself, not the read-only walk.

**Alternatives considered:**

- GitHub API exclusively: rejected — rate-limited; for ~30 fetches
  per release-walk this is fine but breaks under heavy CI use.
- Local clones of every repo: rejected — slow first run, awkward
  state when local clones diverge from remote.

### Aggregated release notes via GitHub's generator

For every repo where `chosen != from_pin`, the CLI calls
`POST /repos/<owner>/<repo>/releases/generate-notes` with
`tag_name=<chosen>` and `previous_tag_name=<from_pin>` (the same
endpoint backing `gh release create --generate-notes`). The returned
markdown bodies are stitched under one `## <repo>` heading each, with
a closing `## Unchanged in this release` section. The aggregated
markdown is the body of the o3-shop draft release.

**Rationale:** GitHub already does the hard part — PR-label
categorization, contributor lists, "first-time contributor" badges,
sensible link formatting. We pay zero engineering cost for parsing
and benefit from each repo's existing `.github/release.yml`
configuration. The same `from_pin[]` map that drives the algorithm
also lets us answer "what changed across the whole network?" — which
is currently only knowable by clicking through 12 release pages.

**Alternatives considered:**

- Hand-rolled commit-message parser: rejected — duplicates work
  GitHub already does well, and forces conventional-commits adoption
  before we have demand for it.
- Conventional-commits inference: deferred — compatible future work;
  GitHub's generator already handles the common case.
- Manual changelog: rejected — not scalable, prone to omissions.
- Per-repo notes only (no aggregation): rejected — the cross-repo
  view is the whole point; admins shouldn't need to chase 12 release
  pages to understand a shop release.

### Bundled modules in scope; non-bundled modules out of scope

The fold-in keeps `gdpr-optin-module`, `usercentrics`, `tinymce-editor`
— they ship with the shop and participate in the release walk like
any other tier-0 dep. The o3-shop fork of `paypal-module` was in the
metapackage from 2022 to early 2026 but has already been removed from
every v1.6.0 RC tag and the v1.6.0 final tag; the fold-in just makes
that removal permanent.
Non-bundled modules (captcha, amazon-pay, country-vat, and the
upstream `oxid-solution-catalysts/paypal-module`) are opt-in installs
via the existing `oe:module:install:*` composer scripts and never
enter the dep tree.

**Rationale:** "what we ship" is decided by what's in
`o3-shop/composer.json`'s `require` list. Modules in the require list
are by definition bundled; modules not in it are opt-in. This is the
existing, working semantic — formalized.

**Alternatives considered:**

- Special-case modules with a "skip-tagging" flag: rejected — adds
  policy where the dep graph already encodes intent.

### Open-PR detection — two rules, two outcomes

The CLI applies different policies to two different kinds of open PRs:

- **Incoming PRs** (open PRs targeting the release branch) →
  **warn, proceed.** An open feature PR is the maintainer's signal of
  "more is coming," but releases sometimes legitimately cut a stable
  point with PRs in flight (hotfix, planned cutoff). Warning-not-
  aborting respects maintainer judgment while still surfacing the
  state.
- **Outgoing merge-back PRs** (open PRs from the release branch into
  `main`, matching the `Merge v<x>.<y>.<z> release into main` title
  pattern) → **abort.** The previous release's merge-back must be
  merged before the next release runs. Otherwise `main` drifts an
  unbounded distance behind the release line and the next release's
  merge-back PR layers on top of an unmerged predecessor — exactly
  the kind of state that causes "wait, which version of main are we
  on?" confusion.

**Rationale:** the asymmetry is principled. Incoming PRs are work
*toward* the release that the maintainer is choosing to cut without;
outgoing merge-back PRs are state debt from the *previous* release.
Forgetting incoming PRs hurts no one but the author of the PR.
Forgetting an outgoing merge-back diverges every consumer's view of
`main` from reality.

**Detection:** list open PRs per repo via
`gh pr list --base main --head <release-branch> --state open --json title`
and match the title prefix. The maintainer's auto-opened merge-back
PRs are predictable; ad-hoc PRs from a maintainer that happen to share
the title pattern would also block, which is fine — that's exactly the
state we want to require resolved.

**Alternatives considered:**

- Always warn, never abort: rejected — the merge-back debt is the
  whole reason the gate exists.
- Always abort on any open PR: rejected — too aggressive; many
  releases legitimately ship with feature PRs queued for the next
  release line.
- Detect via commit-graph divergence (release branch ahead of main by
  more than the current release range): rejected — slower, harder to
  explain, false-positives during the merge-back window itself.

## Risks / Trade-offs

- **CLI runs on the maintainer's machine** — local state (uncommitted
  changes, wrong branch, stale lockfile) can poison a release.
  *Mitigation:* per-repo pre-flight gates (clean tree check, on
  release branch, tests green) before any tag/push action; `--dry-run`
  shows the full plan without touching anything.

- **`--bump` flags must be remembered for every release that needs
  non-patch bumps.** *Mitigation:* `--dry-run` prints the planned tag
  for every repo, so the maintainer sees their omission before the
  release-cutting phase. Future iteration may add a `.next-bump` marker
  file per repo (proposal Q2 option ii).

- **Skip-unchanged trusts that "no commits since `from_pin`" means "no
  release needed".** *Mitigation:* the pre-flight gate runs each
  repo's test suite anyway when a new tag is being cut. For
  unchanged-and-skipped repos, the previous release's tests are the
  warranty.

- **HTTPS fetches require network reachability** to GitHub during the
  release. *Mitigation:* the walk is read-only and fast; the failure
  mode is a clear "could not fetch composer.json from <url>" before
  any state changes. Maintainer retries.

- **Draft GitHub releases require a manual click to publish.**
  *Trade-off accepted:* maintainer-driven publish is the safety belt
  preventing accidental ships; the wiki rewrite documents the click as
  Step 2 of the maintainer's release ritual.

- **The `replace: oxid-esales/oxideshop-metapackage-ce` clause moves
  to a `type: project` repo.** Composer supports this, and
  `testing-library` already does the equivalent — but it's slightly
  unusual. *Mitigation:* integration test resolves `composer install`
  against the rewritten `o3-shop/composer.json` to confirm Composer's
  resolver still rejects hybrid OXID + o3-shop installs.

- **Stability check is binary (RC vs. final).** Nuanced cases — alpha
  shop wanting only beta or higher dep tags — are not handled.
  *Trade-off accepted:* the org's existing flow uses RC and final
  only; alpha/beta is theoretical. Add a comparator override flag if
  it ever becomes load-bearing.

## Migration Plan

1. **Prerequisite (fold-in)** — see proposal's Prerequisite section.
   Done either inline as task 1 of this change or as a standalone
   change applied first. Result: `o3-shop/composer.json` directly pins
   shop-ce + tier-0 deps; `shop-metapackage-ce` is archived.

2. **Build and unit-test `bin/release`** against synthetic composer.json
   fixtures (linear chain, diamond, missing dep, cycle, skip-unchanged
   reuse, RC/final stability cases, pre-fold-in `--from` snapshot
   triggers metapackage indirection in Step 1). No live repos needed.

3. **Apply the fold-in edits** to o3-shop's release branch by hand:
   move metapackage `require` entries inline, drop deprecated entries,
   add `replace: oxid-esales/oxideshop-metapackage-ce`. Add
   `archive.exclude` for `.next-bump` to every release-eligible repo's
   composer.json. Commit and push to the release branches.

4. **Dry-run against `--from v1.6.0 --to v1.6.1-RC1`.** The
   `--dry-run` output is the integration test — every planned tag,
   commit, and release listed. Step 1 must apply the metapackage
   indirection. Maintainer reviews; iterate until clean.

5. **First live release** with the CLI:
   `bin/release --from v1.6.0 --to v1.6.1-RC1`. This run ships the
   entire change (bin/release itself, the runtime ShopVersion, the
   fold-in edits) as part of v1.6.1-RC1. Maintainer publishes the
   resulting drafts manually. No merge-back PRs (RC1 is pre-release).

5a. **shop-metapackage-ce archival** happens after v1.6.1 stabilizes
   (typically alongside the v1.6.1 final cut, not RC1): cut a final
   tag pinning the v1.6.0 state, flag the GitHub repo archived, and
   update the README to point at `o3-shop/o3-shop`.

6. **Wiki rewrite** — replace
   https://github.com/o3-shop/o3-shop/wiki/Create-a-Release with the
   `bin/release` workflow; keep a manual fallback for the case where
   the CLI is unavailable.

**Rollback strategy:** the CLI does not destroy state — every action
is a tag, a commit, or a draft release. To roll back a botched run:
- Delete unpublished draft GitHub releases.
- Delete unwanted tags (`git push --delete origin <tag>`).
- Revert the composer-constraint bump commits.
None of these require the CLI to participate in its own rollback.

## Open Questions

(none currently outstanding — all earlier open questions have been
resolved into decisions above.)
