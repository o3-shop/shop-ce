# O3-Theme 2.x (Twig) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port O3-Theme's 220 Smarty storefront templates (incl. e-mails) to Twig as O3-Theme 2.0.0 — same look, same block names — and make it the theme the v2 dev setup installs.

**Architecture:** The repo `o3-shop/o3-Theme` (checked out at `source/Application/views/o3-theme` inside shop-ce, own git repo) gets a 2.x line. OXID's MIT-licensed `oxid-esales/smarty-to-twig-converter` does the mechanical translation of every `tpl/**/*.tpl` into `*.html.twig` (output only; the tool itself is not shipped); then each storefront area is reworked by hand and verified in the running shop-ce dev environment, which is on branch `b-2.0-twig-support` (Twig core from part 1). Autoescaping is ON, so every HTML value is marked explicitly.

**Tech Stack:** Twig 3 with the O3 core's adopted twig-component functions (`translate`, `format_price`, `seo_url`, `include_widget`, `include_dynamic`, `script`, `style`, `capture`, `ifcontent`, `hasrights`, …), Bootstrap 5.3 assets unchanged, shop-ce Docker (`./docker.sh`), Playwright suite in `shop-ce/tests/Acceptance/playwright`.

**Spec:** `docs/superpowers/specs/2026-11-26-twig-support-design.md` — section "Design — part 2", decisions D1 (same package `o3-shop/o3-theme`, id `o3-theme`, 2.x replaces 1.x) and D2 (autoescape on), plus 1.5 (fresh install default). User-facing rules for theme authors: `docs/twig.md` (on `b-2.0-twig-support`).

## Global Constraints

- Theme id stays `o3-theme`, package stays `o3-shop/o3-theme`, `theme.php` version `2.0.0`, `'templateEngine' => 'twig'`.
- `composer.json` of the theme: `"o3-shop/shop-ce": "^2.0 || dev-b-2.0"` (2.x needs the v2 core).
- Visual result must match O3-Theme 1.6.5: same markup structure, CSS classes and assets (`out/o3-theme/**` untouched except where a template change forces it).
- Block names: every Smarty `[{block name="X"}]` becomes `{% block X %}` with the **same name** (module authors map 1:1). No renamed, dropped or new top-level blocks unless Twig syntax forces it; list any forced change in the CHANGELOG.
- Templates live at the same relative paths with extension `.html.twig`; the `.tpl` files are removed in 2.x (Twig-only theme).
- Autoescaping ON: mark HTML explicitly. `|raw` for: CMS content/snippets (`oxifcontent`/`ifcontent` output, content pages), long descriptions (`getLongDescription`, category/news/action long texts), translations that contain HTML (lang values with tags — decide per key, not globally), and values that are already HTML (e.g. `oViewConf->getHiddenSid()`, `getNavFormParams()`). Never `|raw` user input, `seo_url` output or `translate` output without HTML. `email/plain/**` is not escaped by the engine.
- Only Twig functions/filters that exist in the O3 core (see `source/Internal/Framework/Twig/Extensions/**` in shop-ce) or Twig itself; Twig `assign_advanced` does not exist (removed) — use native Twig arrays/`set`.
- Licence: O3-Theme stays GPL-3.0; keep existing file headers. OXID `twig-theme` 1.x (GPL-3.0) may be consulted for idioms; copy nothing from OXID 8.x lines.
- Commits in the theme repo: Nick's identity (`nlo@tro.net`, check `git -C <theme> config user.email` before the first commit), no AI attribution.
- Work branch in o3-Theme: `twig-port` off `main`; nothing is pushed until the final task (Task 9).

## Review Focus

1. **Double/missing escaping:** a translation containing `<br>` or `&nbsp;` rendered as `&lt;br&gt;`; CMS content shown as literal HTML; or the opposite, a user-controlled value (search term, address, review text, `oViewConf->getActiveCategory()->getTitle()`) rendered raw. → escaping audit in Task 8 + per-area checks.
2. **Lost blocks:** a Smarty block that silently disappeared or got renamed in conversion — modules extending it would break without notice. → block-name parity check in Task 1, re-run in Task 8.
3. **`[{capture}]`/`oxscript`/`oxstyle` ordering:** scripts/styles registered in included templates must still land in the layout's script/style output after conversion (Twig renders includes in order; capture append semantics differ). → checked on start, details and checkout pages (Tasks 3–5).
4. **Widget includes (`oxid_include_widget` → `include_widget`) and dynamic includes (`oxid_include_dynamic`)**: parameters and `nocookie`/cache behaviour must survive. → Task 3.
5. **E-mails:** HTML mails escaped like pages but plain mails must not contain `&amp;`; subjects (`*_subj`) must stay single-line plain text. → Task 7.

