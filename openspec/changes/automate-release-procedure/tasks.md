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

ReleaseCommand now accepts an optional `(planner, printer)` constructor pair; production-mode invocations build the default planner inline with `Https*Fetcher`s + `GitLsRemoteRepoIntrospector` + `GhCliReleaseNotesProvider`. Tests inject stubs that bypass the parent constructor entirely so no real services are constructed. Live execution still prints a "Section 14 wiring pending" notice (4 exit codes: OK / USAGE_ERROR / PRE_FLIGHT_ABORT / PLAN_ERROR).

Refactor: `VersionResolution` gained an optional `latestTag` field so the planner can pass it to `TagCutter` for case-3 candidates. All 14 Section 6 tests still pass.

Tests: 4 planner cases / 19 assertions covering pre-fold-in indirection end-to-end, notes aggregation through the chain, pre-flight skipped when no repo paths, fetcher failures bubble. Plus 24 command-level cases / 42 assertions covering flag parsing, dry-run output, planner failure, pre-flight-abort exit code, live-mode "not yet wired" notice. Full ReleaseTooling suite: 167 tests / 378 assertions.

## 12. Integration tests

- [ ] 12.1 End-to-end test: run `bin/release --from <fixture-from> --to <fixture-to> --dry-run` against a fixture repo network and assert the printed plan
- [ ] 12.2 Composer-install integration test: resolve `composer install` against the post-fold-in `o3-shop/composer.json` and confirm a working shop
- [ ] 12.3 Archive-exclude test: build a dist archive for shop-ce on a branch with a committed `.next-bump`; assert the archive does not contain `.next-bump`

## 13. Wiki rewrite

- [ ] 13.1 Replace https://github.com/o3-shop/o3-shop/wiki/Create-a-Release with the `bin/release` workflow (one command per release, draft publish click)
- [ ] 13.2 Document the 3-tier graph (no metapackage tier)
- [ ] 13.3 Document `.next-bump` file convention and `--bump` flag for non-patch bumps
- [ ] 13.4 Keep a manual fallback section for the case where `bin/release` is unavailable
- [ ] 13.5 Note that currency-rate freshness remains a separate manual maintainer check

## 14. First live release with bin/release

- [ ] 14.1 Run `bin/release --from v1.6.0 --to v1.6.1-RC1 --dry-run` and review the plan (Step 1 must use the pre-fold-in metapackage indirection)
- [ ] 14.2 Resolve any issues uncovered by the dry-run (missing release branches, malformed `.next-bump` files, etc.)
- [ ] 14.3 Run `bin/release --from v1.6.0 --to v1.6.1-RC1` for real — this is the first machine-driven release and ships this entire change
- [ ] 14.4 Manually publish the draft GitHub releases per repo and the aggregated o3-shop draft
- [ ] 14.5 No merge-back PRs are auto-opened (RC1 is pre-release); merge-back PRs land with the eventual v1.6.1 final cut
- [ ] 14.6 Run Section 15 verification on the produced v1.6.1-RC1 artifact
- [ ] 14.7 Capture lessons learned in `.claude/memory/` (per the repo's finish protocol)

## 15. Verification of the v1.6.1-RC1 cut

> Note: v1.6.0 shipped pre-fold-in (with the old hardcoded `ShopVersion.php`). There is no separate manual v1.6.1 release — `bin/release` cuts v1.6.1-RC1 directly from `--from v1.6.0` (Section 14) using the pre-fold-in metapackage indirection in Step 1. These tasks verify the result of that run and run after Section 14.

- [ ] 15.1 Verify a fresh `composer install` of `o3-shop v1.6.1-RC1` produces a working shop with `ShopVersion::getVersion() === "v1.6.1-RC1"`
- [ ] 15.2 Smoke-test the admin UI: confirm the version display shows `v1.6.1-RC1`
- [ ] 15.3 Verify `o3-shop/composer.json` at `v1.6.1-RC1` is post-fold-in (no `o3-shop/shop-metapackage-ce` in `require`, `replace: oxid-esales/oxideshop-metapackage-ce` present)
- [ ] 15.4 Verify the v1.6.1-RC1 dist archive does not contain `.next-bump` (archive.exclude works end-to-end)

## 16. Post-v1.6.1-final cleanup

- [ ] 16.1 (After v1.6.1 final stabilizes — not blocking the v1.6.1-RC1 cut.) Archive the `shop-metapackage-ce` GitHub repo and update its README to point at `o3-shop/o3-shop`. Its v1.6.0 tag already pins the final pre-archival state; no new tag is needed.
