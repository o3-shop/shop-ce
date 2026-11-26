---
name: Testing Patterns
description: PHPUnit setup, mocking with Prophecy, test structure and naming conventions
type: reference
---

## Running Tests
- Single file (fast): `./docker.sh test --fast tests/Unit/Path/To/ClassTest.php`
- Full suite: `./docker.sh test`
- With coverage: `./docker.sh test --coverage tests/Unit`
- Quarantine (slow): `./docker.sh quarantine`

## Test Structure
- Unit tests: `tests/Unit/` — mirror `source/` directory structure
- Integration tests: `tests/Integration/`
- Acceptance tests: `tests/Acceptance/` — Selenium-based, requires full stack
- Test class naming: `{ClassName}Test` in same sub-namespace as class under test

## Mocking
- PHPSpec Prophecy (`phpspec/prophecy-phpunit`)
- Use `$this->prophesize(SomeClass::class)` — not PHPUnit's built-in mocks
- Reveal the mock: `$mock->reveal()`
- Exception: legacy `\OxidTestCase`-based tests (e.g. `tests/Unit/Core/*`) use PHPUnit's built-in
  mocks (`getMockBuilder(...)->onlyMethods([...])`). Match the style already in the file.

## Calling protected/`_`-prefixed methods from tests
- `Core\Base::__call` (active when `OXID_PHP_UNIT` is defined) maps a `UNIT`-prefixed call to the
  underscore method: `$obj->UNITcheckModRewrite($x)` invokes the protected `_checkModRewrite($x)`.
- To unit-test the logic of a protected method that does I/O, extract the I/O into its own protected
  `_`-method, then partial-mock that seam: `getMockBuilder(Cls)->onlyMethods(['_doIo'])` and stub it,
  while calling the method under test via its `UNIT...` alias so the real logic runs.

## Bootstrap
- Fast mode uses `vendor/o3-shop/testing-library/bootstrap.php`
- DB is switched to `o3shop-test` automatically by `run-tests.sh` — never touch production DB in tests

## Groups
- Tag slow/special tests with `@group quarantine`
- All other tests run by default (quarantine group excluded)

## Coverage
- Output: `coverage/coverage.xml` (Clover), `coverage/html/` (HTML), `coverage/junit.xml`
- View HTML report: open `coverage/html/index.html` in a browser

## Twig template chain tests (2026-11-26)
- `ModuleActivationBridge::activate()` reloads the shop config: in-memory `Registry::getConfig()->setConfigParam('sTheme', …)` set before activation is lost — set it after activating (see `TemplateChainTest::createTwigEngine`).
- Compiled Twig template classes stay loaded in the PHP process (class name = hash of the loader cache key), so clearing the twig cache dir between tests is not enough. Anything that changes what a template compiles to (the template chain parent) must be part of `FilesystemLoader::getCacheKey()`.
- A fresh engine per scenario: `(new ContainerBuilderFactory())->create()->getContainer()`, tweak definitions (e.g. replace `twig.loader.filesystem` arg 0 with a fixture theme dir), `compile()`, then `get('oxid_esales.templating.twig_engine')`.

## Rendering a storefront controller end to end (2026-11-26, see `Integration/.../Twig/StorefrontRenderingTest`)
- `./docker.sh test-all` runs only `tests/Unit`; Integration tests must be run explicitly (`./docker.sh test --fast tests/Integration/...`).
- A fixture theme must live under `Config::getViewsDir()` (`source/Application/views/`): symlink it in setUp, `is_link`+`unlink` in tearDown (and before linking, for stale links). Theme switch = `setConfigParam('sTheme'/'sCustomTheme')` + `ContainerFactory::resetContainer()` + `Registry::set(UtilsView::class, null)` (its template dirs only append) + `invalidateTemplateCache()`.
- Render via a `ShopControl` subclass calling `_initializeViewObject()` + `formOutput()`. `FrontendController::init()` needs `$_SERVER['REQUEST_METHOD']`; widgets (`include_widget`/`oxid_include_widget`) run `WidgetControl::start()` → `_process()` which sends headers → needs `@runTestsInSeparateProcesses` + `@preserveGlobalState disabled`.
- Lowering `error_reporting()` does not help: `ShopControl::_runOnce()` resets it per widget. The o3-theme Smarty start page raises many PHP 8 E_WARNINGs (unset template vars); use a scoped `set_error_handler` that chains to the previous (PHPUnit) handler — returning `false` falls through to PHP's builtin handler, not PHPUnit's.
