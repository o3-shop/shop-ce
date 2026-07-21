---
name: paratest-parallel-runner
description: ParaTest parallel test runner — opt-in for ./docker.sh test, DEFAULT for test-all-coverage (parallel + serial parallel-unsafe group, merged into one report)
type: project
---

# ParaTest parallel test runner

`./docker.sh test --parallel [-p N]` runs `tests/Unit` in parallel with
`brianium/paratest` (WrapperRunner). For plain `test`, sequential phpunit stays the
DEFAULT (opt in with `--parallel`).

`./docker.sh test-all-coverage` runs in PARALLEL BY DEFAULT and produces ONE merged
coverage report (see "Parallel coverage" below). `--sequential` forces the old
single-process coverage path.

## How isolation works (the whole trick)
`source/config.inc.php` loads `.env` via `Dotenv::createImmutable()`, which never
overwrites an env var already set in the process. So the per-worker bootstrap
`tests/paratest_bootstrap.php` sets, keyed by ParaTest's `TEST_TOKEN` (1..N):
- `O3SHOP_CONF_DBNAME=<base>_<token>` — own database (base = `o3shop-test`)
- `O3SHOP_CONF_COMPILEDIR=source/tmp/paratest_<token>` — own DI/Smarty cache
- `TMP_PATH=/tmp/oxid_test_library_<token>/` — own testing-lib scratch dir
- `O3SHOP_TEST_CONFIGURATION_DIR=var/configuration_<token>` — own project config

Each worker's bootstrap then **installs the shop fresh into its own empty DB**
(~3-4s, once per persistent worker), so workers never share DB/config state.
When `TEST_TOKEN` is unset the bootstrap behaves like the stock
`vendor/o3-shop/testing-library/bootstrap.php`.

## Non-obvious blockers found + how they're handled
- **`var/configuration` is shared & hardcoded.** Both the shop (`BasicContext`)
  and the testing-lib (`ProjectConfigurationHelper`) build it from
  `Facts::getShopRootPath()` (always `/var/www/html`), and every test class'
  `setUpBeforeClass()/tearDownAfterClass()` backs it up to `…-backup` and
  restores it → workers race and corrupt each other. Fixed by an env override
  (`O3SHOP_TEST_CONFIGURATION_DIR`): `BasicContext` was patched (guarded, no prod
  impact); the vendored `ProjectConfigurationHelper` is patched at runtime by the
  runner via `tests/bin/apply-parallel-patches.php` (idempotent — same pattern as
  CI's `--skip-ssl` sed). NOTE the helper must return the path **without** a
  trailing slash (the handler appends `-backup` by raw string concat).
- **UNC regeneration races.** `TestConfig::prepareUnifiedNamespaceClasses()` does
  a delete-then-regenerate of the shared
  `vendor/o3-shop/shop-unified-namespace-generator/generated` dir. The runner
  regenerates it ONCE up front; the per-worker bootstrap skips it.
- **testing-lib file stripping** (see [[symfony5-migration-b2.0]]): a test wipes
  every `*.php` at the testing-library root (base.php etc.) because base.php
  `chdir()`s there and a cache-clear globs the CWD. This breaks the NEXT run of
  ANY kind. `run-tests.sh` now self-heals via `ensure_testlib_files()` (restores
  from `tests/.testlib-cache/`, or reinstalls) at the start of every run.

## Parallel coverage (test-all-coverage) — determinism via @group parallel-unsafe
Coverage driver here is **xdebug** (slow, memory-heavy). The order-coupled tests
must NOT be gambled in parallel, so they are tagged `@group parallel-unsafe` and
the run is split:
1. `paratest --exclude-group parallel-unsafe --coverage-php coverage/_parallel.cov`
   (ParaTest aggregates all workers into one .cov).
2. `phpunit --group parallel-unsafe --coverage-php coverage/_serial.cov` (single
   process, so the coupled tests get a stable order).
3. `tests/bin/merge-coverage.php` merges both `.cov` (via sebastian/code-coverage —
   no phpcov needed, avoids touching the PHP-7.4-pinned lock) into the final
   `coverage/coverage.xml` (clover, read by the gate) + `coverage/html/` +
   `coverage/junit.xml` (the two JUnit logs concatenated).

The per-worker/serial coverage runs use a temp config (derived from phpunit.xml)
with `includeUncoveredFiles=false` + `processUncoveredFiles=false`; uncovered files
are added ONCE in the merge step via the LIGHT static-analysis path
(`CodeCoverage::includeUncoveredFiles()` -> `addUncoveredFilesFromFilter()`, which
uses `analyser()`, no xdebug driver). merge-coverage.php rebuilds the coverage
filter from phpunit.xml's `<coverage>` so the denominator is unchanged
(subset % 33.42 vs 33.46 before — equal).

NON-OBVIOUS: `--coverage-php` (`Report\PHP`) just `serialize($coverage)` — it does
NOT call getData()/getReport(), so it NEVER processes uncovered files. Thus the
feared "per-worker whole-tree rescan" never actually happened on this path;
uncovered processing only ever ran once, at merge. Net effect of the config change
on subset wall-clock is ~neutral (2m57s -> 2m48s). The real coverage-time cost is
xdebug instrumentation of test execution (~1m52s for 2316 tests, -p4) + the
merge-time HTML/uncovered pass. The big lever would be pcov instead of xdebug.

The tagged tests are green in ISOLATION only after fixing static/state leaks —
tag alone is not enough. Fixed so far: `SeoEncoderCategoryTest::testAncoding...`
depended on `SeoEncoder::$_sPrefix` (a STATIC, defaults to 'o3' on a clean shop)
being leaked as 'oxid' by `Core/SeoEncoderTest`; the test now calls
`setPrefix('oxid')` itself. Tagged (class- or method-level): `SeoEncoderCategoryTest`
(method), `RssfeedTest` (method), `ArticleMainTest::testCopyCategories` (method),
`PluginSmartyOxPriceTest`, `VoucherExcludeTest`, `EmailUtf8Test` (class). NOTE
`phpunit.xml` has `stopOnFailure="true"`, so ONE red tagged test halts the whole
serial pass — keep them green in isolation.

## Timing (this container, 6 CPUs)
- `tests/Unit/Core` (2666 tests, no cov): sequential 36s → parallel -p4 25s.
- full `tests/Unit` (9794 tests, no cov): sequential 122s → parallel -p4 ~52s (~2.3x).
- coverage subset `tests/Unit/Application/Model` -p4: parallel 2316 + serial 29
  tests, merged = 33.46% line cov (that subset only), 2m57s wall.

## Worker count
Container has 6 cores. Default is min(4, cpu-2)=4; override with `-p N`. For
xdebug coverage 4 is the safer default (each worker installs a shop + holds a full
CodeCoverage object; 6 roughly doubles peak RAM for a modest wall-clock gain).

## Plain `test --parallel` still opt-in
Without the group split, full-suite `test --parallel` leaves a small NONDETERMINISTIC
set of failures (the same coupled tests). Coverage handles them via the split above;
plain parallel does not, so it stays opt-in.
