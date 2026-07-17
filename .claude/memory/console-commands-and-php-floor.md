# Console commands & the PHP 7.4 floor

## PHP version floor is 7.4 — but local Docker runs 8.2 (8.x syntax slips through)
`composer.json` requires `"php": "^7.4 || ^8.0"` and CI runs the suite on **7.4, 8.0, 8.1, 8.2** (see `.github/workflows/pr-comment.yml`). The Docker dev container runs **PHP 8.2**, so any 8.x-only syntax compiles and passes ALL local tests, then breaks CI on 7.4/8.0. Avoid in `source/`:
- `readonly` properties (8.1+) and `readonly` promoted constructor params (8.1+)
- constructor property promotion (8.0+)
- trailing comma in function/method **parameter declarations** (8.0+)
- union/intersection types, enums, `never`, first-class callable syntax

Typed properties WITHOUT promotion (`private Foo $bar;`) ARE fine — that's a 7.4 feature. When in doubt, match the explicit-property style of existing `Internal/.../DataObject/*` classes (`private $x;` + `@var` + assignment in `__construct`). This bit #122: a `ThemeDataObject` with `readonly` promotion passed every Docker test, caught only in code review.

## Symfony Console is v3.4 (NOT 4.4+)
`symfony/console` resolves to **v3.4.47**. Therefore in console Command classes:
- Do NOT use `Command::SUCCESS` / `Command::FAILURE` constants (added in 4.4) — return plain `0` / `1` (define your own `EXIT_*` int constants).
- `: int` return type on `execute()` IS fine (correction — the old note here said avoid it). PHP allows a child to add a return type where the parent declares none, and Symfony Console 3.4's `execute()` has none. As of b-1.7, 7 commands use `: int` (incl. `Domain/Authentication/Command/UserCreateCommand`, CI-green on 7.4/8.0/8.1/8.2) and 9 omit it — both work. `Domain/Migration/Command/*` (#205) use `: int`.
- `Symfony\Component\Console\Helper\Table` and `CommandTester::getStatusCode()` ARE available in 3.4.

## Registering a console command
Add a service in the relevant `Internal/Framework/<area>/services.yaml` (or a `Command/services.yaml` imported by it) tagged `{ name: 'console.command', command: 'oe:foo:bar' }`. `_defaults: autowire: true` injects constructor deps by interface FQCN. `Internal/Framework/services.yaml` already imports `Theme/services.yaml`, `Console/services.yaml`, `Module/services.yaml`, etc. After editing, `php bin/oe-console oe:cache:clear` then `php bin/oe-console list` to confirm the command appears.

## Theme CLI (#122) — oe:theme:activate / deactivate / list
Live as of #122, in `source/Internal/Framework/Theme/` (Bridge/Command/Exception/DataObject). They wrap the legacy `\OxidEsales\Eshop\Core\Theme` model via `ThemeBridge`. Key facts:
- `Theme::activate()` is **idempotent and headless-safe**: it writes `sTheme`/`sCustomTheme` + the `theme:<id>` config rows from the theme's `theme.php` via `SettingsHandler`, using `Config::saveShopConfVar` (works fine from a CLI/bootstrap context, current shop id).
- `oe:theme:activate` with NO argument resolves the configured theme (`sCustomTheme || sTheme`) and re-applies its defaults — this is what `bin/o3-setup` and `Setup/Controller.php` call after data import (additive; the duplicated SQL rows in `initial_data.sql` are intentionally NOT removed — that was deferred Phase 3 of #122).
- `oe:theme:deactivate <id>`: clears `sCustomTheme` if `<id>` is the active child theme (reverts to parent base); refuses if `<id>` is the active base theme (`sTheme`); no-op otherwise.
