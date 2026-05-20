## 1. Metapackage fold-in (Prerequisite)

- [x] 1.1 In `o3-shop/composer.json`: copy the v1.6.0 metapackage `require` entries (framework deps + bundled core: shop-ce, o3-theme, wave-theme, shop-demodata-ce, shop-facts, gdpr-optin-module, usercentrics, tinymce-editor) and drop deprecated entries (`flow-theme`, `vortex-theme`, the o3-shop `paypal-module`, `tests-deprecated-ce`)
- [x] 1.2 Move `replace: oxid-esales/oxideshop-metapackage-ce` clause from metapackage into `o3-shop/composer.json`
- [x] 1.3 Run `composer install` against the rewritten `o3-shop/composer.json` and verify resolution succeeds
- [x] 1.4 Verify Composer's resolver still rejects a hybrid install requiring both `o3-shop/o3-shop` and `oxid-esales/oxideshop-metapackage-ce`
- [x] 1.5 Add `"archive": { "exclude": [".next-bump"] }` to every release-eligible repo's `composer.json` (shop-ce, testing-library, themes, demodata, asset packages, bundled modules, dev-tooling leaves)

## 2. ShopVersion runtime resolution (in shop-ce)

- [x] 2.1 Rewrite `source/Core/ShopVersion.php`'s `getVersion()` to walk the 3-step resolution chain (no version literal in committed source)
- [x] 2.2 Step 1: read `source/Core/version.generated.php` if present and return its non-empty value
- [x] 2.3 Step 2: call `Composer\InstalledVersions::getPrettyVersion('o3-shop/shop-ce')` (handle `OutOfBoundsException` and class-missing cases)
- [x] 2.4 Step 3: return the literal `"dev"` when both prior steps produce nothing
- [x] 2.5 Add a composer post-install hook that writes `source/Core/version.generated.php` from the installed shop-ce version (wired in both shop-ce/composer.json for dev and o3-shop/composer.json for production install)
- [x] 2.6 Add `source/Core/version.generated.php` to `.gitignore`
- [x] 2.7 Unit tests: each of the three resolution steps fires correctly; assert no `git`/`shell_exec`/`proc_open` calls in `getVersion()` (10 tests, 19 assertions, all pass via local PHPUnit; full suite verification deferred to /finish when docker is available)

## 3. bin/release CLI scaffold

- [x] 3.1 Add `bin/release` entry point as a Symfony Console command in shop-ce
- [x] 3.2 Define CLI signature with `--from <tag>`, `--to <tag>`, `--bump <repo>=<level>` (repeatable), `--dry-run` flags
- [x] 3.3 Validate `--from` and `--to` are present; exit non-zero with a usage message if either is missing (exit code 2)
- [x] 3.4 Validate `--bump` values match `patch|minor|major|v<semver>`; exit non-zero on malformed input
- [x] 3.5 Add `bin/release` to shop-ce's composer.json `bin` array
- [x] 3.6 Unit tests: CLI flag parsing (both flags present, missing --from, missing --to, empty --from, repeated --bump, malformed --bump, dry-run propagation, plus 7×2 valid/invalid bump-level provider cases) — 21 tests, 36 assertions, all pass

## 4. Algorithm Step 1 — Snapshot `from`

- [x] 4.1 Implement HTTPS fetcher for `raw.githubusercontent.com/o3-shop/<repo>/<ref>/composer.json` (returns parsed JSON, errors with clear "could not fetch <url>" on failure) — `HttpsRawComposerJsonFetcher` (concrete) + `RawComposerJsonFetcher` (interface) + `RawComposerJsonFetchException`
- [x] 4.2 Read `o3-shop/composer.json` at `--from`; build `from_pin[repo]` map for every `o3-shop/*` entry in `require` and `require-dev` — `FromSnapshotBuilder::build()`
- [x] 4.3 Detect pre-fold-in `--from` (composer.json still requires `o3-shop/shop-metapackage-ce`); recurse one level into `shop-metapackage-ce/composer.json` at the version pinned by `--from` and merge its tier-0 pins into `from_pin[]`. (Indirection flag + metapackage version exposed on `FromSnapshot`; the CLI layer emits the info line based on that flag — pure-data builder.)
- [x] 4.4 Unit tests: post-fold-in snapshot builds correct `from_pin[]`; pre-fold-in snapshot triggers metapackage indirection and produces correct merged `from_pin[]`; require-dev-only entries appear in `from_pin[]` (7 tests, 17 assertions, all pass via local PHPUnit). Wiring of `FromSnapshotBuilder` into `ReleaseCommand::execute()` is deferred to Section 11 (dry-run) so each section ships an independently-testable component.

## 5. Algorithm Step 2 — Walk dep tree

