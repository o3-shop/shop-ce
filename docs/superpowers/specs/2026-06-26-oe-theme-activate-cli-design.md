# Design: `oe:theme:*` CLI + install-path wiring

**Issue:** #122 — "Add oe:theme:activate CLI; consolidate theme defaults to theme.php"
**Date:** 2026-06-26
**Branch:** `122-oe-theme-activate-cli`
**Scope:** Phases 1 & 2 of the issue. Phase 3 (removing the duplicated SQL blocks from `initial_data.sql`, the demodata repo, and dead `setup.sql` files) is explicitly **out of scope** here.

## Problem

Theme activation today only happens via the admin UI's "Activate" button, which calls
`\OxidEsales\Eshop\Core\Theme::activate()`. That method is the canonical primitive: it reads
`theme.php` and writes the corresponding `sTheme` / `sCustomTheme` config vars plus the
`theme:<id>` rows via `SettingsHandler::run()`. There is no CLI equivalent, unlike modules which
have `oe:module:activate` / `oe:module:deactivate`. As a result the install paths (`bin/o3-setup`,
the web Setup wizard) rely on hand-maintained `INSERT INTO oxconfig` blocks in `initial_data.sql`
that must be kept in lockstep with `theme.php` — and drift (the root cause behind #118).

## Goals

1. **Phase 1** — expose theme activation symmetrically with the module CLI:
   - `oe:theme:activate <theme-id>`
   - `oe:theme:deactivate <theme-id>`
   - `oe:theme:list`
2. **Phase 2** — call `oe:theme:activate` from the install paths so activation (not hand-written
   SQL) is what populates the live theme config. Additive only: existing SQL rows stay; activation
   is idempotent and re-writes the same rows.

## Non-goals (YAGNI)

- No removal of any SQL (`initial_data.sql`, `demodata.sql`, `setup.sql`) — that is Phase 3.
- No new theme-picker UI in the Setup wizard.
- No multi-shop / subshop theme handling beyond what the legacy model already does (single CE shop;
  shop id flows through the existing `--shop-id` console option and `Config::getShopId()`).
- No changes to `theme.php` files or asset/symlink layout.

## Architecture

Mirror the existing **module CLI** pattern exactly. Modules use a thin legacy bridge
(`Internal/Framework/Module/Setup/Bridge/ModuleActivationBridge`) that wraps the legacy activation,
and commands depend on its interface. We do the same for themes, placing everything in the existing
`source/Internal/Framework/Theme/` directory (which already contains `Bridge/` and `services.yaml`).

### New unit: `ThemeBridge`

`source/Internal/Framework/Theme/Bridge/ThemeBridgeInterface.php`
`source/Internal/Framework/Theme/Bridge/ThemeBridge.php`

Wraps `oxNew(\OxidEsales\Eshop\Core\Theme::class)`. Single purpose: turn theme operations into a
mockable interface so the commands are unit-testable (the legacy model uses static `oxNew` /
`getConfig()` and cannot be mocked directly).

```php
interface ThemeBridgeInterface
{
    /** @throws ThemeNotFoundException, \OxidEsales\Eshop\Core\Exception\StandardException */
    public function activate(string $themeId): void;

    /** @throws ThemeNotFoundException, CannotDeactivateThemeException */
    public function deactivate(string $themeId): void;

    public function getActiveThemeId(): string;

    /** @return ThemeDataObject[]  list of {id, title, version, parentTheme, active} */
    public function list(): array;
}
```

- `activate`: `oxNew(Theme)`, `load($id)` — if `load` returns false the theme dir / `theme.php`
  doesn't exist → throw `ThemeNotFoundException`. Otherwise call `->activate()` (which may throw the
  legacy `StandardException` on activation errors, e.g. parent-version mismatch — let it propagate;
  the command renders it as `<error>`).
