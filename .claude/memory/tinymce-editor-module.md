---
name: tinymce-editor-module
description: tinymce-editor is a sibling repo (not in shop-ce); how it vendors TinyMCE, its build, and the v7 upgrade
type: reference
---

# o3-shop/tinymce-editor module

The admin WYSIWYG editor module is a **separate repo**, not in shop-ce. Locally:
`/Users/nick/o3/tinymce-editor`. It is pulled into the product via the metapackage
(`shop-metapackage-ce` / `o3-shop`), **not** via `shop-ce/composer.json` (which does not
require it). The editor source therefore is NOT in this checkout.

## Layout / how it works
- Vendors the editor under `out/tinymce/` — **committed to git**, not fetched at install.
- Builds its `tinymce.init({...})` config from PHP classes: `Application/Core/TinyMCE/`
  `Configuration.php` (assembles options), `Options/*` (one class per init option,
  implement `OptionInterface`: `getKey`/`get`/`isQuoted`/`requireRegistration`),
  `PluginList.php` + `Plugins/*`, `Toolbar/*` + `ToolbarList.php`.
- Custom JS plugins live in `build/plugins/{oxfullscreen,roxy}/plugin.js` and are copied
  **verbatim** (no minify) to `out/plugins/.../plugin.js`. Keep the two copies identical.
- `build/3_build.js` just copies dirs into `_module/`; `build/4_publish.js` git-commits.
- The module has **no automated test suite** — verification is manual admin QA. It does
  ship `.php-cs-fixer.php` + `phpstan.neon`, but those need the module living inside a shop
  checkout at `source/modules/o3-shop/tinymce-editor`. Host has php/node/npm for `php -l` /
  `node --check`.

## v2.0.0 upgrade — TinyMCE 6.4.1 → 7.9.3 (issue #194, CVE-2026-47759/47761/47762)
- `build/update.sh` (was a dead TinyMCE-4 cloud URL) now does `npm pack tinymce@<ver>` and
  stages the tree into `out/tinymce/`, **preserving `out/tinymce/langs/`** (npm ships no
  language packs). Re-run it to bump: `./build/update.sh 7.9.3`.
- TinyMCE 7.x renamed the license file `license.txt` → `license.md` — update.sh copies both
  names; ship `license.md` for GPL compliance.
- **TinyMCE 7 breaking changes that hit this module:** (1) `license_key` now required —
  added `Options/LicenseKey.php` returning `'gpl'`; (2) `editor.settings` removed — the
  `roxy` file-picker plugin was ported to `editor.options.register/get/set`; (3) `fullpage`
  + `legacyoutput` plugins were removed back in 6.0 (phantom 404s) — deleted their PHP
  classes. `file_picker_callback` is safe to `editor.options.set(...)` because the silver
  theme registers it before plugins init.
- Editor license is **GPL-2.0-or-later** — compatible with the module's GPL-3.0; module
  license stays GPL-3.0.
- Custom `oxfullscreen` button is named `fullscreen` (toggles the admin frameset) — distinct
  from the removed `fullpage`.
- **TinyMCE 7 validates option value types.** The old `MaxHeight`/`MaxWidth` options emitted
  `'90%'`, which 7.x rejects (`Invalid value passed for the max_height option. The value must
  be a number.`) — numeric px only. They were inert in 6.x. Dropped both options. If a cap is
  ever wanted, emit a numeric px value, not a percentage.
- The editor's `content_css` points at the active theme's compiled CSS
  (`/out/<theme>/src/css/styles.min.css`). In a dev shop without built theme assets this 404s
  in the editor console — environmental, NOT a module bug (the storefront 404s it too).
- **Two clones exist:** the module is checked out both at `/Users/nick/o3/tinymce-editor`
  (original) and at `/Users/nick/o3/shop-ce/tinymce-editor` (the satellite the shop actually
  runs via the source/modules symlink). They diverge — the satellite is the live/canonical one
  for shop QA. Commit/push the #194 branch from the satellite.
- Follow-up after the module release is tagged: bump the product reference in metapackage /
  o3-shop, then re-scan the trowow image.
