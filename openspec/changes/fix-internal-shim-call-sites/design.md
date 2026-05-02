## Context

The bug (see `proposal.md`) is residual call-site drift left over from `e4e180cc`'s rename. PR #104 fixed the structural shim direction across ~225 entries and restored a small set of internal call sites in `ListComponentAjax` (the parent class). The subclasses were not swept. `InheritanceContractTest` is silent on this because its assertion is structural — it calls `method()` on a synthetic subclass overriding `_method()` and confirms the override fires; the test doesn't audit which name internal code uses to reach the override.

Relevant facts about the codebase:
- PHP 7.4+ / 8.0, PSR-4 autoload under `OxidEsales\EshopCommunity\` → `./source`; tests under `OxidEsales\EshopCommunity\Tests\` → `./tests`.
- The pinned baseline inventory at `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json` is the authoritative list of `_method()` names. It is the input to this change's detector.
- `bin/verify-underscore-method-body-equivalence.php` is the existing tokenizer-based sibling tool; the new detector mirrors its idioms.
- The shop's "virtual" namespace layer is irrelevant for this change — the detector operates on source files lexically, not at runtime through the class loader.

Stakeholders: same as PR #104. Core devs maintaining `b-1.6.0`; module authors writing `_method()` overrides (now the only override style honored on swept call paths).

## Goals / Non-Goals

**Goals:**
- Produce a deterministic, machine-readable list of every `$this->method(` call site in `source/` where `method` is the non-underscore counterpart of an inventory `_method` and the declaring class still has both names defined.
- Ship a PHPUnit test that runs that detector and asserts the list is empty.
- After the verification gate closes, remediate every listed call site by single-token rewrite to `$this->_method(`. Update affected test mocks. Update affected shim-pair PHPDocs to flag the transitional framing.

**Timing assumption:** Phase 1 and Phase 2 land in the same work session, same as PR #104.

**Non-Goals:**
- Detecting `parent::method(` call sites. Those are intentional — `parent::method()` is the documented way for subclass overrides to call up the chain (see the existing `@internal` PHPDoc).
- Detecting `static::method(` or `self::method(`. Out of scope for this pass; can be a follow-up if drift surfaces.
- Detecting calls outside `source/` (tests, bin, vendor). Inventory scope is `source/` only by precedent.
- The structural template-method refactor that resolves the BC-anchor / new-name tension. That is #108.
- Removing `_method()` shims or renaming the canonical override target. Both stay during the transition.

## Decisions

### D1 — Detector input is the pinned baseline inventory

The detector reads `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json` and treats every entry's `method` (with its leading underscore) as an in-scope name. The non-underscore counterpart is the search target. Rationale: the inventory is already the single source of truth for "which `_method()` names matter" and is reused by `InheritanceContractTest` and `verify-underscore-method-body-equivalence.php`. Reusing it keeps a consistent boundary across the three tools.

### D2 — Detector matches `$this->method(` calls only

Matched token sequence (using PHP's built-in tokenizer):
```
T_VARIABLE("$this") T_OBJECT_OPERATOR("->") T_STRING("method") "("
```
Only `$this->method(` is matched. `parent::method(`, `static::method(`, `self::method(`, and `$other->method(` are all out of scope. The detector reports `(file, line, declaring_class, method)` per match.

Rationale: the convention being enforced is "internal calls go direct to the BC anchor." `parent::method()` is intentional (the `@internal` hint says so). Other forms are either obviously wrong (cross-instance method calls on shimmed methods are extremely rare and probably not relevant) or already covered by the structural test.

### D3 — Pair-presence check before reporting

A `$this->method(` call is reported only if the class declaring the call site has, in the **current** source tree, both `_method()` and `method()` defined (either directly or via inheritance from a class also in scope of the BC-shim layer). Rationale: if the class never had the shim pair (e.g. the inventory's `method` was removed entirely, or the class was added post-baseline and uses a non-shim method), `$this->method(` is just a normal method call and not a convention violation.

Implementation: build a class-method map from a single tokenizer pass over `source/`, then filter the call-site matches against it. Single pass for performance and determinism.

### D4 — Findings file shape

```json
[
  {
    "file": "source/Application/Controller/Admin/CategoryMainAjax.php",
    "line": 190,
    "class": "OxidEsales\\EshopCommunity\\Application\\Controller\\Admin\\CategoryMainAjax",
    "method": "getQuery"
  },
  …
]
```

Sorted deterministically by `(file, line)`. Path is repo-relative. The `class` is the FQCN at the call site (concrete `EshopCommunity\` namespace; the unified namespace is irrelevant for this lexical analysis). The `method` is the non-underscore form (the actual call as written).

Output written to `openspec/changes/fix-internal-shim-call-sites/findings.json`. Same convention as the parent change.

### D5 — Test runs the detector in-process; CI red until Phase 2

`InternalShimCallSitesTest` instantiates the detector class and asserts the returned list is empty. No shell-out. Rationale: same as `InheritanceContractTest` — keeps the test self-contained and fast.

Between Phase 1 landing and Phase 2 closing, the new test will be red. Acceptable because Phases 1 and 2 land in the same session.

### D6 — Remediation transform

For each approved finding `(file, line, class, method)`:
1. In `file`, find the **single** `$this->method(` token sequence on `line` (the detector pinpoints exactly one).
2. Replace `T_STRING("method")` with `T_STRING("_method")`. Single token substitution.
3. No reformat. No re-indentation. No changes to surrounding code.

This preserves diff hygiene — every line in the diff is a single-token change, easy to review.

### D7 — PHPDoc rewrite (gate-conditional)

If the gate approves the PHPDoc rewrite, every shim pair touched in Phase 2 (or every shim pair from PR #104, depending on the gate decision) gets:
- `_method()` PHPDoc: `@deprecated` block rewritten to flag transitional state, name `method()` as the eventual successor (per #108), advise modules to override `_method()` *for now*, with a "plan extension work with both stages in mind" note.
- `method()` PHPDoc: `@internal` block rewritten to clarify it's the public delegate during the transition, that internal call paths bypass it after this sweep, and that #108 will eventually invert this.

The three load-bearing clauses on each must be preserved across PHPDoc-style variations: transitional framing, `_method()` is the safe override target now, `method()` is the long-term canonical target per #108.

### D8 — Test-mock updates ride with the production rewrite

Per file: after rewriting `$this->method(` → `$this->_method(` in production, run the file's targeted unit test. If the test mocks the production-name method, update the mock to the underscore name. Same pattern `c4eb488` applied to `AjaxListComponentTest`. Mock-string updates are tracked in the same commit as the production rewrite — never split.

### D9 — Equivalence check unchanged

`bin/verify-underscore-method-body-equivalence.php` already verifies that `_method()` bodies match baseline. This change does not move bodies — it only edits call sites and PHPDoc. The equivalence tool stays green throughout.

## Risks / Trade-offs

- **Risk:** the detector misses a call form (e.g. dynamic `$this->{$name}(...)`) or false-flags a non-shim method that happens to share a name. Mitigation: hand-spot-check the findings file against grep output before the gate; the AdminAjax trio's count is known (~143). False-positive rate at the gate.
- **Risk:** PHPDoc rewrite mass-touches files outside the call-site batch. Mitigation: limit PHPDoc rewrites to classes appearing in the findings, unless the gate explicitly approves the broader rewrite. Smaller scope = smaller blast radius for review.
- **Trade-off:** committing to BC-anchor-only narrows the override surface. Module authors who built large `method()` overrides on the new name will silently lose dispatch on swept call paths. Mitigation: explicit PHPDoc rewrite that calls this out, and the PR body that flags the design choice. #108 inverts it later.

## Open Questions

- Whether to land Phases 1 and 2 in one PR or two. Same question as PR #104; default: one PR per directory batch, but a single large PR is acceptable if review bandwidth allows. Decided at the gate.
- Whether the new test should additionally enforce the `parent::method(...)` form for `parent::` calls (currently out of scope). Probably yes for completeness, but defer to a follow-up — this PR's scope is `$this->` only.
