# Backwards Compatibility Contract Test

## Summary

O3-Shop positions itself as a safe harbour for users of the predecessor's v6 line who do not want to migrate to v7. This means the entire public/protected API surface from v6 must remain intact and overridable in O3-Shop — no methods removed, no methods made `final`, and no unified namespace mappings broken.

Currently there is no automated verification of this guarantee.

## Background: Two Namespace Layers

The codebase has two namespace layers that are both relevant for backwards compatibility:

| Layer | Namespace | Location |
|---|---|---|
| **Edition namespace** | `OxidEsales\EshopCommunity\...` | Concrete classes in `source/` |
| **Unified namespace** | `OxidEsales\Eshop\...` | Generated wrapper/proxy classes |

Module developers and shop operators work against the **unified namespace** (`OxidEsales\Eshop\...`). That is the public API they extend and call. The mapping between the two layers is defined in `source/Core/Autoload/UnifiedNameSpaceClassMap.php`.

A test that only checks the edition namespace would miss:

- A unified namespace class being **removed from the map** (edition class still exists but is no longer reachable)
- A unified namespace wrapper being generated incorrectly (e.g., made `final`, or methods not forwarded)
- A **class rename** where the edition class changes but the mapping is not updated

Therefore the test must cover **both layers**.

## Motivation

Module developers and shop operators relying on class extensions need confidence that:

- Every public/protected method they override or call today will still exist tomorrow.
- No class or method will be sealed with `final`, breaking their extensions.
- Every unified namespace class they reference still resolves to a valid edition class.

A single automated test can enforce this contract on every CI run.

## Proposed Solution

### 1. Snapshot Generator

A PHP script produces two JSON snapshot files using PHP Reflection:

#### a) Unified Namespace Map Snapshot

Captures every entry from `UnifiedNameSpaceClassMap.php`:

- Unified namespace class name (`OxidEsales\Eshop\...`)
- Mapped edition class name (`OxidEsales\EshopCommunity\...`)
- Flags: `isAbstract`, `isInterface`, `isDeprecated`

Committed as `tests/BackwardsCompatibility/unified-namespace-snapshot.json`.

#### b) Method Signature Snapshot

Scans all classes reachable via the unified namespace and records every public/protected method:

- Unified namespace class name
- Method name
- Visibility (`public` / `protected`)

Committed as `tests/BackwardsCompatibility/api-signature-snapshot.json`.

Both snapshots together represent the v6 API contract.

### 2. PHPUnit Test

`tests/Unit/BackwardsCompatibility/BackwardsCompatibilityTest.php` loads both snapshots and asserts:

#### Unified Namespace Integrity

| Check | Assertion |
|---|---|
| **Map completeness** | Every unified namespace class from the snapshot still exists in `UnifiedNameSpaceClassMap.php` |
| **Target validity** | The mapped edition class exists and is loadable |
| **Consistency** | The flags (`isAbstract`, `isInterface`) still match |

#### Method-Level Contract

| Check | Assertion |
|---|---|
| **Existence** | The class and method still exist (checked via the unified namespace) |
| **Overridability** | Neither the method nor the class is marked `final` |

### 3. CI Integration

The test runs as part of the standard `test-all` suite so CI catches any regression automatically.

## Acceptance Criteria

- [ ] Generator script produces deterministic JSON snapshots for both the namespace map and method signatures
- [ ] Snapshots are committed to the repository
- [ ] PHPUnit test verifies unified namespace map integrity (every entry resolves, no entries removed)
- [ ] PHPUnit test verifies every method snapshot entry for existence and overridability
- [ ] Test passes on the current codebase
- [ ] Test runs in the existing CI pipeline without additional dependencies

## Design Decisions

| Decision | Rationale |
|---|---|
| **Snapshot-based** (not live comparison) | Self-contained — no need to install the predecessor package alongside O3-Shop |
| **Two snapshots** (map + methods) | Covers both the namespace routing layer and the actual method-level API surface |
| **Unified namespace as primary target** | That is the namespace module developers actually extend — testing edition classes alone would miss mapping breakages |
| **JSON format** | Human-readable, easy to diff in PRs |
| **Reflection-only** | Fast — no database or full shop bootstrap needed beyond autoloading |

## Workflow After Merge

- **Adding** new public/protected methods → regenerate the snapshots (they grow, tests still pass)
- **Adding** new unified namespace mappings → regenerate the namespace snapshot
- **Removing** a method or marking it `final` → test fails, requires explicit review and decision
- **Removing** a unified namespace mapping → test fails, requires explicit review and decision
