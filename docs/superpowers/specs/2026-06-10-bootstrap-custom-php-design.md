# Design: `bootstrap.custom.php` support

**Date:** 2026-06-10
**Issue:** #163 — Add `bootstrap.custom.php` support for local/environment-specific initialization

## Problem

Any local configuration, custom service registration, or environment-specific
bootstrapping currently requires patching core bootstrap files. This makes core
updates error-prone and pollutes diffs. There is no clean, gitignore-able entry
point for local developer overrides, environment-specific initialization
(Debugbar, Whoops, custom DI bindings), or CI/staging setup.

## Goal

Provide an optional `bootstrap.custom.php` file that is loaded at the end of the
core bootstrap process — after all core services are registered — so developers
can add local overrides without touching tracked core files.

## Scope

In scope:

- Conditionally load `source/bootstrap.custom.php` at the end of
  `source/bootstrap.php`.
- Add `source/bootstrap.custom.php` to `.gitignore`.
- Add a committed, documented `source/bootstrap.custom.php.dist` example.
- Document the pattern in `README.md`.
- A unit test covering the load logic (present / absent).

Out of scope:

- Multiple custom bootstrap files / directory scanning.
- Loading from `var/configuration/` or the project root (decided: `source/`).
- Changing the existing `modules/functions.php` hook.

## Design

### File location

`source/bootstrap.custom.php` (and `source/bootstrap.custom.php.dist`), i.e.
under `OX_BASE_PATH`, next to `bootstrap.php` and `config.inc.php(.dist)`. This
matches the existing convention: every PHP runtime/config file lives in
`source/`, and the existing custom hook (`modules/functions.php`) is also
resolved relative to `OX_BASE_PATH`.

### Load point

At the **very end** of `source/bootstrap.php` (after the current final function
definitions, ~line 358). By this point all core services are available:

- composer autoloader (`vendor/autoload.php`)
- backwards-compatibility + module autoloaders
- `ConfigFile` registered in `Registry`
- `ExitHandler` registered (when the unified namespace exists)
- `oxNew()` (`oxfunctions.php`)
- `modules/functions.php` custom hook
- `overridablefunctions.php`
- default `ini_set` session params and the bootstrap helper functions

Loading last satisfies the acceptance note: "custom bootstrap should run after
core services are registered to allow proper overrides." Because it runs after
the default `ini_set(...)` calls, a custom file can also override those.

### Mechanism

```php
/**
 * Optional local / environment-specific bootstrap overrides.
 *
 * This file is intentionally NOT committed (see .gitignore). Copy
 * bootstrap.custom.php.dist to bootstrap.custom.php to add local overrides
 * such as custom DI bindings, Whoops/Debugbar, or ini_set() tweaks.
 *
 * Loaded last, so the composer autoloader, the shop ConfigFile, the
 * ExitHandler, oxNew() and all overridable functions are already available.
 */
if (is_readable(OX_BASE_PATH . 'bootstrap.custom.php')) {
    require OX_BASE_PATH . 'bootstrap.custom.php';
}
```

- **`is_readable` guard** → no error or warning when the file is absent
  (acceptance criterion). The default state of the repo is "absent", so the
  guard is the normal path.
- **`require` (not `include`)** → if a developer adds the file, a syntax or load
  error in it must fail loudly rather than be silently swallowed; it is an
  intentional override file. (Absence is already handled by the guard, so
  `require` never fires on a missing file.)
- **No `_once`** → bootstrap runs once per request; `require` is correct and
  symmetric with the surrounding `require`/`include` statements.

### `.gitignore`

Add under the "Shop configuration" group, next to `config.inc.php`:

```
/source/bootstrap.custom.php
```

The `.dist` file stays tracked (only the concrete file is ignored).

### `source/bootstrap.custom.php.dist`

Committed example carrying the standard O3-Shop GPL header, followed by
**commented-out** examples so copying it verbatim is a safe no-op:

- Registering / overriding a DI binding (commented).
- Enabling an error handler such as Whoops or a Debugbar (commented).
- Tweaking `ini_set(...)` for local debugging (commented).

A short top comment explains: copy to `bootstrap.custom.php`, runs last in
bootstrap, available services, and that it must not be committed.

### Documentation

Add a short subsection to `README.md` (e.g. "Local bootstrap overrides")
explaining the copy-the-dist workflow, when the file is loaded, and typical use
cases.

## Testing

Follow the existing `tests/Unit/Bootstrap/BootstrapTmpDirTest.php` pattern,
which mirrors the bootstrap conditional logic in isolation rather than booting
the whole `bootstrap.php`.

New test `tests/Unit/Bootstrap/BootstrapCustomFileTest.php`:

1. **Absent file → no load, no warning.** Point the load logic at a directory
   with no `bootstrap.custom.php`; assert the conditional does not fire and no
   error/warning is raised.
2. **Present file → executed.** Write a temporary `bootstrap.custom.php` that
   produces an observable side effect (e.g. defines a constant or sets a global
   flag); run the load logic; assert the side effect occurred.

The test reproduces the exact guard
(`is_readable($dir . 'bootstrap.custom.php')` + `require`) against a temp
directory, keeping it hermetic and not dependent on the real `source/` tree.

## Acceptance criteria mapping

| Criterion | Covered by |
|---|---|
| Core bootstrap conditionally loads `bootstrap.custom.php` | Load point + mechanism in `bootstrap.php` |
| `bootstrap.custom.php` added to `.gitignore` | `.gitignore` change |
| `bootstrap.custom.php.dist` added with inline docs/examples | `.dist` file |
| No error/warning if absent | `is_readable` guard + absent-file test |
| Documented | `README.md` subsection |
