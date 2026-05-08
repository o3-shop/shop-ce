## ADDED Requirements

### Requirement: Internal call sites of BC-shim methods use the underscore form

For every `(class, _methodName)` entry in the baseline inventory whose class declares both `_methodName()` and a sibling `methodName()` in the current codebase, every internal call expressed as `$this->methodName(...)` inside any file under `source/` SHALL be written as `$this->_methodName(...)` instead. This pins `_methodName()` (the BC anchor) as the canonical override target for the duration of the BC-shim transition.

`parent::methodName(...)`, `static::methodName(...)`, and `self::methodName(...)` are out of scope for this requirement — `parent::` calls are intentional per the existing `@internal` PHPDoc directive, and the other forms are not produced by the BC-shim pattern.

#### Scenario: Internal call site uses the underscore form

- **WHEN** a class in `source/` declares both `_methodName()` and `methodName()` per the BC-shim pattern
- **AND** any method body in the class chain expresses an internal call as `$this->methodName(...)`
- **THEN** the call SHALL be written as `$this->_methodName(...)` instead

#### Scenario: `parent::` calls are exempt

- **WHEN** a subclass override of `methodName()` calls `parent::methodName(...)` to delegate up the chain
- **THEN** the call is exempt from this requirement and remains unchanged

#### Scenario: Classes without the shim pair are exempt

- **WHEN** a class does not declare both `_methodName()` and `methodName()`
- **THEN** any `$this->methodName(...)` call in that class is not subject to this requirement

### Requirement: Convention-enforcement test for internal call sites

The system SHALL include a PHPUnit test that, driven by the pinned baseline inventory, statically scans `source/` for `$this->methodName(...)` call sites that violate the call-site form requirement and asserts the violation list is empty. The test runs in the normal `tests/Unit/` suite alongside `InheritanceContractTest`.

The test MUST distinguish itself from `InheritanceContractTest`:
- `InheritanceContractTest` enforces structural BC: a subclass override of `_methodName()` is dispatched through every public entry point.
- This new test enforces call-site convention: internal code uses `$this->_methodName(...)` rather than `$this->methodName(...)`.

#### Scenario: Test fails when an internal call site uses the non-underscore form

- **WHEN** any file under `source/` contains `$this->methodName(...)` for a `methodName` that is the non-underscore counterpart of an inventory `_methodName` entry, in a class declaring both names
- **THEN** the test fails and records the `(file, line, class, method)` of the violation

#### Scenario: Test passes when no violations exist

- **WHEN** every internal call site of a BC-shim method uses the underscore form
- **THEN** the test passes and emits no findings file (or emits an empty array)

### Requirement: Reproducible call-site detector script

The system SHALL provide a tokenizer-based detector script at `bin/find-internal-shim-call-sites.php` that produces the call-site findings list. The script MUST:
- Accept `--inventory=<path>`, `--source-root=<path>`, `--output=<path>`, `--help`, `-h`
- Resolve the repo root by running `git -C __DIR__ rev-parse --show-toplevel` and resolve all default paths against it (caller `cwd`-independent)
- Default `--inventory` to `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json`
- Default `--source-root` to `source/`
- Default `--output` to `openspec/changes/fix-internal-shim-call-sites/findings.json`
- Tokenize each `.php` file under the source root with PHP's built-in `token_get_all()` and emit `(file, line, declaring_class, method)` for every `$this->method(` call site that satisfies the pair-presence check
- Sort findings deterministically by `(file, line)` and serialize as pretty-printed JSON with a trailing newline
- Write atomically to the resolved output path
- Print a usage summary on `--help`/`-h` with no side effects (no source walking, no output written, no `chdir`)

#### Scenario: Default invocation produces the canonical findings file

- **WHEN** the script is invoked with no arguments
- **THEN** the script reads the default inventory, walks the default source root, and writes findings to the default output path

#### Scenario: `--help` is non-side-effecting

- **WHEN** the script is invoked with `--help` or `-h` (with or without additional flags)
- **THEN** the script writes a usage summary to stdout and exits 0
- **AND** no source walking happens, no output file is written, and the caller's `cwd` is unchanged

#### Scenario: Path resolution is `cwd`-independent

- **WHEN** the script is invoked from outside the repository root
- **THEN** all default paths resolve against the repo root via `git rev-parse --show-toplevel`, not against the caller's `cwd`

### Requirement: Findings file shape

When the call-site detector emits findings, the system SHALL write a machine-readable findings list as JSON to `openspec/changes/fix-internal-shim-call-sites/findings.json`. Each entry MUST include:
- `file`: repo-relative source path
- `line`: integer line number of the call site
- `class`: concrete FQCN at the call site under `OxidEsales\EshopCommunity\...`
- `method`: the non-underscore method name as written in the call

Findings entries SHALL be sorted deterministically by `(file, line)`.

#### Scenario: Findings are emitted on a non-empty run

- **WHEN** the detector identifies one or more call-site violations
- **THEN** a valid JSON file exists at the findings path with one entry per violation, deterministically sorted

#### Scenario: Findings file is empty after a clean run

- **WHEN** the detector identifies zero violations
- **THEN** the findings file is either absent or contains an empty JSON array

### Requirement: PHPDoc transitional framing on remediated shim pairs

When remediation rewrites a class's internal call sites for an inventory entry, the PHPDoc blocks of `_methodName()` and `methodName()` SHALL be updated to flag the BC-shim pair as a **transitional** state and reference [o3-shop/o3-shop#108](https://github.com/o3-shop/o3-shop/issues/108) as the long-term inversion target. Three load-bearing clauses must be present in substance across PHPDoc-style variations:

For `_methodName()`:
- `@deprecated` tag identifying this as transitional
- The instruction that modules SHOULD override `_methodName()` for now (since internal call paths route through it)
- A pointer to #108 as the eventual inversion target that promotes `methodName()` to canonical

For `methodName()`:
- `@internal` annotation explaining this is a public delegate during the transition
- The note that internal call paths bypass this name after the sweep
- A pointer to #108 as the eventual inversion target

#### Scenario: Transitional framing is present on every remediated pair

- **WHEN** a class is touched by Phase 2 remediation
- **THEN** the PHPDoc of `_methodName()` contains a transitional `@deprecated` block referencing #108, and the PHPDoc of `methodName()` contains the `@internal` block referencing #108

#### Scenario: Existing PHPDoc style is preserved

- **WHEN** the file uses a PHPDoc style that differs from the canonical template
- **THEN** the wording adapts to match the local style, while the three load-bearing clauses remain in substance