---

### Task 1: 2.x line, automated conversion baseline, block parity tool

**Files (theme repo):**
- Modify: `theme.php` (version, `templateEngine`), `composer.json` (shop-ce constraint), `CHANGELOG.md` (2.0.0 entry: Twig port, upgrade note pointer to shop `docs/twig.md`)
- Create: `tpl/**/*.html.twig` (converter output), remove `tpl/**/*.tpl`
- Create: `build/check-block-parity.php` (block-name parity checker, theme repo; `build/` is not packaged)

**Interfaces:**
- Produces: `php build/check-block-parity.php <ref-with-tpl> <dir-with-twig>` → exits 0 and prints `OK <n> blocks` when every `[{block name="X"}]` in the `.tpl` set exists as `{% block X %}` in the corresponding `.html.twig`, else lists `missing: <file>: <block>`; later tasks re-run it.

- [ ] **Step 1:** `git -C source/Application/views/o3-theme switch -c twig-port` (from `main` = 1.6.5). Check `user.email`.
- [ ] **Step 2:** Install the converter outside both repos (scratchpad, e.g. `composer create-project oxid-esales/smarty-to-twig-converter <scratch>/s2t` inside the shop container) and convert every `tpl/**/*.tpl` to `*.html.twig` at the same path (`toTwig convert --path=… --ext=.html.twig` or a loop over files; record the exact command in the commit message).
- [ ] **Step 3:** Write `build/check-block-parity.php` (reads the `.tpl` files from `git show main:<path>`); run it → expected: converter output may already pass or list misses; fix only conversion misses of block tags in this task.
- [ ] **Step 4:** Delete the `.tpl` files, update `theme.php`/`composer.json`/`CHANGELOG.md`; run the parity checker → `OK`.
- [ ] **Step 5:** Commit `feat: convert O3-Theme templates to Twig (automated baseline)` — this commit is mechanical output and is allowed not to render yet.

### Task 2: Shared smoke harness (shop-ce dev env, not committed to shop-ce)

**Files (theme repo):** Create `build/smoke-urls.txt` (one storefront URL per line: start, category list, product details with variants, search, basket, user/address step, payment, order, thank-you, account pages, contact, content page, newsletter, revocation, 404, manufacturer list, vendor list, compare, wishlist, price alarm, recommendation, review) and `build/smoke.sh`.

**Interfaces:**
- Produces: `build/smoke.sh [base-url]` → for each URL: HTTP status, whether the body contains `Twig\Error` / `Fatal` / `Uncaught` / `exception.tpl` markers, and new lines in `source/log/oxideshop.log` since the run started; exits non-zero on any 5xx, error marker or new `ERROR`/`CRITICAL` log line. Prints a table. Later tasks run it (logged-in pages via a cookie jar created by the script with a fixed test customer it creates through the shop's PHP API and deletes at the end).

- [ ] **Step 1:** Write the script; run it with the Smarty `main` checkout active → all green (baseline proves the harness).
- [ ] **Step 2:** Switch the theme checkout to `twig-port`, clear caches (`docker exec … php bin/oe-console oe:cache:clear`, remove `source/tmp/container_cache.php`), run it → expected: many failures (this is the RED list the next tasks burn down).
- [ ] **Step 3:** Commit the harness to the theme repo (`build/` is already blacklisted from packaging in `composer.json`).

### Tasks 3–7: Area rework (one task each, same pattern)

Each task: run `build/smoke.sh` for its URLs → RED list; fix the area's templates until its URLs are green and look identical to 1.6.5 (compare by loading the same URL with `main` vs `twig-port` — use the shop-ce Playwright browser or screenshots via the Playwright suite's helpers; differences must be explained); run `build/check-block-parity.php`; commit `fix(twig): <area>`.

