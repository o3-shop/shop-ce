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

## Central state reset (ParallelStateResetExtension)
`tests/Support/ParallelStateResetExtension.php` (PHPUnit 9 BeforeTestHook, registered
in `tests/phpunit.xml <extensions>`) resets process-global STATIC caches before each
test — these survive DatabaseRestorer + UnitTestCase::tearDown and leak between test
classes in a WrapperRunner worker:
- `Config::$_oActCurrencyObject` (+ session 'currency'=0) — leaked non-default
  currency changed rate/decimal/thousands separators (ArticleTest::testApplyCurrency
  100 vs 91.9, SelectlistTest € vs CHF, the Smarty price/number-format tests).
- `UtilsView::$_oSmarty` — a Smarty built without block-plugin dirs caused
  EmailUtf8Test "unrecognized tag oxcontent".
GUARDED to ParaTest workers (`getenv('TEST_TOKEN') !== false`) so sequential runs —
the default and the coverage gate serial pass — are byte-for-byte unchanged.
DO NOT also reset `SeoEncoder::$_sPrefix/$_sSeparator` centrally: nulling them
mid-suite re-inits the encoder and corrupts in-flight SEO URLs (regressed
ArticleTest::testGetLinkSeoEng). The one prefix victim fixes itself (setPrefix).

