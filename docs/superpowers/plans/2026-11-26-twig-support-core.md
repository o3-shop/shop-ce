# Twig Support (Core) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a Twig 3 engine in the O3-Shop v2 core next to Smarty, chosen per render call by the active storefront theme, with module Twig templates, autoescaping, and admin warnings — part 1 of the design (core only).

**Architecture:** OXID `twig-component` v1.1.0 (GPL-3.0) is adopted into `source/Internal/Framework/Twig/` and moved to Twig 3. The existing single `TemplateEngineInterface` service becomes a `DelegatingTemplateEngine` that asks a `TemplateEngineResolver` on every call: admin mode → Smarty, storefront → the engine the active theme declares (`theme.php` key `templateEngine`, inherited from the parent, default `smarty`). Module templates for Twig come from a `.html.twig` sibling of the mapped `.tpl` and from a template chain ported down from twig-component 2.x.

**Tech Stack:** PHP 7.4–8.2, Symfony 5.4 DI, Twig 3 (PHP-7.4-compatible line), Smarty 2.6 (unchanged), PHPUnit 9, `./docker.sh`.

**Spec:** `docs/superpowers/specs/2026-11-26-twig-support-design.md` (read it first; decisions D1/D2 and "Builder's calls" are binding).

**Not in this plan:** O3-Theme 2.x port, switching the fresh-install default theme, Playwright E2E on a Twig storefront — these need a full Twig theme and go into the part-2 plan. Part 1 proves the Twig path end to end with a fixture theme in integration tests (Task 8).

## Global Constraints