- **Task 3 — Layout and widgets:** `tpl/layout/**`, `tpl/widget/**` (header, footer, minibasket, sidebar, locator, product boxes, reviews, address, shoplupe), `tpl/message/**`. Includes `oxid_include_widget` → `include_widget(...)`, `oxid_include_dynamic` → `include_dynamic`, `[{capture append=…}]` → `{% capture append=… %}`, `oxscript`/`oxstyle` → `script()`/`style()` with the same ordering (Review Focus 3, 4). URLs: start page (layout/widgets render), any 404.
- **Task 4 — Catalogue pages:** `tpl/page/shop/**`, `tpl/page/list/**`, `tpl/page/details/**`, `tpl/page/search/**` (if present), `tpl/rdfa/**`, compare/manufacturer/vendor/recommendation/review/privatesales pages. Long descriptions and EU guarantee labels (`[{assign var="oGuaranteeArticle" value=$basketitem->getArticle()}]` patterns) must keep working.
- **Task 5 — Basket and checkout:** `tpl/page/checkout/**` (basket, user, payment, order, thankyou) and `tpl/form/**` used there. A full order must go through on the Twig theme (smoke script places one with the test customer and payment `oxidpayadvance`, shipping `oxidstandard`, customer in groups `oxidnewcustomer`/`oxidcustomer`).
- **Task 6 — Account, forms, info pages:** `tpl/page/account/**`, remaining `tpl/form/**` and `tpl/form/fieldset/**`, `tpl/page/info/**`, `tpl/page/revocation/**`, contact (incl. captcha blocks), newsletter, wishlist, price alarm, forgot password.
- **Task 7 — E-mails:** `tpl/email/html/**` and `tpl/email/plain/**`. Verify by triggering the mails (order, register, forgot password, newsletter opt-in, contact, price alarm owner, stock reminder, wishlist, invite/suggest, shipped-now and download links from admin) and reading them in Mailpit (`http://localhost:8025/api/v1/messages`): HTML escaped correctly, plain mails without HTML entities, subjects single line (Review Focus 5).

### Task 8: Escaping audit and module-extension parity

**Files (theme repo):** templates as needed.

- [ ] **Step 1:** Grep all `|raw` uses; each must be on a value from the Global Constraints list; remove the rest. Grep translations used with `|raw`: only keys whose value contains HTML in `source/Application/translations/{de,en}/lang.php` or the theme's `translations/` (record the list in the commit message).
- [ ] **Step 2:** Inject markers into user-controllable fields (search term `<i>x</i>`, address name, review text, wishlist note) on a test customer and confirm they render escaped on every page that shows them (Review Focus 1).
- [ ] **Step 3:** Module extension smoke: create (in the scratchpad, symlinked into `source/modules/` for the run only) a throwaway module with `views/twig/extensions/themes/o3-theme/layout/base.html.twig` and `.../page/details/inc/productmain.html.twig` overriding one block each, activate it, confirm both blocks render on start and details, deactivate and remove it.
- [ ] **Step 4:** `build/check-block-parity.php` → `OK`; full `build/smoke.sh` → all green. Commit `fix(twig): escaping audit`.

### Task 9: Playwright suite on the Twig theme, v2 dev default, PR

**Files:**
- Shop-ce (branch `b-2.0-twig-support`): `docker/entrypoint.sh` (`install_o3_theme`: clone o3-Theme at the v2 line — branch from config, default the 2.x branch decided by Nick, see handoff), `tests/Acceptance/playwright/**` only where a spec asserts Smarty-specific markup that legitimately changed (document each).
- Theme repo: none new.

- [ ] **Step 1:** With the Twig `o3-theme` active, run the shop-ce Playwright suite (`cd tests/Acceptance/playwright && SHOP_URL=http://localhost:8080 npx playwright test`) → all green, or each failure fixed in the theme (preferred) or justified in the spec file.
- [ ] **Step 2:** Run shop-ce `./docker.sh test-all` with the Twig theme checked out → green.
- [ ] **Step 3:** Shop-ce: make the v2 dev setup install the 2.x theme line (entrypoint), commit on `b-2.0-twig-support`; theme repo: final CHANGELOG, `README.md` note (Twig theme, requires O3-Shop 2.0).
- [ ] **Step 4 (outward — controller asks Nick first):** push `twig-port` to `o3-shop/o3-Theme`, open a draft PR (target per Nick's decision) assigned to `nlo-tronet`; push the shop-ce commit to PR #279.
