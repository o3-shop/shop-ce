## 1. Phase 1 — Detector script

- [x] 1.1 Create `bin/find-internal-shim-call-sites.php`: CLI skeleton, accepts `--inventory=<path>`, `--source-root=<path>`, `--output=<path>`, `--help`, `-h`; default inventory `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`, default source root `source/`, default output `openspec/changes/fix-internal-shim-call-sites/findings.json`
- [x] 1.2 Implement `--help` / `-h` handler: print synopsis, options with defaults, one example invocation; exit 0; no side effects; takes precedence over all other flags
- [x] 1.3 Resolve repo root via `git -C __DIR__ rev-parse --show-toplevel`; resolve all default paths against it (caller cwd-independent)
- [x] 1.4 Load + parse the inventory file; build a set of `_method` names (with leading underscore)
- [x] 1.5 Walk every `.php` file under the source root via `RecursiveDirectoryIterator`; for each file, tokenize once with `token_get_all()` and accumulate (a) per-class method declarations and (b) `$this->method(` call sites with their `(file, line, declaring_class, method)`
- [x] 1.6 Filter call sites: keep only those where `method` is the non-underscore counterpart of an inventory `_method` AND the declaring class has both `_method()` and `method()` declared (either directly or via base class within source/)
- [x] 1.7 Sort findings deterministically by `(file, line)`; serialize as pretty-printed JSON with trailing newline; write atomically to `--output`
- [x] 1.8 Smoke-test: invoke from `/tmp` with explicit arguments; with `--help`; with a missing inventory path. Confirm correct behavior on each.

## 2. Phase 1 — Gate test

- [x] 2.1 Create `tests/Unit/BackwardsCompatibility/InternalShimCallSitesTest.php` (namespace `OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility`) — single test method that invokes the detector logic in-process and asserts the findings array is empty
- [x] 2.2 Detector logic factored into a reusable class so the test does not shell-out (e.g. `OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility\Tooling\InternalShimCallSiteDetector` shared with `bin/find-internal-shim-call-sites.php`)
- [x] 2.3 Test docblock cross-references `InheritanceContractTest`, explains division of labour (structural BC vs. call-site convention), and references issue #107
- [x] 2.4 Wire into the existing PHPUnit config so it runs in the normal `tests/Unit/` suite; accept that CI will be red until Phase 2 lands the same session

## 3. Phase 1 — Findings + verification gate (USER)

- [x] 3.1 Run the detector against `b-1.6.0`'s current state; commit `openspec/changes/fix-internal-shim-call-sites/findings.json`
- [x] 3.2 **User step:** review findings; sanity-check the count against the known grep numbers (60+52+31 = ~143 in AdminAjax trio)
- [x] 3.3 **User step:** decide and communicate three things to Claude:
  - (a) Sweep scope — full inventory or restricted subset?
  - (b) PHPDoc rewrite scope — only classes touched by Phase 2, or all 225 from PR #104?
  - (c) Batching — one PR or per-directory?

## 4. Phase 2 — Remediation

- [x] 4.1 For each approved finding, apply the D6 single-token rewrite: `$this->method(` → `$this->_method(`. No other edits.
- [x] 4.2 Per file, run `./docker.sh test --fast <test-path>` for the matching test. If the test mocks the public name, update the mock to the underscore name. Track every mock update.
- [x] 4.3 Per directory batch, run `./docker.sh test` (full unit suite) to catch cross-cutting regressions.
- [x] 4.4 If the gate approved a PHPDoc rewrite: apply D7 to each affected shim pair. The three load-bearing clauses on each side must be present.
- [x] 4.5 Re-run the equivalence check `bin/verify-underscore-method-body-equivalence.php --compare-sibling` over the touched classes. Every pair must report `[EQUIVALENT]` (this change does not move bodies).
- [x] 4.6 **User step:** approve each Phase 2 commit (per-file or per-directory batch, per the granularity decided at the gate); on approval, Claude creates the commit.

## 5. Phase 4 — Verification + close-out

- [x] 5.1 `./docker.sh test --fast tests/Unit/BackwardsCompatibility/InternalShimCallSitesTest.php` — must pass (findings empty)
- [x] 5.2 `./docker.sh test --fast tests/Unit/BackwardsCompatibility/InheritanceContractTest.php` — must still pass (no public-surface regression)
- [x] 5.3 `./docker.sh test-all` — full unit suite green
- [x] 5.4 Manual smoke (admin browser, dev shop running): Catalogue → Categories → Articles tab; Master Data → Delivery Sets → Groups; Discount → Categories; Voucher Series → Groups
- [x] 5.5 Open PR off `b-1.6.0`. Body cites Ralf's reopen comment, links PR #104 as precedent, calls out the override-style narrowing explicitly, references #108. **Closes #107.**
- [x] 5.6 Run `openspec validate` on the change directory; fix any structural issues
- [x] 5.7 After PR is merged, archive the change via `/opsx:archive`
