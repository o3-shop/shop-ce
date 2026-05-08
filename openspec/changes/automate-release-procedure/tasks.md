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

## 3. Verification of the v1.6.1-RC1 cut (after Section 15 runs)

> Note: v1.6.0 shipped pre-fold-in (with the old hardcoded `ShopVersion.php`). There is no separate manual v1.6.1 release — `bin/release` cuts v1.6.1-RC1 directly from `--from v1.6.0` (Section 15) using the pre-fold-in metapackage indirection in Step 1. These tasks verify the result of that run.

- [ ] 3.1 Verify a fresh `composer install` of `o3-shop v1.6.1-RC1` produces a working shop with `ShopVersion::getVersion() === "v1.6.1-RC1"`
- [ ] 3.2 Smoke-test the admin UI: confirm the version display shows `v1.6.1-RC1`
- [ ] 3.3 Verify `o3-shop/composer.json` at `v1.6.1-RC1` is post-fold-in (no `o3-shop/shop-metapackage-ce` in `require`, `replace: oxid-esales/oxideshop-metapackage-ce` present)
- [ ] 3.4 Verify the v1.6.1-RC1 dist archive does not contain `.next-bump` (archive.exclude works end-to-end)

## 4. bin/release CLI scaffold

- [ ] 4.1 Add `bin/release` entry point as a Symfony Console command in shop-ce
- [ ] 4.2 Define CLI signature with `--from <tag>`, `--to <tag>`, `--bump <repo>=<level>` (repeatable), `--dry-run` flags
- [ ] 4.3 Validate `--from` and `--to` are present; exit non-zero with a usage message if either is missing
- [ ] 4.4 Validate `--bump` values match `patch|minor|major|v<semver>`; exit non-zero on malformed input
- [ ] 4.5 Add `bin/release` to shop-ce's composer.json `bin` array
- [ ] 4.6 Unit tests: CLI flag parsing (both flags present, missing --from, missing --to, repeated --bump, malformed --bump)

## 5. Algorithm Step 1 — Snapshot `from`

- [ ] 5.1 Implement HTTPS fetcher for `raw.githubusercontent.com/o3-shop/<repo>/<ref>/composer.json` (returns parsed JSON, errors with clear "could not fetch <url>" on failure)
- [ ] 5.2 Read `o3-shop/composer.json` at `--from`; build `from_pin[repo]` map for every `o3-shop/*` entry in `require` and `require-dev`
- [ ] 5.3 Detect pre-fold-in `--from` (composer.json still requires `o3-shop/shop-metapackage-ce`); recurse one level into `shop-metapackage-ce/composer.json` at the version pinned by `--from` and merge its tier-0 pins into `from_pin[]`. Log a single info line stating that pre-fold-in indirection was applied.
- [ ] 5.4 Unit tests: post-fold-in snapshot builds correct `from_pin[]`; pre-fold-in snapshot triggers metapackage indirection and produces correct merged `from_pin[]`; require-dev-only entries appear in `from_pin[]`

## 6. Algorithm Step 2 — Walk dep tree

- [ ] 6.1 Recursive walker: for every `o3-shop/*` package in current composer.json, fetch its composer.json at the release branch and recurse into its `require` + `require-dev`
- [ ] 6.2 Track each pin location (which repo's composer.json, which key in require/require-dev) so Step 5 knows where to write
- [ ] 6.3 Cycle detection: maintain a visit-state map; abort with a diagnostic listing the cycle participants on detection
- [ ] 6.4 Topological sort: order candidates so leaves come first; expose a `tier(repo)` function for ordering the per-repo release flow
- [ ] 6.5 Unit tests: linear chain, diamond, missing dep, cycle (two-package, three-package), require-dev-only candidate, tier assignment

## 7. Algorithm Step 3 — Version resolution per candidate

- [ ] 7.1 For each candidate, fetch its tag list via `git ls-remote --tags`
- [ ] 7.2 Implement `latest_tag(repo)` = the highest semver tag on the candidate's release branch (matches `b-X.Y.Z` or `b-X.Y` per the proposal's branch convention)
- [ ] 7.3 Case 1 — Unchanged-since-from: when no commits/tags newer than `from_pin[repo]` exist, reuse `from_pin[repo]`
- [ ] 7.4 Case 2 — Changed-with-usable-tag: when `latest_tag > from_pin[repo]` and stability matches, use `latest_tag`
- [ ] 7.5 Case 3 — Changed-without-usable-tag: when commits exist beyond the latest tag, fall through to Step 4 to compute a new tag
- [ ] 7.6 Stability check: a final `--to` rejects pre-release dep tags; an RC `--to` accepts either
- [ ] 7.7 Unit tests: all three cases, stability check both directions (final rejects RC, RC accepts final)