- PHP floor 7.4 (`config.platform.php` = `7.4.33`); no PHP 8 syntax (no constructor promotion, `match`, union types, named args, `readonly`).
- `twig/twig` constraint must resolve on PHP 7.4 (Twig 3.x up to 3.11): use `"twig/twig": "~3.11.0 || ~3.10.0"`-style range that Composer resolves under the 7.4 platform pin; refresh `composer.lock`.
- Every file adopted from OXID keeps `Copyright © OXID eSales AG` and adds the O3 header (pattern: `source/Application/views/o3-theme/theme.php`) plus `Modified for O3-Shop (2026): <one line>`; CHANGELOG gets an entry naming the adopted packages and versions. Never copy from twig-component 8.x.
- Namespace for adopted code: `OxidEsales\EshopCommunity\Internal\Framework\Twig\`.
- Engine names are exactly `'twig'` and `'smarty'`; theme metadata key is exactly `templateEngine`.
- Twig template extension is `html.twig`; Smarty stays `tpl`.
- Admin (admin mode at render time) always renders with Smarty; `UtilsView::parseThroughSmarty()` and its callers stay Smarty.
- Autoescaping ON (`html`) for storefront Twig templates; templates under `email/plain/` render with autoescape off.
- Logging per `CLAUDE.md` (`__METHOD__ . ' - '`, full sentences, quoted values); new services registered in `services.yaml` with `public: true` only if fetched by id.
- Run tests with `./docker.sh test --fast <path>` (one path per call); `./docker.sh cs-fixer` before each commit.

## Review Focus

1. **Twig cache after deploy/clear:** `oe:cache:clear` and `TemplateCacheService::invalidateTemplateCache()` must also empty the Twig cache dir, else changed `.html.twig` files keep rendering stale. → test in Task 1.
2. **`sTheme` points to a theme that no longer exists** (deleted folder): engine resolution must fall back to `smarty` and log a `WARNING`, never fatal. → test in Task 3.
3. **Nested template names** like `page/checkout/basket.tpl` or `widget/minibasket/minibasket.tpl` must resolve to `page/checkout/basket.html.twig` (path kept, only extension swapped), for theme and module templates alike. → tests in Tasks 4 and 5.
4. **Inactive or other-shop modules** with Twig extension files must not change rendering. → test in Task 6.
5. **CMS content already rendered by Smarty** inside a Twig template is HTML and must not be double-escaped, while a view value `<script>` is escaped. → test in Task 7.

---

### Task 1: Adopt twig-component engine core on Twig 3

**Files:**
- Modify: `composer.json`, `composer.lock`, `CHANGELOG.md`
- Create: `source/Internal/Framework/Twig/` ← from OXID-eSales/twig-component tag `v1.1.0`: `TwigEngine.php`, `TwigContext(Interface).php`, `TwigEngineConfiguration(Interface).php`, `TemplateLoaderNameParser.php`, `Bridge/TwigTemplateRendererBridge.php`, `DependencyInjection/Compiler/*`, `Escaper/*`, `Loader/FilesystemLoader.php`, `Resolver/TemplateNameResolver.php`, `services.yaml` (do **not** adopt `Loader/ContentTemplateLoader.php`: CMS stays Smarty)
- Modify: `source/Internal/Framework/services.yaml` (import `Twig/services.yaml`), `source/Internal/Framework/Templating/Cache/TemplateCacheService.php`
- Test: `tests/Unit/Internal/Framework/Twig/` and `tests/Integration/Internal/Framework/Twig/` ← adapted from the tag's `tests/` for the files above; `tests/Integration/Internal/Framework/Templating/Cache/TemplateCacheServiceTest.php`

**Interfaces:**
- Produces: service id `oxid_esales.templating.twig_engine` (class `…\Twig\TwigEngine`, implements `TemplateEngineInterface`, `getDefaultFileExtension(): string` returns `'html.twig'`). It is **not** bound to `TemplateEngineInterface` (Task 4 does the wiring). Twig cache dir = `<sCompileDir>/twig/`.

- [ ] **Step 1:** Fetch the tag sources (`gh api repos/OXID-eSales/twig-component/...?ref=v1.1.0` or a shallow clone into the scratchpad), copy the listed files, rename namespace `OxidEsales\Twig` → `OxidEsales\EshopCommunity\Internal\Framework\Twig`, add licence headers per Global Constraints.
- [ ] **Step 2:** Copy and adapt the matching tests; add `testInvalidateTemplateCacheAlsoClearsTwigCache` to `TemplateCacheServiceTest` (create a file in `<compileDir>/twig/`, call `invalidateTemplateCache()`, assert the dir is empty).
- [ ] **Step 3:** Run `./docker.sh test --fast tests/Unit/Internal/Framework/Twig` → expected FAIL (Twig not installed / Twig 2 APIs).
- [ ] **Step 4:** Add `twig/twig` to `composer.json`, `docker exec … composer update twig/twig` (with `COMPOSER_ROOT_VERSION=dev-b-1.6`), port the code to Twig 3 (namespaced classes, `Twig\Loader\FilesystemLoader::findTemplate(string $name, bool $throw = true)`, escaper registration via `EscaperExtension::setEscaper`), set Twig `cache` to `<sCompileDir>/twig/`, extend `TemplateCacheService::invalidateTemplateCache()` to empty it.
- [ ] **Step 5:** Run `./docker.sh test --fast tests/Unit/Internal/Framework/Twig`, `… tests/Integration/Internal/Framework/Twig`, `… tests/Integration/Internal/Framework/Templating/Cache/TemplateCacheServiceTest.php` → all PASS.
- [ ] **Step 6:** CHANGELOG entry ("Adopted OXID twig-component v1.1.0 (GPL-3.0) into the core, ported to Twig 3"), cs-fixer, commit `feat(twig): adopt twig-component engine core on Twig 3`.

### Task 2: Adopt the Twig extensions, nodes and token parsers

**Files:**
- Create: `source/Internal/Framework/Twig/Extensions/**`, `Node/*`, `TokenParser/*` ← tag `v1.1.0`; register them in `Twig/services.yaml` (tag used by `TwigEnvironmentPass`)
- Test: `tests/Unit/Internal/Framework/Twig/Extensions/**`, `…/Node`, `…/TokenParser` ← adapted from the tag

**Interfaces:**
- Consumes: Task 1 engine + `TwigEnvironmentPass`; the existing `Internal/Transition/Adapter/TemplateLogic/*` helpers (incl. `InsertNewBasketItemLogicTwig`).
- Produces: Twig functions/filters/tags with the same names as twig-component 1.1.0 (`translate`, `format_price`, `include_widget`, `include_dynamic`, `seo_url`, `script`, `style`, `capture`, `ifcontent`, `hasrights`, …).

- [ ] **Step 1:** Copy extensions/nodes/token parsers and their tests (namespace + headers as Task 1).
- [ ] **Step 2:** Run `./docker.sh test --fast tests/Unit/Internal/Framework/Twig/Extensions` → expected FAIL on Twig-2 APIs.
- [ ] **Step 3:** Port to Twig 3: `Twig\TwigFunction`/`TwigFilter`, `Node::compile(Compiler $compiler): void`, token parsers' `parse(Token $token): Node`; drop any extension whose Twig-3 core equivalent exists only if the template-facing name stays identical. Mark helpers that return HTML with `['is_safe' => ['html']]` (`include_widget`, `include_dynamic`, `script`, `style`, `seo_url` when it builds markup, `translate` stays **unsafe**).
- [ ] **Step 4:** Re-run Step 2 + `tests/Unit/Internal/Framework/Twig/Node` + `…/TokenParser` → PASS.
- [ ] **Step 5:** cs-fixer, commit `feat(twig): adopt twig-component extensions on Twig 3`.

### Task 3: Theme template-engine provider

**Files:**
- Create: `source/Internal/Framework/Templating/Engine/TemplateEngineName.php`, `ThemeTemplateEngineProviderInterface.php`, `ThemeTemplateEngineProvider.php`; register in `Templating/services.yaml`
- Test: `tests/Integration/Internal/Framework/Templating/Engine/ThemeTemplateEngineProviderTest.php` with fixture themes under `tests/Integration/Internal/Framework/Templating/Engine/Fixtures/views/{twigtheme,smartytheme,plaintheme,twigchild,smartychildoftwig}/theme.php`

**Interfaces:**
- Produces: `final class TemplateEngineName { public const TWIG = 'twig'; public const SMARTY = 'smarty'; }`; `ThemeTemplateEngineProviderInterface::getEngineName(?string $themeId = null): string` (null = active theme via `Theme::getActiveThemeId()`); `ThemeTemplateEngineProviderInterface::getOwnEngineName(string $themeId): ?string` (the theme's own `templateEngine` value or null).

- [ ] **Step 1:** Write tests: `testExplicitTwig` → `'twig'`; `testExplicitSmarty` → `'smarty'`; `testMissingKeyDefaultsToSmarty` (`plaintheme`) → `'smarty'`; `testChildWithoutKeyInheritsParent` (`twigchild`, parent `twigtheme`) → `'twig'`; `testChildOwnKeyWins` (`smartychildoftwig` declares smarty) → `'smarty'`; `testUnknownThemeFallsBackToSmartyAndLogsWarning` (themeId `does-not-exist`) → `'smarty'` and one logger `warning` call.
- [ ] **Step 2:** Run `./docker.sh test --fast tests/Integration/Internal/Framework/Templating/Engine/ThemeTemplateEngineProviderTest.php` → FAIL (class missing).
- [ ] **Step 3:** Implement using `OxidEsales\Eshop\Core\Theme::load()/getInfo('templateEngine')/getParent()`; views dir injectable for the fixture (constructor arg defaulting to `Config::getViewsDir()`); invalid values are treated as `smarty` with a warning.
- [ ] **Step 4:** Re-run → PASS. cs-fixer, commit `feat(templating): resolve template engine from theme metadata`.

### Task 4: Per-render engine resolution and wiring

**Files:**
- Create: `source/Internal/Framework/Templating/Engine/TemplateEngineResolverInterface.php`, `TemplateEngineResolver.php`, `DelegatingTemplateEngine.php`
- Modify: `source/Internal/Framework/Smarty/services.yaml` (Smarty engine gets id `oxid_esales.templating.smarty_engine`; `TemplateEngineInterface` → `DelegatingTemplateEngine`), `source/Internal/Framework/Smarty/Bridge/SmartyTemplateRendererBridge.php` (inject `LegacySmartyEngineInterface` for `setEngine`/`getEngine`), `source/Internal/Framework/Templating/Resolver/TemplateNameResolver.php` (extension asked per call)
- Test: `tests/Unit/Internal/Framework/Templating/Engine/TemplateEngineResolverTest.php`, `DelegatingTemplateEngineTest.php`; existing `tests/Unit/Internal/Framework/Smarty/**` must stay green

**Interfaces:**
- Consumes: Task 3 provider; `Config::isAdmin()`.
- Produces: `TemplateEngineResolverInterface::getEngine(): TemplateEngineInterface` (evaluated on every call, never cached per request); `DelegatingTemplateEngine implements TemplateEngineInterface` forwarding `render/renderFragment/exists/addGlobal/getGlobals/getDefaultFileExtension` to `getEngine()`. `addGlobal` is applied to **both** engines so a global set before the switch survives it.

- [ ] **Step 1:** Tests: `testAdminModeAlwaysSmarty` (provider says twig, `isAdmin` true → smarty engine); `testStorefrontFollowsTheme` (twig → twig engine, smarty → smarty engine); `testDecisionIsPerCall` (toggle admin mode between two `getEngine()` calls → different engines); `testDelegatesExtension` (`getDefaultFileExtension()` = `'html.twig'` under twig, `'tpl'` under smarty); `testNestedNameKeepsPath` (`TemplateNameResolver` + `LegacyTemplateNameResolver` turn `page/checkout/basket.tpl` into `page/checkout/basket.html.twig` under twig); `testAddGlobalReachesBothEngines`.
- [ ] **Step 2:** Run both new test files → FAIL.
- [ ] **Step 3:** Implement + rewire services; `SmartyTemplateRendererBridge::setEngine($engine)` calls `setSmarty()` on the injected Smarty engine only.
- [ ] **Step 4:** Run the new tests, `tests/Unit/Internal/Framework/Smarty`, and `./docker.sh test` (full unit suite, Smarty theme active) → all PASS.
- [ ] **Step 5:** cs-fixer, commit `feat(templating): choose template engine per render call`.

### Task 5: Module page templates as `.html.twig` siblings

**Files:**
- Modify: `source/Core/Module/ModuleTemplatePathCalculator.php`
- Test: `tests/Unit/Core/Module/ModuleTemplatePathCalculatorTest.php` (extend)

**Interfaces:**
- Produces: `calculateModuleTemplatePath('x.html.twig')` returns `<mapped path of 'x.tpl' with .tpl replaced by .html.twig>` when no `x.html.twig` key exists and that sibling file is readable; theme-specific mappings (`aModuleTemplates[moduleId][themeId]['x.tpl']`) included.

- [ ] **Step 1:** Tests: `testTwigNameFallsBackToTplMappingSibling` (mapping `mymodule_page.tpl` → `my/views/tpl/page.tpl`, file `page.html.twig` exists → that path); `testTwigNameWithNestedPath` (`page/mymodule/page.tpl` mapping); `testTwigSiblingMissingThrows` (only `.tpl` exists → existing "Cannot find template" exception); `testThemeSpecificMappingSibling`.
- [ ] **Step 2:** Run `./docker.sh test --fast tests/Unit/Core/Module/ModuleTemplatePathCalculatorTest.php` → new tests FAIL.
- [ ] **Step 3:** Implement the fallback inside `calculateModuleTemplatePath()` (look up `substr($name, 0, -strlen('.html.twig')) . '.tpl'`, swap the extension of the resolved path, check readability).
- [ ] **Step 4:** Re-run → PASS. cs-fixer, commit `feat(twig): find module page templates as .html.twig siblings`.

### Task 6: Template chain for module extensions (port from twig-component 2.x)

**Files:**
- Create: `source/Internal/Framework/Twig/Resolver/TemplateChain/**`, `source/Internal/Framework/Twig/Resolver/ModulesTemplateDirectoryResolver.php`, `source/Internal/Framework/Twig/Extensions/ExtendsExtension.php` ← ported from OXID-eSales/twig-component tag `v2.0.1` to PHP 7.4 (no constructor promotion/typed props beyond 7.4/`match`), adapted to O3's `ModuleConfiguration`/`ActiveModulesDataProvider`
- Modify: `source/Internal/Framework/Twig/Loader/FilesystemLoader.php` (register `@<moduleId>` namespaces for active modules' `views/twig/`), `Twig/services.yaml`
- Test: `tests/Integration/Internal/Framework/Twig/TemplateChain/TemplateChainTest.php` with fixture modules under `…/Fixtures/modules/{modulea,moduleb,inactivemodule}/views/twig/extensions/themes/{twigtheme,default}/page/start.html.twig`

**Interfaces:**
- Consumes: Task 1 loader/engine, Task 3 active theme id.
- Produces: a theme template `T` is rendered through the chain `active module extensions (theme-specific path, then default) in activation order → theme T`; each extension uses `{% extends parent() %}`-style chaining exactly as 2.x (`ExtendsExtension`); module-own templates addressable as `@<moduleId>/<path>.html.twig`.

- [ ] **Step 1:** Tests: `testNoModuleRendersThemeTemplate`; `testOneModuleOverridesBlock`; `testTwoModulesChainInActivationOrder` (both blocks appear, order A then B); `testInactiveModuleIgnored`; `testModuleOfOtherShopIgnored`; `testThemeSpecificBeatsDefault`; `testModuleOwnTemplateNamespace` (`@modulea/page/own.html.twig` renders).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Port and wire (sorting configuration + validator included only if needed for the ordering test).
- [ ] **Step 4:** Re-run → PASS. cs-fixer, commit `feat(twig): let modules extend theme templates via template chain`.

### Task 7: Autoescaping (D2)

**Files:**
- Modify: `source/Internal/Framework/Twig/TwigEngineConfiguration.php` (autoescape callback), `Twig/services.yaml`
- Test: `tests/Integration/Internal/Framework/Twig/AutoescapeTest.php` with fixture templates

**Interfaces:**
- Produces: autoescape strategy `'html'` for all templates except names under `email/plain/` (strategy `false`).

- [ ] **Step 1:** Tests: `testViewValueIsEscaped` (`{{ value }}` with `<script>x</script>` → `&lt;script&gt;`); `testParsedCmsContentIsNotDoubleEscaped` (value from `UtilsView::parseThroughSmarty('<b>[{ "x" }]</b>')` rendered via `{{ content|raw }}` → `<b>x</b>`); `testPlainMailNotEscaped` (`email/plain/order.html.twig` with `a & b` → `a & b`); `testSafeHelperNotEscaped` (`{{ include_widget(...) }}` markup kept).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement the autoescape callback.
- [ ] **Step 4:** Re-run → PASS. cs-fixer, commit `feat(twig): enable html autoescaping for storefront templates`.

### Task 8: Fixture Twig theme and end-to-end integration

**Files:**
- Create: `tests/Integration/Internal/Framework/Twig/Fixtures/views/twigfixture/` (`theme.php` with `'templateEngine' => 'twig'`, `tpl/layout/base.html.twig`, `tpl/page/shop/start.html.twig`, `tpl/widget/header/minibasket.html.twig`, `tpl/email/html/order_cust.html.twig`, `tpl/email/plain/order_cust.html.twig`), `tests/Integration/Internal/Framework/Twig/StorefrontRenderingTest.php`

**Interfaces:**
- Consumes: Tasks 1–7. Produces: the fixture theme Task 9 uses.

- [ ] **Step 1:** Tests: `testStartPageRendersWithFixtureTheme` (activate fixture theme via config, render `StartController` through `ShopControl`/`TemplateRenderer` → marker text, translated string, formatted price, widget output present); `testSmartyThemeUnaffected` (switch back to `o3-theme` → existing `.tpl` render).
- [ ] **Step 2:** Run → FAIL. **Step 3:** Add fixture templates using only adopted Twig functions. **Step 4:** Run → PASS; then `./docker.sh test-all` → PASS.
- [ ] **Step 5:** cs-fixer, commit `test(twig): fixture Twig theme and storefront rendering`.

### Task 9: E-mails follow the storefront engine

**Files:**
- Modify: `source/Core/Email.php` (`getRenderer()`: call `setEngine($this->_getSmarty())` only when `TemplateEngineResolverInterface::getEngine()` is the Smarty engine)
- Test: `tests/Integration/Core/EmailTwigTest.php` using the Task 8 fixture theme

**Interfaces:**
- Consumes: Task 4 resolver, Task 8 fixture theme (`email/html/order_cust.html.twig`, `email/plain/order_cust.html.twig`).

- [ ] **Step 1:** Tests: `testOrderMailRendersTwigUnderTwigTheme` (fixture theme active → body contains a marker from the `.html.twig`); `testMailSentFromAdminUsesStorefrontEngine` (admin mode on before `sendOrderEmailToUser`, Email switches it off → Twig marker present); `testSmartyThemeStillRendersTpl` (Smarty theme → existing `.tpl` output).
- [ ] **Step 2:** Run → FAIL. **Step 3:** Implement. **Step 4:** Run + `tests/Unit/Core/EmailTest.php` → PASS.
- [ ] **Step 5:** cs-fixer, commit `feat(twig): render e-mails with the storefront theme's engine`.

### Task 10: Admin warnings (modules without Twig, engine mismatch)

**Files:**
- Create: `source/Internal/Framework/Templating/Compatibility/ModuleTwigCompatibilityCheckerInterface.php`, `ModuleTwigCompatibilityChecker.php`, `ThemeEngineMismatchCheckerInterface.php`, `ThemeEngineMismatchChecker.php`; register in `Templating/services.yaml`
- Modify: `source/Application/Controller/Admin/ThemeMain.php` (`render()` + after successful `setTheme()`), `source/Application/Controller/Admin/NavigationController.php` (`_doStartUpChecks()` adds messages), admin lang files `source/Application/views/admin/{de,en}/lang.php`
- Test: `tests/Unit/Internal/Framework/Templating/Compatibility/*Test.php`, `tests/Unit/Application/Controller/Admin/ThemeMainTest.php` + `NavigationControllerTest.php` (extend)

**Interfaces:**
- Produces: `ModuleTwigCompatibilityCheckerInterface::getModulesWithoutTwigTemplates(int $shopId): string[]` (ids of active modules that have storefront Smarty `blocks` or `.tpl` `templates` but no `.html.twig` sibling for those templates and no `views/twig/` dir); `ThemeEngineMismatchCheckerInterface::getMismatch(string $themeId): ?array` returning `['child' => id, 'parent' => id, 'childEngine' => …, 'parentEngine' => …]` only when the child has its **own** `templateEngine` (or own templates in `tpl/`) differing from the parent's engine. Lang keys: `TWIG_MODULES_WITHOUT_TWIG_TEMPLATES` (`%s` = comma list), `THEME_ENGINE_MISMATCH` (`%1$s` child, `%2$s` parent).

- [ ] **Step 1:** Tests: checker returns `['smartyonly']` for an active module with only `blocks`; returns `[]` for a module with `.html.twig` siblings; ignores inactive modules; ignores admin-only templates (`views/admin/`); mismatch reported for a Smarty child of a Twig parent, null for a child without own templates; `ThemeMain` adds the warning to the display when a Twig theme is active and the checker returns ids; activation is **not** blocked; one `WARNING` log per call.
- [ ] **Step 2:** Run → FAIL. **Step 3:** Implement (Twig only checked when `ThemeTemplateEngineProvider::getEngineName()` is `twig`). **Step 4:** Run → PASS.
- [ ] **Step 5:** cs-fixer, commit `feat(twig): warn about modules without Twig templates and theme engine mismatch`.

### Task 11: Documentation and upgrade notes

**Files:**
- Create: `docs/twig.md` (how a theme declares the engine, module Twig templates/extension paths, autoescape rules, admin/CMS stay Smarty)
- Modify: `CHANGELOG.md` (v2 upgrade note per spec 1.6: pin `o3-shop/o3-theme:^1` before updating to keep Smarty), `.claude/memory/` (new memory file + `MEMORY.md` line on the engine resolution)

- [ ] **Step 1:** Write docs; the upgrade note text must say: "To keep a Smarty storefront, pin `o3-shop/o3-theme` to `^1` in your project's `composer.json` *before* updating to O3-Shop 2.0; otherwise O3-Theme is updated to its Twig version."
- [ ] **Step 2:** Run `./docker.sh test-all-coverage` (via `/finish`) → PASS.
- [ ] **Step 3:** Commit `docs(twig): document Twig support and the v2 theme upgrade note`.
