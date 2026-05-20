## ADDED Requirements

### Requirement: Pinned baseline inventory

The system SHALL maintain a committed JSON inventory that enumerates every `protected` or `public` method whose name begins with `_` (but not `__`, to exclude PHP magic methods) and that is declared (not inherited) on a class in a file under `source/` in revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`, grouped by fully qualified class name using the concrete `OxidEsales\EshopCommunity\...` namespace. Files outside `source/` (notably `tests/`, `bin/`, and tooling) are intentionally excluded — they are not part of the shop's module BC surface and their class chains are not resolved through the unified namespace.

Each inventory entry MUST include the following fields:
- `class`: concrete FQCN under `OxidEsales\EshopCommunity\...`
- `method`: the method name, including its leading underscore
- `visibility`: `protected` or `public`
- `is_static`: boolean
- `is_abstract`: boolean
- `baseline_file`: repo-relative path to the source file in the baseline revision

The inventory file SHALL be stored at `tests/Unit/BackwardsCompatibility/underscore-method-snapshot.json` and SHALL be sorted deterministically by `class` then `method` so that diffs remain stable across regenerations.

#### Scenario: Inventory is the single source of truth

- **WHEN** a tool or test requires the set of `_method()` names subject to the inheritance contract
- **THEN** the tool or test reads exclusively from the committed baseline inventory file and does not scan the current working tree

#### Scenario: Inventory content matches the baseline revision

- **WHEN** the generator script is invoked against revision `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`
- **THEN** the script's output matches the committed inventory byte-for-byte after the canonical sort

### Requirement: Inheritance dispatch contract

For every `(class, _methodName)` entry in the baseline inventory whose class still exists in the current codebase, the system SHALL guarantee that a subclass override of `_methodName()` is invoked when a caller enters the class via either the underscore or the non-underscore name. A deprecation shim that introduces a sibling `methodName()` alongside `_methodName()` MUST NOT route the implementation body such that overrides of `_methodName()` are bypassed.

#### Scenario: Both names exist; override fires through the non-underscore entry point

- **WHEN** a subclass overrides `_methodName()` with a marker implementation
- **AND** a caller invokes the non-underscore `methodName()` on the subclass
- **THEN** the marker implementation in the subclass is executed

#### Scenario: Only the underscore method exists

- **WHEN** the class still declares `_methodName()` and no sibling `methodName()` has been introduced
- **THEN** the contract holds trivially and no action is required

### Requirement: Automated runtime inheritance-contract test

The system SHALL include a PHPUnit test that, driven by the pinned baseline inventory, verifies the inheritance dispatch contract at runtime using a synthetic subclass with an observable marker. The test MUST resolve each inventory entry's class through the unified virtual namespace `OxidEsales\Eshop\...` before reflecting; reflection against the concrete `OxidEsales\EshopCommunity\...` name is not an acceptable alternative.

The synthetic subclass SHALL be instantiated via `ReflectionClass::newInstanceWithoutConstructor()`. The non-underscore `methodName()` SHALL be invoked via `ReflectionMethod::invokeArgs()` with type-defaulted arguments, wrapped in `try/catch` for `\Throwable`. The test SHALL distinguish `override_not_called` (call returned without the marker firing) from `exception_before_dispatch` (a `\Throwable` was thrown before the marker could fire).

#### Scenario: Test resolves classes via the unified namespace

- **WHEN** the test loads an inventory entry whose baseline class is `OxidEsales\EshopCommunity\Application\Controller\Admin\AdminListController`
- **THEN** the synthetic subclass extends `\OxidEsales\Eshop\Application\Controller\Admin\AdminListController`

#### Scenario: Test fails when override is not dispatched

- **WHEN** the shim for a given entry delegates in the wrong direction — `_method()` calls `method()` — and the subclass overrides `_method()`
- **THEN** the test fails for that entry and records `observed: "override_not_called"` in the findings output

#### Scenario: Test passes when override is dispatched

- **WHEN** the shim for a given entry delegates in the correct direction — `method()` calls `_method()` — and the subclass overrides `_method()`
- **THEN** the test passes for that entry

#### Scenario: Test distinguishes exceptions from dispatch failures

- **WHEN** invoking `method()` with type-defaulted arguments throws a `\Throwable` before the underscore override can fire
- **THEN** the test records `observed: "exception_before_dispatch"` for that entry rather than `override_not_called`

### Requirement: Machine-readable findings output

When the inheritance-contract test run observes one or more contract violations, the system SHALL write a machine-readable findings list as JSON to `openspec/changes/fix-underscore-method-inheritance/findings.json`.

Each findings entry MUST include:
- `class`: concrete baseline class (`OxidEsales\EshopCommunity\...`)
- `unified_class`: resolved unified class (`OxidEsales\Eshop\...`)
- `method`: underscore method name
- `sibling_method`: non-underscore method name
- `current_file`: repo-relative path of the current implementation
- `observed`: one of `override_not_called`, `exception_before_dispatch`, `class_missing`
- `notes`: free-form diagnostic string

Findings entries SHALL be sorted deterministically by `class` then `method`.

#### Scenario: Findings are written after a run with violations

- **WHEN** the inheritance-contract test run observes one or more contract violations
- **THEN** a valid JSON file exists at the findings path containing one entry per violation, deterministically sorted

#### Scenario: Findings file is absent or empty after a clean run

- **WHEN** the inheritance-contract test run observes no contract violations
- **THEN** the findings path either does not exist or contains an empty JSON array

### Requirement: Reproducible generator script

The system SHALL provide a generator script at `tests/Unit/BackwardsCompatibility/generate-underscore-method-snapshot.php` that produces the baseline inventory JSON for a given git revision. The script MUST:
- Resolve the repo root by running `git -C __DIR__ rev-parse --show-toplevel` and MUST NOT rely on the caller's current working directory
- Accept `--revision=<sha>`; default value: the pinned baseline `ebe86dc08875034d5a3d0533b7cbdede7cc6abff`
- Accept `--output=<path>`: relative paths are interpreted against the caller's current working directory, absolute paths are used as-is; default: the canonical inventory path resolved against the repo root
- Accept `--help` and `-h`: prints a usage summary (synopsis, description, options with defaults, an example invocation from outside the repo, pointer to this design) to stdout and exits 0 with no side effects (no git calls, no output file written, no directory change). `--help` / `-h` takes precedence over every other flag on the same command line
- Verify the requested revision exists in the resolved work tree's history via `git -C <repo-root> rev-parse --verify <revision>^{commit}` before proceeding
- Exit non-zero with a clear diagnostic if invoked outside a git work tree or if the requested revision does not exist
- Leave the caller's current working directory unchanged on exit (no uncompensated `chdir`)

#### Scenario: Script invoked from a directory outside the repo

- **WHEN** a caller invokes the script from a directory outside the repo with the pinned revision
- **THEN** the script resolves the repo root from its own location, generates the inventory against the pinned revision, writes to the default output path inside the resolved repo, and exits zero

#### Scenario: Script rejects a non-existent revision

- **WHEN** a caller invokes the script with a revision that does not exist in the resolved repo's history
- **THEN** the script exits non-zero with a diagnostic naming the missing revision and the resolved repo root, and no output file is written

#### Scenario: Script rejects invocation outside any git work tree

- **WHEN** the script's own location `__DIR__` is not inside a git work tree
- **THEN** the script exits non-zero with a diagnostic and no output file is written

#### Scenario: `--help` prints usage and exits with no side effects

- **WHEN** the script is invoked with `--help` or `-h` (with or without additional flags)
- **THEN** the script writes a usage summary to stdout containing synopsis, description, options with their defaults, an example invocation from outside the repo, and a pointer to the design doc
- **AND** the script exits with status 0
- **AND** no output file is written, no `git` subprocess is started, and the caller's current working directory is unchanged

### Requirement: `_method()` signatures remain verbatim during remediation

When remediation for an inventory entry inverts the delegation direction, the signature of `_methodName()` — including parameter types, default values, variadic markers, return type, and PHPDoc — SHALL remain identical to the baseline. Native type declarations MUST NOT be added to either `_method()` or `method()` as part of this change; signature tightening on the non-underscore counterpart is out of scope and tracked separately in [o3-shop/o3-shop#108](https://github.com/o3-shop/o3-shop/issues/108) for the next major release.

#### Scenario: Remediation preserves the underscore signature

- **WHEN** Claude applies the delegation inversion for a given class
- **THEN** the `_method()` signature in the resulting source matches the baseline revision exactly

#### Scenario: No native types added to `method()` in this change

- **WHEN** Claude rewrites `method()` as a one-line delegate
- **THEN** `method()`'s parameter and return declarations remain byte-identical to the pre-remediation state; the only change to `method()` is its body

### Requirement: PHPDoc delegation hint on the non-underscore counterpart

When remediation for an inventory entry inverts the delegation direction, the PHPDoc block of the non-underscore `methodName()` SHALL include a hint directing downstream override authors to call `parent::methodName()` (not the deprecated `_methodName()`) if their override does not fully replace the behavior, so that downstream overrides in a multi-level class chain are preserved. The hint MUST reference [o3-shop/o3-shop#108](https://github.com/o3-shop/o3-shop/issues/108) as the tracking item for the structural (template-method) fix.

#### Scenario: Delegation hint present on every remediated method

- **WHEN** Claude completes remediation for an inventory entry
- **THEN** the PHPDoc of `methodName()` contains a note instructing override authors to call `parent::methodName()` (and not `_methodName()`) when not fully replacing the behavior
- **AND** the PHPDoc contains a reference to `o3-shop/o3-shop#108`