## 8. Algorithm Step 4 — Tag-cutting policy

- [ ] 8.1 Special case: when the candidate is `shop-ce`, the new tag equals `--to` verbatim
- [ ] 8.2 For other candidates, resolve the bump level with precedence: `--bump <repo>=<level>` flag → `.next-bump` file at the repo's release-branch root → default `patch`
- [ ] 8.3 Read `.next-bump` from the release branch via HTTPS fetch; trim whitespace; validate the value matches `patch|minor|major|v<semver>`; ignore the file with a warning if malformed
- [ ] 8.4 Compute the new tag from `latest_tag(repo)` + bump level (e.g. `v1.2.5` + `minor` = `v1.3.0`; `v1.0.1` + exact `v2.0.0` = `v2.0.0`)
- [ ] 8.5 When `.next-bump` was the chosen source, plan a delete of the file in the same commit the tag is cut from
- [ ] 8.6 When the `--bump` flag was the chosen source, leave any `.next-bump` file untouched
- [ ] 8.7 Unit tests: default patch, .next-bump honored, flag overrides .next-bump, file consumed on use, file untouched on flag override, exact-version path, invalid .next-bump value

## 9. Algorithm Step 5 — Constraint update

- [ ] 9.1 Implement constraint-satisfies check: given a Composer constraint string and a version string, return whether the version satisfies the constraint (use `composer/semver` package)
- [ ] 9.2 For each pin location recorded in Step 2: skip if existing constraint already satisfies the chosen version
- [ ] 9.3 Replace exact pins (e.g. `"v1.5.4"`) with the chosen version verbatim
- [ ] 9.4 Widen flexible constraints (caret, tilde, range) only when the chosen version doesn't satisfy them
- [ ] 9.5 Unit tests: caret already satisfies (no edit), exact pin needs replacement, caret needs widening to next major

## 10. Algorithm Step 6 — Release notes aggregation

- [ ] 10.1 For each candidate where `chosen != from_pin[repo]`, call `POST /repos/o3-shop/<repo>/releases/generate-notes` via `gh api` with `tag_name=<chosen>` and `previous_tag_name=<from_pin[repo]>`
- [ ] 10.2 Stitch the returned markdown bodies under one `## <repo>` heading each
- [ ] 10.3 Append a `## Unchanged in this release` section listing every candidate where `chosen == from_pin[repo]` with its continued version
- [ ] 10.4 Use the aggregated markdown as the body of the `o3-shop` draft GitHub release
- [ ] 10.5 Unit tests: changed-repo / unchanged-repo / multi-repo summary, GitHub API call shape

## 11. Per-repo release flow — gates and actions