- `deactivate` (semantics chosen in brainstorming — "revert to parent; refuse on base theme"):
  - Load `<id>`. If it doesn't exist → `ThemeNotFoundException`.
  - If `<id>` is the active **child** theme (`sCustomTheme === <id>`): clear `sCustomTheme`
    (`saveShopConfVar('str', 'sCustomTheme', '')`) → storefront falls back to the parent base theme
    held in `sTheme`. Success.
  - If `<id>` is a **base** theme currently set as `sTheme` (and not a child override): refuse —
    throw `CannotDeactivateThemeException` ("Cannot deactivate base theme '<id>': the storefront
    would be left without a theme. Activate another theme instead."). The command renders this as
    `<error>` and exits non-zero.
  - If `<id>` is not active at all: no-op, friendly message "Theme '<id>' is not active."
- `getActiveThemeId`: returns `sCustomTheme` if set, else `sTheme` (matches
  `Theme::getActiveThemeId()`).
- `list`: `oxNew(Theme)->getList()` → map each to a small read-model
  (`ThemeDataObject` or plain associative array) of `id`, `title`, `version`, `parentTheme`,
  `active` (active = id matches `getActiveThemeId()` or its parent chain). A plain array is fine;
  a tiny DTO is cleaner — decide in the plan, no behavioural difference.

### New unit: commands

`source/Internal/Framework/Theme/Command/ThemeActivateCommand.php` → `oe:theme:activate`
`source/Internal/Framework/Theme/Command/ThemeDeactivateCommand.php` → `oe:theme:deactivate`
`source/Internal/Framework/Theme/Command/ThemeListCommand.php` → `oe:theme:list`

Each is a thin `Symfony\Component\Console\Command\Command` depending only on `ThemeBridgeInterface`,
following `ModuleActivateCommand`'s shape: `configure()` (description, `theme-id` argument, help) +
`execute()` (call bridge, print `<info>`/`<error>`, return exit code). Message constants mirror the
module command style:

- activate: `Theme "%s" was activated.` / `Theme "%s" not found.`
- deactivate: `Theme "%s" was deactivated.` / `Theme "%s" is not active.` / refusal error
- list: a Symfony `Table` with columns **ID · Title · Version · Parent · Active**.

`execute()` returns `Command::SUCCESS` / `Command::FAILURE` so scripts can branch on exit code.

### Registration

Extend `source/Internal/Framework/Theme/services.yaml`: register `ThemeBridgeInterface → ThemeBridge`
and the three commands, each tagged `{ name: 'console.command', command: 'oe:theme:...' }` — identical
to `source/Internal/Framework/Module/Command/services.yaml`. (Verify in the plan that
`Theme/services.yaml` is actually imported by the container config; `AdminThemeBridge` is registered
there and is in use, so it is.)

## Phase 2: install-path wiring

Additive. After data import, activate the **declared default** read from config
(`sCustomTheme || sTheme`) — `initial_data.sql` already declares `sTheme='o3-theme'`, so it remains
the single source of "which theme is default"; we introduce no new hard-coded theme id.

- **`bin/o3-setup`** — after the `initial_data.sql` backfill step (~line 139, before view
  regeneration), invoke `oe-console oe:theme:activate <default>` via the same shell-out mechanism the
  script already uses for migrations. Determine `<default>` by querying `oxconfig` for `sCustomTheme`
  then `sTheme` (or have a tiny `oe:theme:activate` with no argument default to the declared theme —
  **decide in plan**; preference: explicit arg resolved from config in the script, keeping the
  command argument required and unambiguous).
- **`source/Setup/Controller.php`** — after `installShopData()` (web wizard), trigger the same
  activation. Reuse the existing console-invocation path
  (`executeExternalDatabaseMigrationCommand()`-style) rather than booting the full shop framework
  inside the wizard. Read the default from the just-imported config.

Both call sites are idempotent with the existing SQL rows.

## Error handling

- Unknown theme id → `ThemeNotFoundException` → command prints `Theme "<id>" not found.` and returns
  `FAILURE`.
- Legacy activation errors (parent version mismatch, etc.) → caught, printed as `<error>` with the
  message, returns `FAILURE`.
- Deactivating a base theme → `CannotDeactivateThemeException` → clear error, returns `FAILURE`.
- Install paths: a failed activation must surface (non-zero exit / logged error), not be swallowed —
  it indicates the shop has no working theme.

## Testing (TDD)

**Unit** (`tests/Unit/Internal/Framework/Theme/Command/…`), mocked `ThemeBridgeInterface`, mirroring
`ModuleActivateCommandTest`:
- activate: success message on activate; "not found" when bridge throws `ThemeNotFoundException`;
  `FAILURE` on activation exception.
- deactivate: success on child theme; refusal on base theme; "not active" no-op.
- list: renders rows incl. the active flag.
- `ThemeBridge` unit test where practical (active-id resolution, list mapping) — legacy `oxNew` parts
  are covered by integration.

**Integration** (`tests/Integration/Internal/Framework/Theme/Command/…`), real services, mirroring
the module integration tests:
- activate a real theme → assert `sTheme`/`sCustomTheme` config + presence of `theme:<id>` rows.
- deactivate child → assert `sCustomTheme` cleared.
- list includes installed themes with correct active flag.

## Files

**New**
- `source/Internal/Framework/Theme/Bridge/ThemeBridgeInterface.php`
- `source/Internal/Framework/Theme/Bridge/ThemeBridge.php`
- `source/Internal/Framework/Theme/Command/ThemeActivateCommand.php`
- `source/Internal/Framework/Theme/Command/ThemeDeactivateCommand.php`
- `source/Internal/Framework/Theme/Command/ThemeListCommand.php`
- `source/Internal/Framework/Theme/Exception/ThemeNotFoundException.php`
- `source/Internal/Framework/Theme/Exception/CannotDeactivateThemeException.php`
- (optional) `source/Internal/Framework/Theme/DataObject/ThemeDataObject.php`
- tests under `tests/Unit/...` and `tests/Integration/...`

**Modified**
- `source/Internal/Framework/Theme/services.yaml` — register bridge + 3 commands
- `bin/o3-setup` — activate default theme after data import
- `source/Setup/Controller.php` — activate default theme after `installShopData()`

## Open questions resolved in brainstorming

- Scope = Phases 1 + 2. ✔
- Subcommands = activate + deactivate + list. ✔
- Deactivate semantics = revert to parent; refuse on base theme. ✔
- Default theme = read declared default (`sCustomTheme || sTheme`) from config, not hard-coded. ✔
