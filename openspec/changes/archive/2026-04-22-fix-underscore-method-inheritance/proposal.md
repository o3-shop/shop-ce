## Why

Commit `e4e180cc1ba531ea6d2dd8f8d405a8addabb5d92` ("Created replacement functions for deprecated functions") renamed many protected `_methodName()` methods to `methodName()` across ~100 files and reintroduced the underscore-prefixed name as a thin deprecated shim. The shim delegates the **wrong direction**: the old `_method()` calls the new `method()`. This silently breaks every subclass that overrides `_method()` — calls routed through the parent's internal code path now land on the new name, skipping the child override entirely. The same broken pattern could be applied in earlier or later commits to any method that was originally `_`-prefixed, so detection must be anchored to a historical baseline where the correct inheritance behavior was in place (revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`) rather than to the current shape of the code.

This change delivers both detection and remediation, structured as two passes with a mandatory user-verification gate between them.

## What Changes

### Phase 1 — Detection
- Extract the full inventory of protected/public `_methodName()` methods present in revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff` — grouped by fully qualified class name. The inventory is committed to the repo so it stays stable even after refactors shrink the current-day set.
- Add an automated inheritance-contract test that, for every `(ClassName, _methodName)` entry in the pinned inventory, verifies at **runtime** that a subclass override of `_methodName()` is actually invoked when the parent's public code path is exercised. The test uses a synthetic subclass (declared at test time) that overrides `_methodName()` with an observable marker; triggering the relevant code path and observing that the marker fires is the assertion. No static analysis or AST inspection is used here — that approach is deferred to a later refactor.
- Produce a machine-readable findings list enumerating every `(Class, _method)` entry whose inheritance chain is currently broken (i.e., where `_method()` is now a shim that delegates to `method()` instead of owning the implementation).

### Verification gate
- The findings list is reviewed and verified by the user. **Phase 2 does not start until this gate passes.** The gate is an explicit task, not an implicit handoff.

### Phase 2 — Remediation
- For every `(Class, _method)` entry on the user-verified list, invert the delegation in that class so the new `methodName()` delegates to `$this->_methodName(...)`, the implementation body lives on `_methodName()`, and `_methodName()` is marked `@deprecated` with a pointer to the new name. This restores the inheritance chain for subclasses that override the underscore name.
- After each remediation, the inheritance-contract test from Phase 1 must pass for that entry.

## Capabilities

### New Capabilities
- `legacy-method-inheritance-contract`: Defines (1) the rule that every `_method()` present in baseline `ebe86dc0` must, in the current codebase, preserve the virtual dispatch chain so subclass overrides of the underscore name are invoked regardless of which name the caller uses, (2) the reproducible procedure for regenerating the pinned baseline inventory from a given git revision, and (3) the runtime test that enforces the rule across the codebase.

### Modified Capabilities
<!-- None: no existing specs in openspec/specs/. The behavioral spec of individual controllers/models does not change — only the dispatch direction of the shim layer, which is a correctness fix, not a spec change. -->

## Impact

- **Production code (Phase 2):** Every class on the user-verified findings list gets its method-pair delegation inverted. Body moves back to `_methodName()`; `methodName()` becomes a one-line delegate. Subject to the list approved at the verification gate — expected scope is the ~100 files touched by `e4e180cc` plus any earlier/later commits the test flags.
- **Production code (Phase 1):** None.
- **Tests:** New PHPUnit test that loads the pinned baseline inventory and runs a runtime override-and-observe check per entry. Runs in CI.
- **Tooling:** A small generator script (committed) that, given a git revision, emits the inventory file. Used once to produce the `ebe86dc0` baseline and available for future re-anchoring.
- **CI gating:** Between Phase 1 landing and the verification gate closing, the new test will flag known failures. Staging strategy (skipped-with-annotation, snapshot of known failures, merge gate exceptions) is a design decision for `design.md`.
- **Extenders / modules:** Behaviorally neutral for modules that never overrode the underscore methods; restorative for modules that did — they stop being silently bypassed after Phase 2. No public API signature changes.
- **Dependencies:** None new. Uses the existing PHPUnit setup and PHP Reflection.
- **Docs:** A short note in the upgrade/migration section pointing extenders at the restored inheritance behavior, added alongside Phase 2.
