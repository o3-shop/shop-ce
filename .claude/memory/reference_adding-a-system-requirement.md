---
name: reference_adding-a-system-requirement
description: Adding a check to the setup screen + admin System Health page is 3 touch points and zero template edits — the pages are fully data-driven
type: reference
---

# Adding a system requirement (setup screen + admin "Systemgesundheit")

Both requirement UIs render from the *same* source — `SystemRequirements::getSystemInfo()`,
which walks `getRequiredModules()` and calls a check method per module id. Neither
`source/Setup/tpl/systemreq.php` nor `source/Application/views/admin/tpl/sysreq_main.tpl`
(nor `diagnostics_main.tpl`) enumerates the checks, so **adding one needs no template change**.

Three touch points for a new module id `my_check`:

1. `source/Core/SystemRequirements.php`
   - add `'my_check'` to the right list in `getRequiredModules()`
     (`$aRequiredPHPExtensions` / `$aRequiredPHPConfigs` / `$aRequiredServerConfigs` —
     the list decides the group heading)
   - add `'my_check' => 'php'` to `$_aInfoMap` (docs-URL anchor; without it the
     "learn more" link drops the `#anchor`)
   - implement `checkMyCheck()`. **The method name is derived, not registered:**
     `getModuleInfo()` does `'check' . str_replace(' ', '', ucwords(str_replace('_', ' ', $id)))`,
     so a typo in the id is a fatal on the setup page, not a missing row.
2. Setup lang: `MOD_MY_CHECK` in `source/Setup/En/lang.php` **and** `source/Setup/De/lang.php`
   (setup ships only these two languages; `Setup\Language::getModuleName()` = `'MOD_' . strtoupper($id)`).
3. Admin lang: `SYSREQ_MY_CHECK` in `source/Application/views/admin/{en,de}/lang.php`
   (template does `"SYSREQ_"|cat:$sModule|oxupper`). Keep these arrays alphabetically sorted.

## Return value decides whether setup is blocked

`Setup\Controller::systemReq()` calls `SystemRequirements::canSetupContinue()`, which returns
false as soon as **any** module state is `MODULE_STATUS_BLOCKS_SETUP` (0) — the
"Proceed with install" button is then simply not rendered. So:

- `MODULE_STATUS_OK` (2) → green `pass`
- `MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS` (1) → yellow `pmin`, setup continues
- `MODULE_STATUS_BLOCKS_SETUP` (0) → red `fail`, **installation impossible**
- `MODULE_STATUS_UNABLE_TO_DETECT` (-1) → grey `null`, setup continues

Exceptions (mod_rewrite/htaccess, mysql_version) are patched *after* the fact in
`Setup\Controller::updateSystemRequirementsInfo()`, not inside the check methods.

## Worked example

`gd_freetype` (branch `gd-freetype-requirement`): `checkGdFreetype()` returns 0 when
`function_exists('imagettftext')` is false, so a GD built without FreeType blocks install.
Motivation: [[reference_eu-guarantee-labels]] renders text into the label with `imagettftext()`
and could only log an error at runtime. `composer.json` is *not* the place for this — composer
can't express "ext-gd **with FreeType**", and the file only pins pdo/json anyway.

Regression guard worth keeping: a test that loops `getRequiredModules()` and asserts
`method_exists()` for each derived `check*()` name.
