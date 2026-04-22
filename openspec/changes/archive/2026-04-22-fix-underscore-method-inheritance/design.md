## Context

The bug (see `proposal.md`) is that deprecated BC shims introduced in `e4e180cc` delegate in the wrong direction: the old `_method()` calls the new `method()`, so subclass overrides of `_method()` are silently bypassed. The fix is mechanically simple per class but must be applied across ~100 files, and we need a durable guarantee that the same pattern does not recur.

Relevant facts about the codebase:
- PHP 7.4+ / 8.0, PSR-4 autoload under `OxidEsales\EshopCommunity\` → `./source`; tests under `OxidEsales\EshopCommunity\Tests\` → `./tests`.
- PHPUnit is configured (`tests/phpunit.xml`, `tests/bootstrap.php`). `phpspec/prophecy-phpunit` is available. No AST parser (e.g. `nikic/php-parser`) is in deps; PHP's built-in tokenizer is.
- The shop exposes a "virtual" namespace layer via `o3-shop/shop-unified-namespace-generator`. The source-file inventory references the concrete `OxidEsales\EshopCommunity\...` names (that is what the baseline files declare via `namespace`). The runtime test **must** resolve each entry through the unified namespace `OxidEsales\Eshop\...` — not the concrete `EshopCommunity` name — so the class chain assembled by the module system participates in dispatch. A module that inserts its own subclass between the shop core and the synthetic test subclass could itself trip the same dispatch bug, and only reflection against the unified namespace catches that.
- Baseline revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff` is the last state before the broken-shim pattern started.

Stakeholders: core devs maintaining b-1.5 and future releases; module authors who extend the shop via `_method()` overrides (the primary victims of the regression).

## Goals / Non-Goals

**Goals:**
- Produce a committed, reproducible inventory of every protected/public `_methodName()` method present in the baseline revision, keyed by fully qualified class name.
- Ship a PHPUnit test that, for each inventory entry, verifies at runtime that a subclass override of `_method()` is dispatched when the shim's public-name entry point is invoked.
- Produce a machine-readable findings list (JSON) enumerating classes whose inheritance chain is currently broken. The list is the hand-off artifact for the verification gate.
- After the verification gate closes, remediate every listed class by inverting the delegation, such that the test passes cleanly on the full inventory.

**Timing assumption:** Phase 1 and Phase 2 land in the same work session. That is what lets this design avoid CI-gating scaffolding (see D5).

**Non-Goals:**
- AST-based verification of delegation direction by inspecting method bodies. Deferred to a later refactor — we prefer runtime behavioral evidence here.
- Rewriting call sites that reference the new (non-underscore) name. Those keep working.
- Removing the deprecated `_method()` shims. They stay, correctly wired, until a future major.
- Changing public API signatures or adding new deprecation notices beyond what the shim already declares.
- Detecting a different but related anti-pattern: methods that were entirely removed without a shim. The existing "missing method" test already covers that; not in scope here.

## Decisions

### D1 — Inventory source and format

**Decision:** Extract the baseline inventory via PHP's built-in tokenizer (`token_get_all`) running over a `git archive`-exported snapshot of revision `ebe86dc0`. Restrict enumeration to `.php` files under `source/` — classes under `tests/`, `bin/`, and other top-level directories are not part of the shop's module BC surface and are excluded. Magic methods (names starting with `__`) are also excluded. Store the result as JSON at `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`.

**Schema (one line per method):**
```json
{
  "class": "OxidEsales\\EshopCommunity\\Application\\Controller\\Admin\\AdminListController",
  "method": "_prepareWhereQuery",
  "visibility": "protected",
  "is_static": false,
  "is_abstract": false,
  "baseline_file": "source/Application/Controller/Admin/AdminListController.php"
}
```

**Rationale:** The tokenizer is in PHP core — no new dependency. It is accurate enough for our needs (name, visibility, class). A regex would miss edge cases (methods inside heredocs, methods inside traits declared inline, visibility modifiers in unusual order). Checking the inventory into git means re-running the extractor in CI is not required; it is a one-off deliverable of the change. Re-running remains possible through a committed script.

**Alternatives considered:**
- `nikic/php-parser` as a dev dependency: more ergonomic but unnecessary weight for a one-shot extraction.
- Grep/regex: simpler but brittle. Rejected.
- Not checking in the inventory and regenerating at test time: adds git-archive complexity to every CI run; no upside.

### D2 — Inventory generator script