#### Scenario: Hint wording adapts to file conventions

- **WHEN** the target file uses a PHPDoc style that differs from the canonical template
- **THEN** the wording is adjusted to match the local style
- **AND** the two load-bearing clauses — "call `parent::method()`, not `_method()`" and the issue reference — remain present in substance

### Requirement: `@deprecated` hint on `_method()` points to `method()` and addresses new code

When remediation for an inventory entry inverts the delegation direction, the PHPDoc block of `_methodName()` SHALL contain an `@deprecated` tag whose message (a) names `methodName()` as the successor and (b) instructs authors of new code — including new modules — not to call or override `_methodName()`. If the shim inherited from the baseline already has such a block, the remediation preserves it, updating the message only if it does not yet name `methodName()` by name. If the block is missing, Claude adds one.

The three load-bearing clauses that must be present in substance: the `@deprecated` tag itself, a direct reference to `methodName()` as the replacement, and the "new code MUST NOT call or override `_methodName()`" guidance.

#### Scenario: Deprecation hint present on every remediated underscore method

- **WHEN** Claude completes remediation for an inventory entry
- **THEN** the PHPDoc of `_methodName()` contains an `@deprecated` tag that names `methodName()` as the replacement and advises new code not to call or override `_methodName()`

#### Scenario: Existing valid deprecation block is preserved

- **WHEN** the shim inherited from the baseline already contains an `@deprecated` block that names `methodName()` as the replacement
- **THEN** the existing block is preserved without rewriting, and Claude adds only the "new code should use `methodName()`" clause if it is missing

#### Scenario: Missing deprecation block is added during remediation

- **WHEN** `_methodName()` in the current codebase has no `@deprecated` block (e.g. the shim lost it during the `e4e180cc` refactor)
- **THEN** Claude adds one following the canonical template, adapted to the file's PHPDoc style
