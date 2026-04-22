## 1. Phase 1 — Baseline inventory generator

- [x] 1.1 Create `tests/Unit/BackwardsCompatibility/generate-underscore-method-snapshot.php`: CLI skeleton that accepts `--revision=<sha>`, `--output=<path>`, `--help`, `-h`; default revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`; default output is the canonical inventory path resolved against the repo root
- [x] 1.2 Implement `--help` / `-h` handler: print synopsis, description, options with defaults, one example invocation from outside the repo, pointer to `design.md` D2; exit 0; no git calls, no output written, no `chdir`; takes precedence over all other flags
- [x] 1.3 Resolve repo root via `git -C __DIR__ rev-parse --show-toplevel`; exit non-zero with a clear diagnostic if `__DIR__` is not inside a git work tree
- [x] 1.4 Validate `--revision` exists in the resolved work tree via `git -C <repo-root> rev-parse --verify <revision>^{commit}`; exit non-zero with a diagnostic naming the missing revision and the resolved repo root if validation fails; do not write any output in this case
- [x] 1.5 Interpret `--output` POSIX-style: absolute path used as-is; relative path resolved against the caller's `$cwd`; default resolved against the repo root (`tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`)
- [x] 1.6 Extract a snapshot of the resolved revision with `git archive` into a temp directory; clean the temp directory on exit (success or failure) and do not leave the caller's cwd changed
- [x] 1.7 Walk every `.php` file under `source/` in the snapshot (skip `tests/`, `bin/`, other top-level dirs) and tokenize with PHP's built-in `token_get_all()`; for each class, enumerate declared (not inherited) `protected` and `public` methods whose name begins with `_` but not `__`; emit entries with fields `class` (FQCN under `OxidEsales\EshopCommunity\...`), `method`, `visibility`, `is_static`, `is_abstract`, `baseline_file`
- [x] 1.8 Sort the entry list deterministically (by `class` then `method`); serialize as pretty-printed JSON with a trailing newline; write atomically to the resolved `--output` path
- [x] 1.9 Run the script against the pinned baseline to produce `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`; leave the file in the working tree for user review
- [x] 1.9b **User step:** approve the generated baseline inventory; on approval, Claude stages and commits the file
- [x] 1.10 Smoke-test: invoke the script from `/tmp` with `--revision=<pinned-sha>`, verify output matches the committed inventory byte-for-byte; invoke with `--help` and confirm zero side effects; invoke with a bogus `--revision=deadbeef` and confirm non-zero exit with no output file written

## 2. Phase 1 — Inheritance-contract test

- [x] 2.1 Create `tests/Unit/BackwardsCompatibility/InheritanceContractTest.php` (namespace `OxidEsales\EshopCommunity\Tests\Unit\BackwardsCompatibility`) with a PHPUnit data-provider method that reads `underscore-method-snapshot.json` and yields one case per inventory entry
- [x] 2.2 For each provider case, map the concrete baseline class `OxidEsales\EshopCommunity\...` to its unified-namespace FQCN by replacing the `OxidEsales\EshopCommunity\` prefix with `OxidEsales\Eshop\` — reflection MUST use the unified name, concrete names are never used for reflection
- [x] 2.3 Handle the early-exit branches: class missing (concrete gone or unified unresolvable) → aggregate for D7 `markTestIncomplete` at the end of the run; underscore method no longer present → skip (existing removal tests cover this); underscore method present and no sibling non-underscore method → pass trivially
- [x] 2.4 When both `_method()` and `method()` exist: build a synthetic subclass via `eval()` with a unique class name, extending the unified-name class, overriding `_method()` to set `$this->__overrideCalled = true` and return a safe default of the declared return type; generate the override signature from `ReflectionMethod` on the parent so the eval body stays type-compatible
- [x] 2.5 Instantiate the synthetic subclass via `ReflectionClass::newInstanceWithoutConstructor()` to bypass DI/Registry requirements
- [x] 2.6 Invoke the non-underscore `method()` via `ReflectionMethod::invokeArgs()` with type-defaulted nulls (`[]` for arrays, `0` for ints, `''` for strings, `null` for nullable/mixed); wrap in `try/catch(\Throwable)`
- [x] 2.7 Assertion & finding classification: if `__overrideCalled === true` → pass; if false and no throwable → `observed: "override_not_called"`; if false and a throwable fired before the override → `observed: "exception_before_dispatch"`
- [x] 2.8 Accumulate findings across the data-provider run; emit `openspec/changes/fix-underscore-method-inheritance/findings.json` via an `@afterClass` hook or a dedicated runner under `bin/`, with entries sorted deterministically by `class` then `method`, including both `class` (concrete) and `unified_class` fields
- [x] 2.9 Wire the test into the existing PHPUnit config so it runs in the normal `tests/Unit/` suite (no new suite); accept that CI will be red until Phase 2 fixes land the same session (per D5)

## 3. Verification gate (USER)

- [x] 3.1 **User step:** review `openspec/changes/fix-underscore-method-inheritance/findings.json` produced by the Phase 1 test run
- [x] 3.2 **User step:** approve the findings list for remediation OR annotate out-of-scope entries; communicate any entries to exclude from Phase 2 before Claude proceeds
      - User decision 2026-04-19: **all 226 entries are in-scope** for Phase 2, including the 162 `exception_before_dispatch` cases

## 4. Phase 2 — Remediation

- [x] 4.1 For each approved finding (grouped per file, or per directory per the open question — default per-directory), apply the D6 transform: body moves from `method()` to `_method()` with `_method()`'s signature verbatim from baseline; `method()` becomes `return $this->_method(...$args);`; no native types added to either method
- [x] 4.2 On each remediated `method()` PHPDoc, add the delegation hint directing downstream override authors to call `parent::method()` (not `_method()`) when not fully replacing the behavior; include the `o3-shop/o3-shop#108` reference
- [x] 4.3 On each remediated `_method()` PHPDoc, ensure an `@deprecated` tag is present whose message names `method()` as the successor AND advises new code (including new modules) not to call or override `_method()`; preserve an existing valid block, update only if missing the new-code clause, add one if absent
- [x] 4.4 After each remediation edit, run `InheritanceContractTest` against the full inventory; confirm the affected entries flipped from failing to passing and no previously-passing entry regressed
- [x] 4.5 **Equivalence check per batch** (after the batch's commits): run `bin/verify-underscore-method-body-equivalence.php --compare-sibling --revision=<pre-remediation-sha>` over the batch's (file, `_method`) pairs. Every remediated pair must report `[EQUIVALENT]`; `[NOT_FOUND_IN_BASELINE]` is acceptable for pairs without a simple `_x`/`x` sibling. Any `[BODY_DIVERGED]` blocks moving to the next batch.
- [x] 4.6 **Targeted tests per batch** (after the batch's commits): for each file in the batch, run the existing unit + integration tests that cover it (`./docker.sh test --fast <test-path>`), file by file. For any failure, compare against pre-remediation (`git stash; git checkout <pre-remediation-sha> -- <file>; rerun`); separate new regressions from pre-existing failures. Report the result matrix. New regressions block the next batch; pre-existing failures are noted and carried forward.
- [x] 4.7 **Full-suite security layer** (after the batch's commits): temporarily rename `tests/Unit/BackwardsCompatibility/InheritanceContractTest.php` to `.disabled` (so PHPUnit skips it), run `./docker.sh test`, confirm the suite is green, then restore the name. Catches cross-cutting regressions the per-file tests miss (tests that mock our classes, integration tests, class-load-order effects). Any new failure blocks the next batch.
- [x] 4.8 **User step:** approve each Phase 2 commit (per-file or per-directory batch, per the granularity decided at the gate); on approval, Claude creates the commit — no commit happens without this approval
- [x] 4.9 When the inheritance-contract test is green on the full inventory locally, **ask the user for explicit approval to push**; on approval, Claude runs `git push` (to the existing tracked branch — no new branch creation) and verifies CI recovers

## 5. Documentation & close-out

- [x] 5.1 Draft a short cross-link comment for o3-shop/o3-shop#107 (noting the PR(s) implementing this change and referencing #108 for deferred type-tightening); **ask the user for approval of the comment text and of posting it**; on approval, Claude posts via `gh issue comment`
      - User decision 2026-04-22: skip a separate issue comment; the PR body already contains `Closes #107` and links to #108, which GitHub auto-cross-references on both issues.
- [x] 5.2 Verify o3-shop/o3-shop#108 body still accurately describes the deferred work (types on `method()` counterparts) and does not promise anything this change already did
      - Verified 2026-04-22: #108 body is accurate. Deferred work (native type declarations on `method()` counterparts) was not done here; `_method()` signatures remain verbatim. One minor path-reference drift in point 5 (mentions `tests/Unit/Core/LegacyMethodInheritanceData/baseline_underscore_methods.json`, actual artifact lives at `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`) — implementation-detail only, whoever picks up #108 will rediscover.
- [x] 5.3 Run `openspec validate` on the change directory; fix any structural issues
- [x] 5.4 Archive the change via `/opsx:archive` once CI is fully green on the b-1.5 branch