**Decision:** Commit a one-off CLI script `tests/Unit/BackwardsCompatibility/generate-underscore-method-snapshot.php` that accepts `--revision=<sha>` and `--output=<path>`. It internally calls `git archive` into a temp dir, walks the tree, tokenizes each `.php` file, filters for protected/public methods whose name starts with `_`, emits the JSON.

**Invocation independence (MUST):** The script must be invokable from any working directory — from the repo root, from a subdirectory, or from a path outside the repo entirely. Concretely:
- The script resolves the repo root itself by running `git -C __DIR__ rev-parse --show-toplevel` (not by trusting `$cwd`). All `git archive` and file-walk operations are run against that resolved path.
- A relative `--output=<path>` is interpreted relative to the caller's `$cwd`, matching POSIX tool convention. An absolute `--output=<path>` is used as-is. If `--output` is omitted, the default is the canonical inventory path inside the repo (`tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`, resolved against the repo root, not `$cwd`).
- `--revision` accepts any `git` revision spec (SHA, tag, branch name). If omitted, the default is the pinned baseline `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`.
- The script accepts `--help` and `-h`. When either is passed, the script prints a usage summary (synopsis line; description; list of options with their defaults; one short example of invocation from outside the repo; pointer to the design doc) to stdout and exits with status 0. No side effects: no `git` calls, no output file written, no cwd change. `--help` takes precedence over any other flag on the same command line.
- The script must not `chdir()` into the repo root and leave it there on exit; if it changes directory internally it restores the original `$cwd` before returning so that error/log messages printed after the chdir still reference the caller's expected paths.
- The script must emit a clear error and exit non-zero (rather than silently misbehaving) in either of these cases:
  - `__DIR__` is not inside a git work tree — the only way this script makes sense.
  - The resolved `--revision` does not exist in the work tree's git history (verified via `git -C <repo-root> rev-parse --verify <revision>^{commit}`). This catches the common mistake of running the script from the wrong checkout — for example, from a sibling repo that happens to contain a `tests/Unit/BackwardsCompatibility/generate-underscore-method-snapshot.php` but whose history does not include the pinned baseline SHA — and prevents silently producing an inventory against an unintended revision.

**Rationale:** Reproducibility plus ergonomics. Someone regenerating the inventory from `vendor/` after a composer install, from a CI runner with an arbitrary working directory, or from a wrapper script should not have to remember to `cd` first. Naming the script `bin/...` matches the existing `bin/oe-console` convention. Runtime: seconds.

### D3 — Test mechanics: runtime override-and-observe

**Decision:** A single data-provider-driven PHPUnit test `InheritanceContractTest::testUnderscoreShimPreservesOverride(string $class, string $underscoreMethod)`. The data provider reads `underscore-method-snapshot.json` and yields one case per entry.