### Measured effect (full `tests/Unit`, `--parallel -p6 --all-failures`, 2 runs)
Before: ~1-6 nondeterministic stragglers/run (currency/smarty/SEO family).
After: run 1 = **0 failures**, run 2 = **1 failure**, ~1m0-1m3s wall. The central
reset cleared the entire currency/smarty family (0 occurrences in either run). The
lone residual, `UtilsobjectTest::testOxNewClassExtendingWhenClassesDoesNotExists`, is
a DIFFERENT category (oxNew module class-chain) that fails even in whole-class
isolation — it needs a predecessor class from the full order — so tagging it
parallel-unsafe would NOT make the serial group green. Left as a known residual
(don't chase the long tail); a module-config/ModuleVariablesLocator reset would be
the next lever but is high-risk (cf. the SeoEncoder regression).

## Plain `test --parallel` still opt-in
Even with the central reset, plain full-suite `test --parallel` can leave the one
residual straggler above (nondeterministic), so it stays opt-in. Coverage stays
deterministic via the @group parallel-unsafe split.

## Session 2026-07-21 — the REAL blockers (root causes, not order-coupling)
Chasing "24/7 green parallel" surfaced that the dominant failures were NOT test
order-coupling but concrete bugs. Fixed:

1. **CWD-wipe (the worker crash at ~19%, and broke sequential too).**
   `oxUtils::oxResetFileCache()` did `glob($this->getCacheFilePath(null,true).'*')`.
   `getCacheFilePath()` returns `false` when the compile dir is unresolvable
   (empty config OR a non-existent dir → `realpath` false). `false.'*'` == `'*'`,
   so `glob('*')` enumerated the **process CWD** — which `base.php` `chdir()`s into
   (the testing-library satellite) — and `@unlink`ed every top-level file + the
   `vendor` symlink. That wrecked the shared satellite mid-run (crashing other
   parallel workers with "test_config.yml not found") and left the NEXT run (incl.
   sequential) unbootstrappable. NOTE `realpath('')` returns the CWD on Linux (not
   false), so an *empty* compile dir is a second route to the same wipe.
   **Fix (source/Core/Utils.php):** `getCacheFilePath()` returns false for an empty
   compile dir too; `oxResetFileCache()`/`resetLanguageCache()`/`resetMenuCache()`
   bail when the path is false (never `glob('*')`); `_lockFile()` bails on an empty
   path (PHP 8 `fopen('')` throws a ValueError that `@` does NOT suppress).
   The satellite `vendor` symlink (`testing-library/vendor -> ../vendor`, made by
   docker/entrypoint.sh so base.php's 3-levels-up vendor fallback resolves) is what
   the wipe destroyed; if a run dies with base.php requiring
   `/var/www/html/testing-library/vendor/autoload.php`, recreate that symlink and
   `git -C testing-library checkout -- composer.json test_config.yml.dist ...`.

2. **Cold-start global-constant family.** Plain `\PHPUnit\Framework\TestCase` unit
   tests that reference a global constant/class defined only when some other test
   first loads it (Smarty's `SMARTY_PHP_REMOVE` from
   `vendor/o3-shop/smarty/libs/Smarty.class.php`; `Core\Module\Module` for the
   module-id in a log message). They pass when a predecessor loaded it, fail when
   they're the first file in a fresh WrapperRunner worker. **Fix = make the test
   self-contained** (call `class_exists(\Smarty::class)` / `class_exists(Module::class)`
   before use). Fixed: `SmartySecuritySettingsDataProviderTest::testGetSecuritySettings`,
   `UtilsobjectTest::testOxNewClassExtendingWhenClassesDoesNotExists`.

3. **fRound() precision leak (currency family the hook missed).** `oxUtils::fRound()`
   caches the currency precision in the per-instance `$_iCurPrecision` on the Utils
   SINGLETON on first call, then ignores the currency passed on every later call.
   A polluter that rounds a 2-decimal currency poisons
   `LangTest::testFormatsCurrencyUsingSimulatedCurrencyObject` (3-decimal → 10322.326
   formatted as `10#322~330` not `10#322~326`). **Fix:** the central reset extension
   now also nulls `Utils::$_iCurPrecision` before each test.

**`--isolate` (ParaTest Runner, fresh process per file) is unreliable** — its
per-file bootstrap crashes ("Log file empty … PHPUnit process crashed"). Do NOT use
it for enumeration; loop the WrapperRunner suite instead.

STATUS: full parallel run went from "crashes at 19%, cannot complete" to "9795
tests complete in ~54-62s". Enumerating the remaining tail by looping WrapperRunner
to collect every unique straggler, then fixing + proving ≥20 green runs.

4. **The intermittent "cascade" is `-p6` OVERSUBSCRIPTION, not a test bug.** The
   container has 6 cores. Running `-p6` = 6 PHP workers + MariaDB + system all
   fighting for 6 cores → a worker gets starved, hangs, and paratest kills it
   (WorkerCrashedException, exit 255, run balloons to 7–22 min). Observed only at
   `-p6` (≈2 of 9 `-p6` runs); NEVER at `-p4` (0 of several). The crash names
   whichever file the killed worker held (e.g. OnlineVatIdCheckTest) — that's
   COLLATERAL, not the cause (that test fully mocks its SoapClient; no network).
   FIX: use the documented default `-p4` (min(4, cpu-2)) which leaves 2 cores of
   headroom. `-p4` full suite ≈ 52s — still well inside the 40–55s goal. Do NOT
   force `-p6` on a 6-core box.

5. **Pristine DB restore baseline (per worker).** The testing-library captures its
   DatabaseRestorer baseline lazily at the first UnitTestCase; a test running earlier
   in a worker poisons it, so every per-class restore keeps the pollution
   (nondeterministic "article 1126 not available" / missing-demo-row). Fixed: the
   per-worker bootstrap dumps the baseline right after install (pristine), and a
   runtime patch skips the later `backupDatabase()` (env `O3SHOP_BASELINE_CAPTURED`).

6. **MariaDB was tuned for durability, not tests** → intermittent MULTI-MINUTE stalls
   (a whole -p4/-p6 run 13× slower, or a worker hangs 7–22 min then crashes). Root:
   `innodb_buffer_pool_size=128M` (too small for demo DB × N workers → disk thrash) +
   `innodb_flush_log_at_trx_commit=1` (fsync every commit) under the restore-heavy
   parallel load. Fix: `docker/docker-compose.yml` db service now runs with
   `--innodb-buffer-pool-size=1G --innodb-flush-log-at-trx-commit=2`. Stalls gone,
   timing steady ~74s. (Apply live without restart via `SET GLOBAL` of both.)

7. **View-naming cascade (whole-class).** `Language::getLanguageAbbr($id)` returns the
   numeric id (e.g. '0') instead of the abbreviation ('de') when the per-instance
   `$_aLangAbbr` cache is leaked/empty, so `TableViewNameGenerator` builds view names
   like `oxv_oxshops_0` that don't exist (real views are `oxv_oxshops` / `oxv_oxshops_de`)
   → 50-failure ArticleMainTest+VendorTest cascade. Fixed: the parallel reset hook now
   nulls `Language::$_aLangAbbr` before each test.

NOTE the DatabaseRestorer EXCLUDES views (`getDbTables()` unsets `oxv_*`) and cannot
recreate dropped tables/views — so view/table drops by a test are not auto-repaired;
the leaked-abbreviation reset (7) avoids the need to touch views for the known case.

8. **Serial split for the plain gate + the ParaTest --exclude-group gotcha.**
   `run-tests.sh` non-coverage parallel path now mirrors coverage: run everything
   except `@group parallel-unsafe` in parallel, then that group once serially (those
   ~6 tests are inherently order-coupled AND pollute shared state for other workers).
   CRITICAL GOTCHA: **ParaTest honours only the LAST `--exclude-group`.** Passing two
   (`--exclude-group quarantine --exclude-group parallel-unsafe`) silently DROPS the
   quarantine exclusion, so `@group quarantine` tests (e.g.
   `LangIntegrityTest::testNotUsedTranslations`, which scans o3-theme templates and
   is a known-flaky data test) run in parallel and fail ~every run. Fix: ONE
   comma-separated flag: `--exclude-group quarantine,parallel-unsafe` (applied to
   both the plain and coverage parallel paths). PHPUnit (the serial phase) accumulates
   multiple group flags fine — this only bit ParaTest.

9. **xdebug caused the worker SEGFAULTS (exit 139) and slowed runs.** The image
   loads xdebug in `xdebug.mode=coverage` for ALL runs. Under the parallel workers
   this intermittently segfaulted a worker (WorkerCrashedException, exit 139) and
   added ~20s overhead even without collecting coverage. Fix: `run-tests.sh` exports
   `XDEBUG_MODE=off` (xdebug's env override, inherited by ParaTest worker
   subprocesses) for every non-coverage run; coverage runs keep xdebug. Verified:
   `XDEBUG_MODE=off` → "Coverage ✘ disabled". Full `-p4` dropped 85s → ~66s.

10. **The wall-clock bottleneck was virtiofs + no CLI opcache — NOT the DB.**
    Under colima the repo is a virtiofs (FUSE) mount, and `opcache.enable_cli=Off`
    by default, so each ParaTest worker re-read AND recompiled every PHP file from
    the host for all ~2400 of its tests — workers sat blocked in the FUSE
    `request_wait_answer` channel (diagnosis: VM ~70% idle, MySQL idle (0–2 queries),
    iowait ~2%, huge context-switch rate, and `-p6` == `-p4` timing). Fix:
    `run-tests.sh` passes CLI-opcache flags to the workers via ParaTest
    `--passthru-php` (and to the serial phpunit run):
    `-d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -d opcache.memory_consumption=256 -d opcache.max_accelerated_files=30000`.
    Full `-p4` dropped ~62s → ~51s (inside the 40–55s goal). More workers do NOT
    help (virtiofs daemon serialises), so -p4 is the sweet spot.

11. **Leaked language config → numeric view names (root of the ArticleMain/Vendor
    cascade).** `getActiveShopLanguageIds()` reads config params
    `aLanguageParams`/`aLanguages`; OXID's UnitTestCase doesn't fully rebuild Config
    between tests, so a test overriding them leaks it → `getLanguageAbbr(0)` returns
    '0' not 'de' → `oxv_oxshops_0` (nonexistent) → whole-class cascade. The reset
    hook now snapshots those two params once per worker (pristine) and restores them
    before each test (superseding the weaker `_aLangAbbr`-only reset).

STATUS: proving ≥20 consecutive green `-p4` runs (real gate config, ~51s each).
The rare view-naming cascade (`oxv_oxshops_0`: leaked language config makes
`getLanguageAbbr(0)` return '0' not 'de') was likely driven by a quarantine polluter
that shouldn't have been running — expected to vanish with the quarantine fix; the
proof confirms.
