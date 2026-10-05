## Why

PR [#104](https://github.com/o3-shop/shop-ce/pull/104) (commit `45e71ae`) closed the **structural** half of issue [#107](https://github.com/o3-shop/o3-shop/issues/107): for every `(Class, _method)` entry from baseline `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`, the implementation body was moved back to `_method()` and `method()` was rewritten as a one-line delegate `return $this->_method(...)`. The runtime `InheritanceContractTest` proves that calling the public `method()` correctly dispatches to a subclass override of `_method()`; `findings.json` for that change is empty.

PR #104 also restored a small set of internal call sites in the parent class `ListComponentAjax` (notably `processRequest` line 257: `$sQAdd = $this->_getQuery();`). It missed the **subclasses**. A direct grep across `source/` finds 60+ `$this->getQuery(`, 52+ `$this->addFilter(`, 31+ `$this->getAll(` call sites — all in `ListComponentAjax` descendants, all inherited from `e4e180cc`'s rename, none restored. Ralf's reopen comment on #107 flags this:

> We found a few more remaining pieces: `$this->getQuery()` is called internally on still quite some places — even after this fix. Needs to be reverted, too.

The same shape of drift almost certainly affects other entries in the 677-row baseline inventory. This change finishes the baseline restoration in the subclasses and adds an automated guard that prevents the same drift from recurring.

This is **not a correctness fix** — `InheritanceContractTest` already proves the public-surface dispatch contract holds. Both `$this->method()` and `$this->_method()` reach a module's `_method()` override via virtual dispatch (the public-name path takes one extra hop through the delegate). It is a **convention sweep** that pins `_method()` (the BC anchor) as the canonical override target during the transition period before #108's template-method refactor.

## Trade-off this commits to

Internal calls on the BC anchor narrow dispatch to `_method()` overrides only. Module overrides of the new public `method()` are bypassed by any internal path that has been swept. This is a deliberate design choice — the codebase commits to BC anchor (`_method()`) as the canonical override target during the transition. The current PHPDocs say the opposite (`@deprecated … new code MUST NOT call or override _method()`). They MUST be updated to flag this as transitional and point at #108 as the long-term inversion target. Without that framing, module authors reading "MUST override `_method()`" would commit to a name slated for removal and migrate twice.

## What Changes

### Phase 1 — Detection
- Add a tokenizer-based detector script `bin/find-internal-shim-call-sites.php` that, given the existing pinned inventory at `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`, walks every `.php` under `source/`, finds each `$this->method(` where `method` is the non-underscore counterpart of an inventory `_method` entry **and** the class declaring the call site still has both `_method()` and `method()` defined. Emits deterministic JSON `{file, line, class, method}`.
- Add a PHPUnit test `tests/Unit/BackwardsCompatibility/InternalShimCallSitesTest.php` that runs the detector logic and asserts the findings list is empty. Mirrors `InheritanceContractTest`'s shape so it slots into the existing CI gate. Docblock cross-references `InheritanceContractTest` and explains the division of labour: structural BC vs. call-site convention.
- Produce `openspec/changes/fix-internal-shim-call-sites/findings.json` with the current set of violations.

### Verification gate
- The findings list is reviewed and signed off before any production code is touched. Phase 2 does not start until this gate passes. Three decisions to record:
  1. Sweep scope: full inventory or AdminAjax trio first?
  2. PHPDoc rewrite scope: every shim pair touched in Phase 2, or all 225 from PR #104?
  3. Batching: one PR or per-directory?

### Phase 2 — Remediation
- For every approved finding, rewrite `$this->method(` → `$this->_method(`. Token-aware, single-token substitution, no reformat.
- Update PHPDoc on each affected shim pair (per gate decision): `@deprecated`/`@internal` blocks rewritten to flag the transitional state and reference #108 as the long-term inversion target.
- Update tests that mock the public name to mock the underscore name (same pattern `c4eb488` applied to `AjaxListComponentTest`).
- After each batch, run targeted unit tests then the full unit suite to catch cross-cutting regressions.

## Capabilities

### Modified Capabilities

- `legacy-method-inheritance-contract`: extend with a **call-site-form requirement** for parent classes that hold a BC-shim pair. The structural dispatch contract (Phase 1 of `fix-underscore-method-inheritance`) remains unchanged; this change adds the convention layer that pins which name internal code SHOULD use.

### New Capabilities
<!-- None: the convention layer extends an existing capability. -->

## Impact

- **Production code (Phase 2):** Every approved call-site rewrite is a single-token substitution. PHPDoc rewrites are localised to shim-pair PHPDoc blocks; no signature changes.
- **Production code (Phase 1):** None.
- **Tests:** New PHPUnit test that runs the detector against the current source tree. Plus mock-string updates in tests that mock the public name of a swept method.
- **Tooling:** `bin/find-internal-shim-call-sites.php` (new). Lives alongside the existing `bin/verify-underscore-method-body-equivalence.php`.
- **CI gating:** Between Phase 1 landing and the verification gate closing, the new test will be red. Either skipped-with-annotation until Phase 2 lands, or both phases land in the same PR — gate decision.
- **Extenders / modules:** Behaviorally neutral for modules that override `_method()`. Module overrides of the new public `method()` lose dispatch on swept call paths — by design, per the trade-off above.
- **Dependencies:** None new. Reuses the existing PHPUnit setup, PHP Reflection, and the pinned baseline inventory.
- **Docs:** PHPDoc on every swept shim pair gets the transitional-framing rewrite. A short note in the upgrade/migration section pointing extenders at the call-site rule, added alongside Phase 2.