**Per-case procedure:**
1. Map the baseline `OxidEsales\EshopCommunity\...` class name to its unified-namespace FQCN by replacing the `OxidEsales\EshopCommunity\` prefix with `OxidEsales\Eshop\`. For the running example, `OxidEsales\EshopCommunity\Application\Controller\Admin\AdminListController` → `OxidEsales\Eshop\Application\Controller\Admin\AdminListController`. All subsequent reflection **must** use the unified name, not the concrete `EshopCommunity` name, so the module class chain participates in dispatch. If the unified name is unresolvable (class absent from the virtual namespace map), the case is handled per D7.
2. Skip if the underscore method is not present on the class today and never had a `methodName()` counterpart added — this is the "no shim at all" case; existing tests cover removals.
3. If the underscore method is present and **no** non-underscore counterpart exists, pass (shape is unchanged from baseline).
4. Otherwise, both `_method()` and `method()` exist. Build a synthetic subclass **at test time** (`eval()` of a PSR-compatible class body, with a unique name) that extends the unified-name class, overrides `_method()` to set `$this->__overrideCalled = true` and return a safe default, and defines nothing else.
5. Instantiate the subclass without calling its constructor (`ReflectionClass::newInstanceWithoutConstructor()`), to avoid dependencies on DI/Registry state.
6. Invoke the non-underscore `method()` via `ReflectionMethod::invokeArgs()`, passing `null` for each required parameter (or type-appropriate defaults: `[]` for array, `0` for int, `''` for string, `null` for nullable/mixed). Wrap in try/catch for `\Throwable`.
7. **Assertion:** `$subclass->__overrideCalled === true`. If true, the `method()` shim correctly delegated to `$this->_method()` and the override was dispatched — through whatever chain of module subclasses the unified namespace resolved. If false (including when a `\Throwable` bubbled from the real implementation path), the dispatch was wrong and this entry is a finding.

**Rationale:** The regression we care about is a dispatch fact, not a body-structure fact. Observing dispatch at runtime is both the most accurate signal and the cheapest thing to maintain — if someone finds a cleverer way to structure the shim (e.g. via a trait) the test still passes as long as the override fires. `newInstanceWithoutConstructor()` avoids triggering framework bootstrapping per test case; `eval()` of a short class body is safe because class names and bodies are synthesized from inventory data we control. `invokeArgs` with type-defaulted nulls is a known ugly trick, but the goal is only to traverse the shim far enough to confirm `$this->_method()` is called — we don't care whether the real implementation succeeds.

**Alternatives considered:**
- Tokenizer/AST inspection of the shim body to assert `return $this->_method(...)`. Clearer failure messages but ignores real dispatch semantics (e.g. a trait could satisfy the contract). Deferred per non-goal.
- Mocking via Prophecy or PHPUnit's `createPartialMock`. Would work but is clunkier than a synthetic subclass for this single-override case.
- Requiring per-class hand-written fixtures. Does not scale to ~100 entries; defeats the point.

### D4 — Findings list format

**Decision:** The test writes a machine-readable findings list to `openspec/changes/fix-underscore-method-inheritance/findings.json` at the end of its run (via a `@afterClass` hook or a dedicated runner in `bin/`). The list contains one entry per failure:

```json
{
  "class": "OxidEsales\\EshopCommunity\\Application\\Controller\\Admin\\AdminListController",
  "unified_class": "OxidEsales\\Eshop\\Application\\Controller\\Admin\\AdminListController",
  "method": "_prepareWhereQuery",
  "sibling_method": "prepareWhereQuery",
  "current_file": "source/Application/Controller/Admin/AdminListController.php",
  "observed": "override_not_called",
  "notes": "dispatch routed to prepareWhereQuery() without hitting _prepareWhereQuery() override"
}
```

Both the baseline `class` (concrete `OxidEsales\EshopCommunity\...`) and the `unified_class` are recorded so reviewers can correlate the source-file site of the fix with the runtime-effective class the test exercised.

**Rationale:** Writing the list from the test itself means the list is always consistent with what the test saw. The location inside the openspec change directory keeps it bundled with the change's paper trail and gets it reviewed alongside design/tasks. JSON lets Phase 2 tooling (or a human) iterate the list.

### D5 — CI during Phase 1

**Decision:** No known-failures baseline, no skip-list, no split CI job. The inheritance-contract test fails hard on the current codebase; CI is expected to be red from the moment Phase 1 lands until the final Phase 2 commit lands on the same day. Remediation commits progressively turn the test green as entries are fixed.

**Rationale:** Phase 1 and Phase 2 are landing in the same work session, so any baseline/skip-list would exist for a few hours before being deleted. The scaffolding would outlive its usefulness only if the two phases separated in time — they won't, so the cost (maintenance, drift risk, extra code path in the test) is not worth the short-lived green.

**Trade-off accepted:** During the Phase-1-to-Phase-2 window CI cannot distinguish a newly introduced regression from an expected failure. The window is short enough (hours, not days) that this is acceptable.

**Alternatives considered:**
- PHPStan/Psalm-style known-failures baseline. Rejected on complexity grounds given the same-day timing.
- Mark the whole test `@group known-broken` and run in a non-blocking CI job. Rejected: same reason.

**Re-evaluate if:** The timing assumption breaks (Phase 2 slips beyond the same work session). At that point, reopen this decision and add a baseline.

### D6 — Phase 2 remediation mechanics

**Decision:** Claude Code applies the remediation interactively in the same work session as Phase 1, after the user approves the findings list. There is no hand-editing by the user and no blanket codemod. For each entry on the verified list Claude performs the same mechanical transform per class:
1. Move the implementation body from `method()` back to `_method()`. Keep `_method()`'s signature **verbatim** — parameter types, defaults, variadics, return type, and PHPDoc unchanged. This is the BC surface modules rely on and must not shift.
2. Reduce `method()` to a one-line delegate: `return $this->_method(...$args);` (or `$this->_method(...);` without `return` if `_method()` is `void`). Parameter signature of `method()` is preserved verbatim — no type tightening in this change.
3. Add or update a PHPDoc hint on `method()` directing downstream override authors to preserve the class chain. Canonical text:

   ```php
   /**
    * @internal If your override does not fully replace the behavior, call
    *           parent::<methodName>() (not the deprecated _<methodName>())
    *           so downstream overrides in the class chain are preserved.
    *           Template-method refactor tracked in o3-shop/o3-shop#108.
    */
   ```

   Adjust phrasing to match existing PHPDoc conventions in the file, but the two load-bearing clauses — "call `parent::method()`, not `_method()`" and the issue reference — must be present.
4. Ensure `_method()`'s PHPDoc contains a clear `@deprecated` tag whose message names `method()` as the replacement and tells authors of new code (including new modules) to use `method()` instead of `_method()`. If the current shim already has such a block, leave it in place, updating only the message if it does not already mention `method()` by name. If the block is missing (e.g. the shim lost it during `e4e180cc`), add one. Canonical text:

   ```php
   /**
    * @deprecated Use <methodName>() instead. This underscore-prefixed name
    *             is retained only for backward compatibility with module
    *             subclasses that already override it; new code, including
    *             new modules, MUST NOT call or override _<methodName>().
    */
   ```

   As with the `method()` hint, phrasing may be adjusted to match local PHPDoc style, but three load-bearing clauses must be present: (a) the `@deprecated` tag, (b) a direct reference to `method()` as the successor, and (c) the advice that new code should not call or override `_method()`.
5. Run the inheritance-contract test; confirm the entry now passes.

Commits are grouped per file (all entries for one class in one commit) or per directory (see open question on granularity).

**Mid-chain override discipline (nuance that is NOT fully fixed by this change).** The inversion restores the baseline single-subclass contract: a subclass that overrides `_method()` sees its override fire for any caller entering through either name. It does **not** guarantee correctness for chains of overrides where a middle link uses the modern name terminally. Concrete failure scenario:

- Parent (core) after inversion: `method()` is a delegate to `_method()`; `_method()` holds the real implementation.
- Module A (middle, modern): overrides `method()` with a terminal body that does not call `parent::method()` or `$this->_method()`.
- Module B (grandchild, old): overrides `_method()`.
- Any parent-originated call path reaches `$this->method()`, dispatches to Module A's terminal override, and **never reaches Module B's `_method()` override**. Module B is effectively bypassed for those call paths.

The PHPDoc hint added at step 3 is the mitigation: it tells Module A's author to call `parent::method()` so the parent's delegate can route to `$this->_method()` and Module B's override fires. It is advisory — PHP does not enforce it, and an existing Module A that predates the hint will still break downstream modules. The structural fix (making `method()` `final` and moving all override authority onto `_method()`, or the template-method refactor) is deliberately out of scope here and handed off to [o3-shop/o3-shop#108](https://github.com/o3-shop/o3-shop/issues/108).

Dispatch matrix assuming single subclass, post-inversion:

| Caller uses | Subclass overrides | Outcome |
|---|---|---|
| `method()` | `_method()` | delegate → `$this->_method()` → **override fires** |
| `_method()` | `_method()` | direct dispatch → **override fires** |
| `method()` | `method()` | direct dispatch → **override fires** |
| `_method()` | `method()` | parent `_method()` runs directly → **override bypassed** |

Row 4 is the asymmetry that also seeds the multi-level-chain failure above. Row 4 is acceptable because `method()`-only overrides are (a) a small, recent population and (b) were already being bypassed by underscore-name callers in the broken state that motivated this change.

**Native type declarations on `method()` are explicitly out of scope.** Although `method()` is being rewritten and typing it from the existing PHPDoc would be a natural co-benefit, adding native types is an LSP-breaking change for any consumer that already overrides the new `method()` name (introduced by `e4e180cc` in October 2024 — old enough for modules to have adopted it). This change ships in the `b-1.5` minor line, so signature tightening is deferred to the next major release. Tracked in [o3-shop/o3-shop#108](https://github.com/o3-shop/o3-shop/issues/108).

**No codemod / no rector rule.** Rationale: PHPDoc blocks, `phpcs:ignore` markers, parameter defaults, and inline comments vary per file. Automated rewriting risks mangling these. Claude editing each entry with the `Edit` tool preserves the surrounding text exactly; ~100 small, review-friendly diffs are the natural output.

**Alternatives considered:**
- `rector` rule to swap delegation direction. Possible in principle but the time to write and validate the rule exceeds the time for Claude to apply the edits directly, and the blast radius of a buggy rector rule across 100 files is larger than the blast radius of 100 individual `Edit` operations.
- Handing the list to the user for hand-editing. Rejected: the user has explicitly delegated the mechanical edit work; their hands-on involvement is the verification gate, not the code edits.

### D7 — Classes that no longer exist in the current tree

**Decision:** "Missing" means either the concrete `OxidEsales\EshopCommunity\...` class is gone **or** the unified namespace cannot resolve it. The test aggregates both into a single `markTestIncomplete()` at the end of the run with the list, rather than per-case failures. A separate tracking issue (or an entry in the follow-up) can decide whether each removal was intentional. Not blocking Phase 2.

**Rationale:** The existing "missing method" test already guards against accidental removals. Duplicating that here adds noise.

### D8 — Virtual namespace handling

**Decision:** Inventory generation reads baseline source files and therefore records concrete `OxidEsales\EshopCommunity\...` names (that is what the source files declare). The runtime test **must** resolve each entry through the unified virtual namespace `OxidEsales\Eshop\...` (generated by `o3-shop/shop-unified-namespace-generator`) before reflecting. Reflecting against the concrete `OxidEsales\EshopCommunity\...` class is not an acceptable alternative and is explicitly rejected. Both names are kept side by side in the findings list for auditability.

**Rationale:** Reflecting against the unified name is "double secure" — it catches not only the core-class regression but also any module in the class chain that reintroduces the same broken shim pattern. Reflecting against the concrete name only measures the base shop, misses module overrides entirely, and therefore cannot satisfy the goal of this change. Anchoring the inventory to concrete names keeps the extractor simple (source files declare concrete names) and preserves the baseline pin.

## Risks / Trade-offs

- **Constructor-less instantiation may miss subtle dispatch bugs.** `newInstanceWithoutConstructor()` bypasses ctor side effects. If a class's `_method()` only behaves correctly after ctor state is established, the synthetic override still fires (we only care whether it fires), so this is safe for the specific dispatch question — but a broader correctness test would need full construction. **Mitigation:** We are explicit in the test comment that this is a *dispatch* assertion, not a behavior assertion.
- **`invokeArgs` with synthetic arguments may throw before reaching the override call.** If `method()` performs argument validation that throws before delegating to `_method()`, the override never fires and we'd mis-report a dispatch failure. **Mitigation:** When the test catches a `\Throwable` before `__overrideCalled` is set, it records `observed: "exception_before_dispatch"` rather than `observed: "override_not_called"`. Phase 2 reviewer distinguishes the two; a `exception_before_dispatch` entry may need a per-class fixture rather than a code fix.
- **`eval()` usage has a stigma.** Inputs are limited to class names we parse from PHP source we control (the baseline inventory). **Mitigation:** Generate the synthetic class string from a template with class name and method signature only; never splice user input.
- **Red CI window.** Between Phase 1 landing and the final Phase 2 commit, the inheritance-contract test is red. A genuinely new regression introduced by an unrelated commit in the same window cannot be distinguished from expected failures. **Mitigation:** The window is a single work session by design (see D5 and the timing assumption in Goals). If that assumption breaks, reopen D5 and add a baseline.
- **New `_method()` introductions escape the inventory.** The baseline is pinned to `ebe86dc0`, so a truly new `_method()` added after that date is not in scope. **Mitigation:** Out of scope for this change; a later refactor can extend to cover the current tree. This is an accepted trade-off: pinning to the baseline is what makes the test stable.

## Migration Plan

1. **Claude applies Phase 1**: generator script, baseline inventory JSON, inheritance-contract test, `findings.json` — landed as one or a small number of commits. CI goes red on the new test.
2. **User step — verification gate:** the user reviews `findings.json` and signals go / no-go for Phase 2. Any out-of-scope entries are noted in the approval so Claude excludes them from the Phase 2 edits.
3. **Claude applies Phase 2** in the same work session: each commit swaps delegation for one class (or one directory; see open questions). CI progressively recovers as entries are fixed.
4. When the inheritance-contract test is green on the full inventory, Phase 2 is complete. Archive the change.

**Rollback:** Phase 1 is pure additions (new test, new data files, new script). Reverting those commits is clean. Phase 2 reverts are per-commit — each remediation lives in its own commit so it reverts atomically without touching neighbors.

## Open Questions

- **Phase 2 commit granularity.** One commit per class vs. one commit per directory (Component, Controller/Admin, Model, …). Per-class is safer for reverts; per-directory is less noisy in the log. Default to per-directory unless a particular class has conflicts during remediation. Confirm with user at gate time.
- **What to do with `exception_before_dispatch` entries.** Are they considered broken (argument validation is itself subclass-overridable behavior) or tolerable? Design leans toward "tolerable, annotate as such" to keep Phase 2 scope tight, but deferring the call.