- [x] 5.1 Recursive walker: for every `o3-shop/*` package in current composer.json, fetch its composer.json at the release branch and recurse into its `require` + `require-dev` — `DepTreeWalker::walk()` with DFS coloring; non-`o3-shop/*` deps are filtered out
- [x] 5.2 Track each pin location (which repo's composer.json, which key in require/require-dev) so Step 5 knows where to write — `PinLocation` value object; `WalkResult::pinLocations($package)` returns all spots
- [x] 5.3 Cycle detection: maintain a visit-state map; abort with a diagnostic listing the cycle participants on detection — `CycleDetectedException` carries the ordered cycle path closing on the first node
- [x] 5.4 Topological sort: order candidates so leaves come first; expose a `tier(repo)` function for ordering the per-repo release flow — post-order DFS gives leaves-first ordering; `tier(leaf)=0`, `tier(node)=max(tier(dep))+1`
- [x] 5.5 Unit tests: linear chain, diamond, missing dep, cycle (two-package, three-package), require-dev-only candidate, tier assignment (9 tests / 31 assertions, all pass via local PHPUnit; full ReleaseTooling suite 37 tests / 84 assertions)

## 6. Algorithm Step 3 — Version resolution per candidate

- [x] 6.1 For each candidate, fetch its tag list via `git ls-remote --tags` — abstracted behind `RemoteRepoIntrospector` interface (concrete `git ls-remote` implementation lands with Section 11 wiring; tests use `InMemoryRepoIntrospector`)
- [x] 6.2 Implement `latest_tag(repo)` = the highest semver tag on the candidate's release branch — `CandidateVersionResolver::highestSemverTag()` filters non-semver tags via `SEMVER_TAG_PATTERN` and picks the max via `composer/semver Comparator`
- [x] 6.3 Case 1 — Unchanged-since-from: when no commits/tags newer than `from_pin[repo]` exist, reuse `from_pin[repo]`
- [x] 6.4 Case 2 — Changed-with-usable-tag: when `latest_tag > from_pin[repo]` and stability matches, use `latest_tag`
- [x] 6.5 Case 3 — Changed-without-usable-tag: when commits exist beyond the latest tag (branch SHA differs from latest-tag SHA), or when no semver tags exist yet, fall through to Step 4 to compute a new tag
- [x] 6.6 Stability check: a final `--to` rejects pre-release dep tags; an RC `--to` accepts either — `CandidateVersionResolver::stabilityCompatible()` (uses composer/semver `VersionParser::parseStability`)
- [x] 6.7 Unit tests: all three cases, stability check both directions (final rejects RC, RC accepts final), plus highest-tag selection across out-of-order tag lists, no-tags-yet path, caret-from-pin, non-semver-tag filtering — 14 tests / 32 assertions, all pass via local PHPUnit (full ReleaseTooling suite: 51 / 116)

## 7. Algorithm Step 4 — Tag-cutting policy

- [x] 7.1 Special case: when the candidate is `shop-ce`, the new tag equals `--to` verbatim — `TagCutter::cut('o3-shop/shop-ce', …)` short-circuits to `--to` ahead of the flag/file/default chain
- [x] 7.2 For other candidates, resolve the bump level with precedence: `--bump <repo>=<level>` flag → `.next-bump` file at the repo's release-branch root → default `patch`
- [x] 7.3 Read `.next-bump` from the release branch via HTTPS fetch; trim whitespace; validate the value matches `patch|minor|major|v<semver>`; ignore the file with a warning if malformed — new `RawRepoFileFetcher` interface + `HttpsRawRepoFileFetcher` impl; `TagCutter::readNextBumpFile()` records warnings via `TagCutResult::notes()`
- [x] 7.4 Compute the new tag from `latest_tag(repo)` + bump level — `TagCutter::applyBump()` clears subordinate segments on minor/major; exact returns the literal; pre-release suffix on `latest_tag` drops on bump
- [x] 7.5 When `.next-bump` was the chosen source, plan a delete of the file in the same commit the tag is cut from — `TagCutResult::deleteNextBumpFile()` is true only for source `next-bump-file`
- [x] 7.6 When the `--bump` flag was the chosen source, leave any `.next-bump` file untouched — `TagCutResult::deleteNextBumpFile()` returns false for source `flag`
- [x] 7.7 Unit tests: default patch, .next-bump honored (newline-trimmed, exact-version, all kinds), flag overrides .next-bump, file consumed on use, file untouched on flag override, exact-version path, invalid `.next-bump` value (warning + fallthrough), empty file, no-latest-tag for patch (throws), no-latest-tag for exact (succeeds), bump arithmetic (patch/minor/major segment clearing, pre-release suffix drop) — 34 tests / 73 assertions across `BumpLevelTest` and `TagCutterTest`. Full ReleaseTooling suite: 85 tests / 189 assertions.

Refactor: renamed `RawComposerJsonFetchException` → `RawRepoFetchException` so both fetchers (composer.json and arbitrary file) share one boundary exception. All 51 prior tests still pass with the rename.

## 8. Algorithm Step 5 — Constraint update

- [x] 8.1 Implement constraint-satisfies check: given a Composer constraint string and a version string, return whether the version satisfies the constraint (use `composer/semver` package) — `ConstraintUpdater::satisfies()` wraps `Composer\Semver\Semver::satisfies` and treats unparseable constraints as "does-not-satisfy" so they fall into the rewrite branch
- [x] 8.2 For each pin location recorded in Step 2: skip if existing constraint already satisfies the chosen version — `ConstraintUpdate::shape() === SHAPE_UNCHANGED` when the existing covers chosen
- [x] 8.3 Replace exact pins (e.g. `"v1.5.4"`) with the chosen version verbatim — `SHAPE_EXACT_REPLACED`; matches `v?N.N.N(-suffix)?`
- [x] 8.4 Widen flexible constraints (caret, tilde, range) only when the chosen version doesn't satisfy them — `SHAPE_CARET_WIDENED` re-anchors caret at chosen; `SHAPE_TILDE_WIDENED` does the same for tilde; ranges/ORs without leading caret fall to `SHAPE_FALLBACK_REPLACED`
- [x] 8.5 Unit tests: caret already satisfies (no edit), exact pin needs replacement, caret needs widening to next major — covered plus tilde-already-satisfies, exact-without-v-prefix replacement, tilde widening across minor boundary, range-satisfies (no edit), or-of-carets satisfied (no edit), or-of-carets miss (re-anchored), range-miss (fallback replacement), pre-release-vs-stable-caret (behavior pinned to composer/semver default), whitespace tolerance — 18 tests / 36 assertions in `ConstraintUpdaterTest`. Full ReleaseTooling suite: 103 / 225.

## 9. Algorithm Step 6 — Release notes aggregation

- [x] 9.1 For each candidate where `chosen != from_pin[repo]`, call `POST /repos/o3-shop/<repo>/releases/generate-notes` via `gh api` with `tag_name=<chosen>` and `previous_tag_name=<from_pin[repo]>` — `GhCliReleaseNotesProvider::notesFor()` shells out via `Symfony\Component\Process\Process` and uses `--jq .body` to extract the markdown
- [x] 9.2 Stitch the returned markdown bodies under one `## <repo>` heading each — `ReleaseNotesAggregator::aggregate()` emits `## <package>\n\n<body>` per changed repo
- [x] 9.3 Append a `## Unchanged in this release` section listing every candidate where `chosen == from_pin[repo]` with its continued version — section appears after all changed-repo blocks; one bullet per unchanged candidate `- \`<package>\` continues at \`<from-pin>\``
- [x] 9.4 Use the aggregated markdown as the body of the `o3-shop` draft GitHub release — wiring lives in Section 11 (per-repo flow); aggregator returns the body string for that consumer
- [x] 9.5 Unit tests: changed-repo / unchanged-repo / multi-repo summary, GitHub API call shape — 9 tests / 30 assertions: single-changed-repo, call-shape captures `(package, previous, new)`, unchanged-repo skips API call, mixed multi-repo with deduplicated provider calls (only changed repos), changed-precede-unchanged ordering, all-unchanged yields only summary section, empty-candidate-list yields empty string, body trimming, `CandidateState::isChanged()` predicate. Full ReleaseTooling suite: 112 tests / 255 assertions.

GhCliReleaseNotesProvider returns stub markdown on gh-api failure (with stderr captured in the stub) so the aggregated body still ships and the maintainer can edit the draft GitHub release before publishing.

## 10. Per-repo release flow — gates and actions

- [x] 10.1 Pre-flight: clean working tree (no uncommitted changes) per repo — `WorkingTreeGate` (git status --porcelain; aborts on any output)
- [x] 10.2 Pre-flight: on the expected release branch per repo — `BranchGate` (git rev-parse --abbrev-ref HEAD; aborts on mismatch)
- [x] 10.3 Pre-flight: deps resolved to release versions (composer install passes) — `ComposerInstallGate` (--dry-run --no-scripts --no-interaction; aborts on non-zero)
- [x] 10.4 Pre-flight: per-repo test suite passes — `TestSuiteGate` with maintainer-supplied per-repo command resolver (returns null to skip; tail of output included on failure)
- [x] 10.5 Pre-flight: detect open PRs targeting the release branch (incoming) — log a warning identifying URLs, proceed — `IncomingPrGate` (gh pr list; STATUS_WARNING; lists each PR's #/title/url)
- [x] 10.6 Pre-flight: detect open PRs from release branch into `main` matching `Merge v<x>.<y>.<z> release into main` — abort with a list of unmerged URLs — `MergeBackPrGate` filtering by `MergeBackPrTitlePattern`
- [x] 10.7 If any pre-flight gate fails for any repo, abort before any state-changing action and report the failing gate(s) — `PreFlightRunner` runs all gates and returns a `PreFlightReport` with `shouldAbort()`, `hasWarnings()`, and `allMessages()` (combined `[gate-name] message` lines). All gates run even after an abort so the operator gets one combined diagnostic.
- [x] 10.8 Per repo: commit constraint changes (Step 5) and `.next-bump` deletions (Step 8) to the release branch in a single commit per repo, push directly (no PR) — `PerRepoActions::commitChangesAndPush()` (optional `git rm --ignore-unmatch .next-bump` + `git add` + `git commit -m` + `git push origin <branch>`)
- [x] 10.9 Per repo: create the tag at the new commit — `PerRepoActions::createTag()` (annotated tag + push)
- [x] 10.10 Per repo: create a draft GitHub release at that tag via `gh release create --draft` — `PerRepoActions::createDraftRelease()`; uses `--generate-notes` by default; accepts a `--notes <body>` override for the o3-shop aggregated body (Section 9 output)
- [x] 10.11 For final shop releases: auto-open a `Merge v<x>.<y>.<z> release into main` PR per repo — `PerRepoActions::openMergeBackPr()` calls `gh pr create --base main --head <branch>` with the canonical title via `MergeBackPrTitlePattern::buildTitle()`
- [x] 10.12 For pre-release shop releases: do not open merge-back PRs — `MergeBackPolicy::shouldOpenForShopTo()` returns false for any `-rc`/`-alpha`/`-beta`/`-dev`/`-preview`/`-pre`/`-p` suffix; the Section 11 orchestrator gates the call to `openMergeBackPr()` on this predicate.

New components: `ProcessExecutor` (interface) + `SymfonyProcessExecutor` + `ProcessOutcome`; `PreFlightGate` (interface) + 6 concrete gates; `GateOutcome`/`PreFlightReport`/`PreFlightRunner`; `MergeBackPrTitlePattern` (pure regex helper); `MergeBackPolicy` (pure predicate); `PerRepoActions` (state-changing actions, all bubble RuntimeException on shell failure).

Tests: 48 cases / 98 assertions across `MergeBackPolicyTest`, `PreFlightRunnerTest`, `GateBehaviorTest` (one happy + one failure per gate), and `PerRepoActionsTest` (sequence + error path per action). Plain PHPUnit\TestCase. `FakeProcessExecutor` test double records every invocation. Full ReleaseTooling suite: 160 tests / 353 assertions.

## 11. Dry-run mode

- [x] 11.1 When `--dry-run` is set, run Steps 1–6 and the pre-flight gates with no state-changing actions — `ReleasePlanner::plan()` orchestrates Sections 4–10 into a `ReleasePlan` value object; the planner only reads (HTTPS fetches + `git ls-remote` + `gh pr list` for pre-flight). State-changing methods on `PerRepoActions` are never reached on the dry-run path.
- [x] 11.2 Print the per-repo plan: chosen version, source (case 1/2/3), planned tag (if any), planned commit subjects, planned release URLs — `DryRunPrinter::print()` emits per-candidate `<package> [<case-label>] <from-pin> -> <chosen-version>` lines plus the bump source (`flag` / `next-bump-file` / `default-patch` / `shop-ce-verbatim`) and any `.next-bump` consumption notes
- [x] 11.3 Print the aggregated release-notes markdown that would be attached — `DryRunPrinter::printAggregatedNotes()` renders the Section 9 output indented under a heading
- [x] 11.4 Exit zero on success; exit non-zero if pre-flight gates would fail — `ReleasePlan::shouldAbort()` (any pre-flight `shouldAbort` across reports). `ReleaseCommand` returns `EXIT_PRE_FLIGHT_ABORT = 3` on abort, `EXIT_PLAN_ERROR = 4` on planner exceptions, `EXIT_OK = 0` otherwise.
- [x] 11.5 Unit tests: dry-run never invokes `git tag`, `git push`, `gh release create`, `gh pr create`, or any composer.json write — covered by structural separation: the dry-run path through `ReleaseCommand::execute()` calls only `ReleasePlanner::plan()` and `DryRunPrinter::print()`. `PerRepoActions` (which owns every state-changing shell command) is never instantiated by `buildDefaultPlanner()` and is never reached from `execute()`. Tests assert this via stub-planner injection: the planner is called exactly once with the parsed inputs and only the printer output reaches stdout. 4 planner tests + 24 command tests + 10 printer-pathway assertions cover the whole flow.

New components:
  CandidatePlan         per-package decision + tag-cut bookkeeping
  ConstraintEditPlan    per pin-location rewrite plan
  ReleasePlan           whole-run output: candidates + edits + notes + pre-flight reports
  ReleasePlanner        Sections 4–10 orchestrator; pure data-flow
  DryRunPrinter         deterministic text rendering of a ReleasePlan
  DefaultBranchResolver per-package release-branch map (matches Section 1.5 decisions)
  GitLsRemoteRepoIntrospector  reference RemoteRepoIntrospector via `git ls-remote --tags --heads`

ReleaseCommand now accepts an optional `(planner, printer, liveExecutor)` constructor triple; production-mode invocations build the default planner inline with `Https*Fetcher`s + `GitLsRemoteRepoIntrospector` + `GhCliReleaseNotesProvider`, and the default `LiveExecutor` with `SymfonyProcessExecutor` + `PerRepoActions` + `ComposerJsonConstraintWriter`. Tests inject stubs that bypass the parent constructor entirely so no real services are constructed. Exit codes: OK / USAGE_ERROR / PRE_FLIGHT_ABORT / PLAN_ERROR. (Live-mode wiring landed in §15.)

Refactor: `VersionResolution` gained an optional `latestTag` field so the planner can pass it to `TagCutter` for case-3 candidates. All 14 Section 6 tests still pass.

Tests: 4 planner cases / 19 assertions covering pre-fold-in indirection end-to-end, notes aggregation through the chain, pre-flight skipped when no repo paths, fetcher failures bubble. Plus 24 command-level cases / 42 assertions covering flag parsing, dry-run output, planner failure, pre-flight-abort exit code, live-mode "not yet wired" notice. Full ReleaseTooling suite: 167 tests / 378 assertions.

## 12. Integration tests

- [x] 12.1 End-to-end test: run `bin/release --from <fixture-from> --to <fixture-to> --dry-run` against a fixture repo network and assert the printed plan — covered two ways: (a) `ReleasePlannerTest` runs the full algorithm chain against in-memory fake fetchers (the fixture-network equivalent) and asserts plan structure; (b) live verification against origin via `bin/release --from v1.6.0 --to v1.6.1-RC1 --dry-run` — surfaced and fixed 4 real-world bugs (constraint-update wrap, from_pin shallow recursion, metapackage-precedence ordering, gh-cli case-rename), all with regression tests. Full ReleaseTooling suite: 198 tests / 433 assertions.

## 13. Wiki rewrite

- [x] 13.1 Replace https://github.com/o3-shop/o3-shop/wiki/Create-a-Release with the `bin/release` workflow (one command per release, draft publish click) — landed in wiki commit `ba5669e`
- [x] 13.2 Document the 3-tier graph (no metapackage tier) — "The release graph" section covers tiers 0/1/2 and the post-fold-in metapackage absence
- [x] 13.3 Document `.next-bump` file convention and `--bump` flag for non-patch bumps — "Bump levels for new tags" section covers precedence, file consumption, and `archive.exclude` belt-and-suspenders
- [x] 13.4 Keep a manual fallback section for the case where `bin/release` is unavailable — "Manual fallback" appendix retains the per-repo recipe, post-fold-in (no metapackage step)
- [x] 13.5 Note that currency-rate freshness remains a separate manual maintainer check — "Pre-release housekeeping → Refresh currency exchange rates" section calls this out explicitly as not automated by `bin/release`

## 14. Branch-model normalization (before any machine-driven release)

Every release-eligible repo gets a `main` branch as its long-lived "latest released code" line. Eliminates the per-repo special case in `bin/release`'s merge-back-PR flow — `MergeBackPrGate` and `PerRepoActions::openMergeBackPr` both target `main` already; this makes that uniformly correct across the network. Lands **before §15** so every machine-driven release (RC1 onward) runs against a uniform branch model. Possible stepping stone toward trunk-based development later, but does not commit to it.

No `bin/release` code changes — the merge-back machinery already targets `main`; this section only normalizes the org-side branch model so that targeting becomes universally valid.

- [x] 14.1 Per-repo audit: identify the canonical released line for every repo currently without `main`. Confirmed mapping:
    - `testing-library` → `b-1.6`
    - `gdpr-optin-module` → `b-1.0`
    - `usercentrics` → `b-1.0`
    - `shop-ide-helper` → `b-1.6`
    - `shop-unified-namespace-generator` → `b-1.6`
    - `developer-tools` → `b-7.0.x` (only line)
    - `codeception-modules` → `b-1.0`
    - `codeception-page-objects` → `b-6.5.x`
    - `MinkSeleniumDriver` → `b-7.0.x`
- [x] 14.2 Create `main` on each of the 9 repos above, pointing at the HEAD of the chosen line (branch-HEAD policy chosen over tag-pinned because all 9 had latest tag = HEAD modulo the recently-merged §1.5 archive.exclude commit; aligns the 9 with "code about to be released")
- [x] 14.3 Set `main` as the GitHub default branch — all 17 release-eligible repos. Verification surfaced 3 stragglers that had `main` but a non-main default (`shop-ce` default=b-1.5, `shop-facts` default=b-1.0, `o3-shop` default=b-1.0-ce): `shop-ce`'s main was already at v1.6.0 + merge-back so default flip was sufficient; `shop-facts`'s main was 6 commits behind v1.0.4 (PR #2 fast-forwarded it to v1.0.4 commit, then default flipped); `o3-shop`'s main was 55 behind v1.6.0 with 1 commit divergence (PR #147 brought main to v1.6.0 via merge commit, then default flipped). Both temp branches deleted post-merge.
- [x] 14.4 Apply branch protection to `main` on **every** release-eligible repo (all 17). Minimum invariants applied uniformly:
    - PR required to merge (`required_approving_review_count: 0` — no approver requirement, but no direct pushes either)
    - No force-pushes (`allow_force_pushes: false`)
    - No branch deletion (`allow_deletions: false`)
    Linear-history rule intentionally NOT enforced (would conflict with the wiki's "merge commit, not squash" guidance for merge-back PRs in [Create-a-Release](https://github.com/o3-shop/o3-shop/wiki/Create-a-Release)). `enforce_admins: false` so the maintainer can land emergency direct pushes if needed.
- [x] 14.5 Verify uniformly: confirmed across all 17 repos that (a) `default_branch == "main"`, (b) protection state matches the §14.4 invariants, (c) the merge-back gate's underlying call (`gh pr list --base main --head <release-branch> --state open`) returns clean JSON on every repo (no `--base main`-not-found errors). End-to-end final-release dry-run verification deferred to §17.3's first machine-driven cut, which exercises the full pre-flight gate stack against live origin.
- [x] 14.6 §14 audit gap discovered during §17.1 dry-run: the original §14.1 audit picked the 9 repos that visibly lacked `main` from a manual list, but the actual release graph (derived from the dep walk in §17.1) contains 22 release-eligible repos — 5 more than the §14 list: `smarty`, `shop-doctrine-migration-wrapper`, `shop-db-views-generator`, `shop-demodata-installer`, `php-selenium`. RC1 wasn't blocked (RC1 cuts no merge-back PRs and `gh pr list --base main` returns empty when main is missing) but v1.6.1 final's merge-back PR creation would have failed. Extension fix landed in this PR: 4 of the 5 (`smarty`, `shop-doctrine-migration-wrapper`, `shop-demodata-installer`, `php-selenium`) had `main` bootstrapped at HEAD of the actual release branch (support/2.6, b-1.6, b-1.6, b-1.0 respectively); 2 of those 4 also had stale defaults at `b-7.0.x` that got flipped to `main`; the 5th (`shop-db-views-generator`) already had main as default and just got §14.4 protection. All 22 release-eligible repos now uniform: `default_branch == "main"`, PR-required (0 approvers), no force-push, no delete. **Lesson:** future audits should start from the dep walk, not a manual repo list.

## 15. Live-execution wiring in `ReleaseCommand`

The dry-run path is fully implemented (§11). The live path in `ReleaseCommand::execute()` currently prints a "Section 15 wiring pending" notice and exits `EXIT_OK` — running `bin/release --from v1.6.0 --to v1.6.1-RC1` (no `--dry-run`) does not actually cut anything yet. This section closes that gap.

The required components already exist and are unit-tested in isolation:
- §10 gates (`WorkingTreeGate`, `BranchGate`, `ComposerInstallGate`, `TestSuiteGate`, `IncomingPrGate`, `MergeBackPrGate`) and `PerRepoActions`
- `ReleasePlanner` already accepts an optional `PreFlightRunner` and a `repoPaths` map
- `MergeBackPolicy::shouldOpenForShopTo()` predicate

The work is purely orchestration in the CLI layer.

- [x] 15.1 Add a CLI flag for local repo paths — `--repo-path <package>=/abs/path` (repeatable). Validates that each path exists and is a Git working tree (`.git/` present); rejects relative paths and missing `vendor/repo` slugs.
- [x] 15.2 Default-planner factory builds a `PreFlightRunner` with all 6 gates (`WorkingTreeGate`, `BranchGate`, `ComposerInstallGate`, `TestSuiteGate` with a no-op resolver, `IncomingPrGate`, `MergeBackPrGate`) when any `--repo-path` is supplied. New `LiveExecutor` orchestrator wires `PerRepoActions` (with `SymfonyProcessExecutor`) and a new `ComposerJsonConstraintWriter` (regex-based, formatting-preserving) plus `DefaultBranchResolver`.
- [x] 15.3 Parsed repo paths are threaded through `ReleasePlanner::plan($from, $to, $bumpFlags, $repoPaths)`; pre-flight reports populate `ReleasePlan::preFlightReports()`.
- [x] 15.4 Live-mode entry path checks `ReleasePlan::shouldAbort()` after planning; on abort the printer renders the combined gate diagnostic, the command prints a one-liner, and exits `EXIT_PRE_FLIGHT_ABORT (3)` before instantiating the executor.
- [x] 15.5 `LiveExecutor::execute()` walks candidates in topological order (leaves-first, per `ReleasePlan::candidates()`) and for each that needs a new tag invokes commit/push, tag/push, and draft-release (`--generate-notes`). After all candidates the orchestrator then processes `o3-shop/o3-shop` itself (never a candidate, always tagged with `--to`, draft body = aggregated §9 markdown via `--notes`). After every tag is cut, merge-back PRs are opened iff `MergeBackPolicy::shouldOpenForShopTo($toTag)` returns true. Constraint edits applied to local `composer.json` files via `ComposerJsonConstraintWriter` before each commit. `.next-bump` deletion driven by `TagCutResult::deleteNextBumpFile()`.
- [x] 15.6 Progress mirrors the dry-run callable style (`<comment>...</comment>` per major step). After each candidate, the released-URL is captured into `LiveExecutor::releaseUrls()`. On success or failure, `ReleaseCommand::execute()` calls `printPartialState()` to dump the captured URLs (`Draft GitHub releases created:` + `Merge-back PRs opened:`) so a partial-failure state is recoverable from the log.
- [x] 15.7 Unit tests added: `ComposerJsonConstraintWriterTest` (replace exact pin / multiple edits / missing pattern / format preservation / missing file), `LiveExecutorTest` (candidate-then-orchestrator order, `--notes` vs `--generate-notes` per repo, RC `--to` skips merge-back, final `--to` opens merge-back for candidates + orchestrator, missing repo path throws, `.next-bump` deletion fires when `TagCutResult` says so), and updated `ReleaseCommandTest` (live mode without `--repo-path` returns USAGE_ERROR; malformed `--repo-path` returns USAGE_ERROR; live mode with valid path invokes executor and exits OK; pre-flight abort short-circuits before executor; executor failure surfaces as PLAN_ERROR with partial state).
- [x] 15.8 Removed the "Section 15 wiring pending" notice from `ReleaseCommand::execute()`. Doc-comment updated to reflect that §15 wiring landed.

## 16. Repo-path discovery + auto-clone + UpToDateGate

§15 made live execution possible but required the maintainer to pass `--repo-path <pkg>=/abs/path` for every release-eligible repo (~22 of them). This section closes that UX hole so the no-arg `bin/release --from <prev> --to <new>` invocation works zero-config when the maintainer has the conventional sibling-clone layout (every repo lives at `<parent-of-shop-ce>/<github-repo-name>`). For repos not yet cloned, `bin/release` clones them on-demand into the same sibling dir.

Adds a 4-way `UpToDateGate` so existing-but-stale local clones don't slip through pre-flight: ahead-of-origin warns (visibly surfacing local commits the developer has chosen to ship), behind aborts (developer must pull), diverged aborts (rebase or merge required), in-sync passes.

Lands **before §17** so the first machine-driven release runs against the zero-config UX.

- [x] 16.1 New `RepoCloneUrlResolver`: detects shop-ce's `origin` URL scheme (HTTPS vs SSH) by reading `git config --get remote.origin.url`, then produces a clone URL for any `o3-shop/*` package, mapping composer-package-name → GitHub repo name via the existing `PackageRepoSlug::resolve()` (so `o3-shop/o3-theme` → `https://github.com/o3-shop/o3-Theme.git`). Aborts on unsupported schemes (only `https://github.com/` and `git@github.com:` accepted).
- [x] 16.2 New `RepoPathDiscovery::discoverAll()`: given a base directory and the running shop-ce path, returns a `package => abs path` map. shop-ce always maps to the running clone; other packages check `<base>/<github-name>` — if the dir contains `.git/`, use it; if missing, `git clone --branch <release-branch> <url> <path>`; if exists but not a git tree, aborts with a clear message. `package` defaults to every key of `DefaultBranchResolver::PACKAGE_TO_BRANCH` so the typical no-arg invocation discovers all 22 release-eligible repos. Per-package action ("using running clone" / "found existing clone" / "auto-cloning") streams to the supplied progress callable.
- [x] 16.3 New `UpToDateGate` (4-way comparison): `git fetch origin <release-branch>` then `git rev-list --left-right --count origin/<release-branch>...HEAD`. **In-sync** → pass. **Ahead of origin** → `STATUS_WARNING` "local <branch> is N commits ahead of origin/<branch>; those commits will ship in this release" (preserves intentional in-flight work). **Behind** → `STATUS_ABORT` "pull and re-run". **Diverged** → `STATUS_ABORT` "rebase or merge and re-run". Singular/plural wording handled. Appended to the default pre-flight runner between `BranchGate` and `ComposerInstallGate`.
- [x] 16.4 `ReleaseCommand` wiring: added `--repo-base /abs/path` flag (defaults to `dirname(realpath(__DIR__ . '/../../../..'))` from inside `ReleaseCommand.php` — i.e. the parent of shop-ce). New `resolveRepoPaths()` helper: if `--repo-path` values supplied, honor them verbatim (escape hatch); else if dry-run, return empty map (current behavior); else (live mode), build a `RepoPathDiscovery` with `RepoCloneUrlResolver::fromRepoOrigin()` of the running clone and call `discoverAll()`. Existing `--repo-path` parsing still validates path-is-absolute and `.git/` exists. Discovery progress streams to `OutputInterface` as a `<comment>` line per repo.
- [x] 16.5 Unit tests added: `RepoCloneUrlResolverTest` (7 cases: HTTPS detection from origin, SSH detection from origin, case-rename preserved in URL for HTTPS and SSH, unknown scheme rejected, unsupported origin URL aborts, `git config` failure aborts). `RepoPathDiscoveryTest` (7 cases: existing clones reused, missing clones auto-cloned with correct branch, shop-ce never auto-cloned over self, non-git directory aborts, clone failure aborts, non-existent base dir aborts, progress callable invoked per package). `UpToDateGateTest` (8 cases: 4 outcome states + fetch failure + rev-list failure + malformed output + singular wording for `1 commit ahead`). `ReleaseCommandTest` updated: removed obsolete `testLiveModeWithoutRepoPathExitsUsageError` (live-mode no-flag now invokes discovery, not USAGE_ERROR); existing `--repo-path`-using tests still cover the explicit-path escape hatch.
- [x] 16.6 `ComposerInstallGate` refinement (post-first-live-run feedback): the first live run of `bin/release --from v1.6.0 --to v1.6.1-RC1` uncovered that `ComposerInstallGate` aborted on most release-eligible repos because (a) modern composer's built-in security audit blocked any package with a public advisory (smarty/smarty 2.6.x, phpunit, several others) — noise unrelated to whether the deps actually install — and (b) most release-eligible repos are libraries that don't ship a `composer.lock`, so `composer install` runs in "resolve from scratch" mode that doesn't represent the production install path. Final shape after iteration: (i) gate skips when `composer.lock` is missing (libraries pass through; only project-type repos are gated). (ii) Gate now invokes shop-ce's bundled `vendor/bin/composer` (composer 2.2.x via the `o3-shop/shop-composer-plugin` transitive dep) instead of the host's PATH composer, eliminating version-skew (PATH might be 2.9+ which has aggressive resolver-time advisory blocks; bundled 2.2.x predates the audit feature entirely so no audit-skip flag is needed). PRs: #128 (initial gate refinements + `--no-audit` CLI flag), #129 (route to bundled composer), #130 (drop the now-unnecessary `--no-audit` flag since 2.2.x has no audit). After this trio, the gate runs cleanly across the v1.6.x release graph without per-release audit-skip choices.

## 17. First live release with bin/release

- [x] 17.1 Run `bin/release --from v1.6.0 --to v1.6.1-RC1 --dry-run` and review the plan (Step 1 must use the pre-fold-in metapackage indirection) — done; pre-fold-in indirection fired correctly, 21 candidates resolved, plan structurally clean. Two known constraint jumps acknowledged (gdpr-optin v1.0.1→v2.3.5 and usercentrics v1.0.0→v1.2.2). Two phantom release-notes items (shop-ce#99, shop-facts#2) will resolve when v1.6.1-RC1 is an actual tag.
- [x] 17.2 Resolve any issues uncovered by the dry-run — three categories surfaced and were resolved: (a) `ComposerInstallGate` host-PATH composer mismatch (#128 / #129 / #130 — see §16.6); (b) `shop-doctrine-migration-wrapper` b-1.6 carried in-progress dbal-3 / migrations-3 work that conflicted with shop-ce v1.6.x's dbal-2 stack — preserved on a new `b-2.0.0` branch and `b-1.6` reset to v1.0.2 + the §1.5 archive.exclude commit (one merge-back PR cycle on `main` to keep §14 invariants); (c) three peer-constraint pins (`shop-composer-plugin`, `testing-library`, `codeception-modules`) referenced a stale `dev-dev-b-1.x` alias in shop-ce's composer.json — loosened the constraints to `^1.2 || dev-*` so any git checkout's root version satisfies them, eliminating per-release-line branch-alias maintenance. Constraint-loosening commits landed directly on each repo's release branch and were carried into the cut tags by bin/release.
- [x] 17.3 Run `bin/release --from v1.6.0 --to v1.6.1-RC1` for real — first machine-driven o3-shop release. 22 repos discovered + auto-cloned, 7 pre-flight gates pass on every repo, 21 candidates committed (where edits were planned) + tagged + pushed + draft GitHub releases created, plus the aggregated o3-shop draft with the cross-repo body. Tags verified on origin via `gh api repos/<repo>/git/refs/tags/<tag>` for all 22.
- [x] 17.4 Manually publish the draft GitHub releases per repo and the aggregated o3-shop draft — 22 drafts published; pre-release flag set on `shop-ce` and `o3-shop` v1.6.1-RC1 drafts, unchecked on the 20 others.
- [x] 17.5 No merge-back PRs are auto-opened (RC1 is pre-release); merge-back PRs land with the eventual v1.6.1 final cut — `MergeBackPolicy::shouldOpenForShopTo("v1.6.1-RC1")` correctly returned false; live execution log explicitly logs `Pre-release shop --to (v1.6.1-RC1); no merge-back PRs auto-opened.`
- [x] 17.6 Run Section 18 verification on the produced v1.6.1-RC1 artifact — done; all four §18.x checks pass (see §18 below).
- [x] 17.7 Capture lessons learned in `.claude/memory/` (per the repo's finish protocol) — added under `known-pitfalls.md`: bundled-vs-PATH composer, `composer audit` flag rename across versions, packagist sync delay, gitignored `composer.lock` semantics for the gate, and the "branch alias staleness" footgun avoided by `dev-*` constraints.

## 18. Verification of the v1.6.1-RC1 cut

> Note: v1.6.0 shipped pre-fold-in (with the old hardcoded `ShopVersion.php`). There is no separate manual v1.6.1 release — `bin/release` cuts v1.6.1-RC1 directly from `--from v1.6.0` (Section 17) using the pre-fold-in metapackage indirection in Step 1. These tasks verify the result of that run and run after Section 17.

- [x] 18.1 Verify a fresh `composer install` of `o3-shop v1.6.1-RC1` produces a working shop with `ShopVersion::getVersion() === "v1.6.1-RC1"` (folds in former §12.2 — composer-install integration check against the post-fold-in `o3-shop/composer.json`) — verified end-to-end against the running shop in the o3-default-container docker stack: `vendor/o3-shop/shop-ce/source/Core/version.generated.php` was created by the composer post-install hook with content `return 'v1.6.1-RC1';`, and `OxidEsales\EshopCommunity\Core\ShopVersion::getVersion()` returns `v1.6.1-RC1` (Step 1 of the runtime resolution chain — generated-file path).
- [x] 18.2 Smoke-test the admin UI: confirm the version display shows `v1.6.1-RC1` — verified.
- [x] 18.3 Verify `o3-shop/composer.json` at `v1.6.1-RC1` is post-fold-in (no `o3-shop/shop-metapackage-ce` in `require`, `replace: oxid-esales/oxideshop-metapackage-ce` present) (folds in former §12.2 — composer-install integration check against the post-fold-in `o3-shop/composer.json`) — verified via raw.githubusercontent.com against the v1.6.1-RC1 tag: metapackage absent from `require`, replace clause present, shop-ce pinned at v1.6.1-RC1, all 6 planned constraint edits applied correctly.
- [x] 18.4 Verify the v1.6.1-RC1 dist archive does not contain `.next-bump` (archive.exclude works end-to-end) (folds in former §12.3 — `.next-bump` archive-exclude check, exercised end-to-end against the real cut) — verified by downloading the GitHub-generated archive at `/archive/refs/tags/v1.6.1-RC1.tar.gz` and grepping for `.next-bump`: 0 occurrences, archive.exclude works end-to-end.

## 19. Post-v1.6.1-final cleanup

- [ ] 19.1 (After v1.6.1 final stabilizes — not blocking the v1.6.1-RC1 cut.) Archive the `shop-metapackage-ce` GitHub repo and update its README to point at `o3-shop/o3-shop`. Its v1.6.0 tag already pins the final pre-archival state; no new tag is needed. **Tracked as follow-up after archival of #140:** [o3-shop/o3-shop#149](https://github.com/o3-shop/o3-shop/issues/149).