- [ ] 11.1 Pre-flight: clean working tree (no uncommitted changes) per repo
- [ ] 11.2 Pre-flight: on the expected release branch per repo
- [ ] 11.3 Pre-flight: deps resolved to release versions (composer install passes)
- [ ] 11.4 Pre-flight: per-repo test suite passes
- [ ] 11.5 Pre-flight: detect open PRs targeting the release branch (incoming) — log a warning identifying URLs, proceed
- [ ] 11.6 Pre-flight: detect open PRs from release branch into `main` matching `Merge v<x>.<y>.<z> release into main` — abort with a list of unmerged URLs
- [ ] 11.7 If any pre-flight gate fails for any repo, abort before any state-changing action and report the failing gate(s)
- [ ] 11.8 Per repo: commit constraint changes (Step 5) and `.next-bump` deletions (Step 8) to the release branch in a single commit per repo, push directly (no PR)
- [ ] 11.9 Per repo: create the tag at the new commit
- [ ] 11.10 Per repo: create a draft GitHub release at that tag via `gh release create --draft` (let GitHub auto-generate the body)
- [ ] 11.11 For final shop releases (`--to` has no `-rc`/`-alpha`/`-beta` suffix): auto-open a `Merge v<x>.<y>.<z> release into main` PR per repo via `gh pr create --base main --head <release-branch>`
- [ ] 11.12 For pre-release shop releases: do not open merge-back PRs

## 12. Dry-run mode

- [ ] 12.1 When `--dry-run` is set, run Steps 1–6 and the pre-flight gates with no state-changing actions
- [ ] 12.2 Print the per-repo plan: chosen version, source (case 1/2/3), planned tag (if any), planned commit subjects, planned release URLs
- [ ] 12.3 Print the aggregated release-notes markdown that would be attached
- [ ] 12.4 Exit zero on success; exit non-zero if pre-flight gates would fail
- [ ] 12.5 Unit tests: dry-run never invokes `git tag`, `git push`, `gh release create`, `gh pr create`, or any composer.json write

## 13. Integration tests

- [ ] 13.1 End-to-end test: run `bin/release --from <fixture-from> --to <fixture-to> --dry-run` against a fixture repo network and assert the printed plan
- [ ] 13.2 Composer-install integration test: resolve `composer install` against the post-fold-in `o3-shop/composer.json` and confirm a working shop
- [ ] 13.3 Archive-exclude test: build a dist archive for shop-ce on a branch with a committed `.next-bump`; assert the archive does not contain `.next-bump`

## 14. Wiki rewrite

- [ ] 14.1 Replace https://github.com/o3-shop/o3-shop/wiki/Create-a-Release with the `bin/release` workflow (one command per release, draft publish click)
- [ ] 14.2 Document the 3-tier graph (no metapackage tier)
- [ ] 14.3 Document `.next-bump` file convention and `--bump` flag for non-patch bumps
- [ ] 14.4 Keep a manual fallback section for the case where `bin/release` is unavailable
- [ ] 14.5 Note that currency-rate freshness remains a separate manual maintainer check

## 15. First live release with bin/release

- [ ] 15.1 Run `bin/release --from v1.6.0 --to v1.6.1-RC1 --dry-run` and review the plan (Step 1 must use the pre-fold-in metapackage indirection)
- [ ] 15.2 Resolve any issues uncovered by the dry-run (missing release branches, malformed `.next-bump` files, etc.)
- [ ] 15.3 Run `bin/release --from v1.6.0 --to v1.6.1-RC1` for real — this is the first machine-driven release and ships this entire change
- [ ] 15.4 Manually publish the draft GitHub releases per repo and the aggregated o3-shop draft
- [ ] 15.5 No merge-back PRs are auto-opened (RC1 is pre-release); merge-back PRs land with the eventual v1.6.1 final cut
- [ ] 15.6 Run Section 3 verification on the produced v1.6.1-RC1 artifact
- [ ] 15.7 Capture lessons learned in `.claude/memory/` (per the repo's finish protocol)

## 16. Post-v1.6.1-final cleanup

- [ ] 16.1 (After v1.6.1 final stabilizes — not blocking the v1.6.1-RC1 cut.) Archive the `shop-metapackage-ce` GitHub repo and update its README to point at `o3-shop/o3-shop`. Its v1.6.0 tag already pins the final pre-archival state; no new tag is needed.
