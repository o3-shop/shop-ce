<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

/**
 * ParaTest per-worker bootstrap.
 *
 * ParaTest runs the suite in N parallel PHP worker processes. All ~1060 test
 * files share one test database and a DatabaseRestorer that dumps/restores it
 * between tests, so pointing every worker at the same database would corrupt
 * them. This bootstrap gives each worker its own isolated state, keyed by the
 * ParaTest-provided TEST_TOKEN (an integer, 1..N):
 *
 *   - database    : "<base>_<token>"   (base = O3SHOP_CONF_DBNAME from .env)
 *   - compile dir : source/tmp/paratest_<token>   (Symfony container + Smarty cache)
 *   - temp dir    : /tmp/oxid_test_library_<token> (testing-library scratch/service IO)
 *
 * The redirection works because source/config.inc.php loads the .env via
 * Dotenv::createImmutable(), which never overwrites a variable already present
 * in the process environment. Setting these vars here — before the shop config
 * is read — therefore wins over .env. Each worker's bootstrap then installs the
 * shop into its own (empty) database, so workers never touch each other's data.
 *
 * When TEST_TOKEN is absent (plain phpunit, or the sequential fallback) this
 * file behaves exactly like vendor/o3-shop/testing-library/bootstrap.php.
 *
 * NOTE: Unlike the stock bootstrap, this does NOT call
 * TestConfig::prepareUnifiedNamespaceClasses(). That call does a
 * delete-then-regenerate of the shared UNC output directory
 * (vendor/o3-shop/shop-unified-namespace-generator/generated) which races
 * across parallel workers. The runner (run-tests.sh) regenerates the UNC
 * classes exactly once, up front, before launching the workers.
 */

$token = getenv('TEST_TOKEN');
$isWorker = ($token !== false && $token !== '');

if ($isWorker) {
    $baseDb = getenv('O3SHOP_CONF_DBNAME');
    if ($baseDb === false || $baseDb === '') {
        $baseDb = 'o3shop-test';
    }
    $workerDb = $baseDb . '_' . $token;

    putenv('O3SHOP_CONF_DBNAME=' . $workerDb);
    $_ENV['O3SHOP_CONF_DBNAME'] = $workerDb;
    $_SERVER['O3SHOP_CONF_DBNAME'] = $workerDb;

    // Repo root — /var/www/html under docker, the checkout dir under CI. Derived
    // from this file's location (tests/paratest_bootstrap.php) so the per-worker
    // dirs resolve correctly in both environments.
    $baseDir = dirname(__DIR__);

    $compileDir = $baseDir . '/source/tmp/paratest_' . $token;
    if (!is_dir($compileDir)) {
        @mkdir($compileDir, 0777, true);
    }
    @mkdir($compileDir . '/smarty', 0777, true);
    putenv('O3SHOP_CONF_COMPILEDIR=' . $compileDir);
    $_ENV['O3SHOP_CONF_COMPILEDIR'] = $compileDir;
    $_SERVER['O3SHOP_CONF_COMPILEDIR'] = $compileDir;

    // testing-library reads this via TestConfig::getValue('tmp_path') -> env TMP_PATH.
    $tmpPath = '/tmp/oxid_test_library_' . $token . '/';
    if (!is_dir($tmpPath)) {
        @mkdir($tmpPath, 0777, true);
    }
    putenv('TMP_PATH=' . $tmpPath);
    $_ENV['TMP_PATH'] = $tmpPath;
    $_SERVER['TMP_PATH'] = $tmpPath;

    // Isolate the project configuration directory (var/configuration/shops/*.yaml).
    // Both the shop (BasicContext) and the testing-library
    // (ProjectConfigurationHelper) honour this env var; the runner seeds one
    // populated copy per worker before launch. Without this, every test class'
    // setUpBeforeClass()/tearDownAfterClass() backup+restore of the single shared
    // var/configuration directory races across workers.
    $projectConfigDir = $baseDir . '/var/configuration_' . $token;
    if (!is_dir($projectConfigDir)) {
        @mkdir($projectConfigDir . '/shops', 0777, true);
    }
    putenv('O3SHOP_TEST_CONFIGURATION_DIR=' . $projectConfigDir);
    $_ENV['O3SHOP_TEST_CONFIGURATION_DIR'] = $projectConfigDir;
    $_SERVER['O3SHOP_TEST_CONFIGURATION_DIR'] = $projectConfigDir;
}

$testLibraryDir = dirname(__DIR__) . '/vendor/o3-shop/testing-library';

require_once $testLibraryDir . '/base.php';
require_once TEST_LIBRARY_PATH . 'Deprecated.php';

if (!$isWorker) {
    // Sequential / plain-phpunit path: keep the stock behaviour, including the
    // UNC regeneration guard the shop relies on.
    \OxidEsales\TestingLibrary\TestConfig::prepareUnifiedNamespaceClasses();
}

if (!defined('OXID_PHP_UNIT')) {
    define('OXID_PHP_UNIT', true);
}

$bootstrap = new OxidEsales\TestingLibrary\Bootstrap\UnitBootstrap();
$bootstrap->init();

if ($isWorker) {
    // Capture the DB restore baseline NOW — right after the fresh shop install
    // and BEFORE any test runs. The testing-library otherwise captures it lazily
    // in the FIRST UnitTestCase (setUpBeforeTestSuite -> backupDatabase). In a
    // WrapperRunner worker any test that runs before that first UnitTestCase
    // (e.g. a plain PHPUnit\Framework\TestCase, or a test that mutates shared
    // demo data) would poison the baseline, so every later per-class restore
    // restores to a polluted shop — the source of nondeterministic "article
    // 1126 not available" / missing-row stragglers. Dumping here guarantees a
    // pristine baseline; the patched UnitTestCase::backupDatabase() then skips
    // re-dumping (see tests/bin/apply-parallel-patches.php).
    try {
        $factory = new \OxidEsales\TestingLibrary\Services\Library\DatabaseRestorer\DatabaseRestorerFactory();
        $restorer = $factory->createRestorer('DatabaseRestorer');
        $restorer->dumpDB('test');

        $restoreProperty = new \ReflectionProperty(\OxidEsales\TestingLibrary\UnitTestCase::class, 'dbRestore');
        $restoreProperty->setAccessible(true);
        $restoreProperty->setValue(null, $restorer);

        putenv('O3SHOP_BASELINE_CAPTURED=1');
    } catch (\Throwable $e) {
        // Best effort: if the pristine capture fails, fall back to the stock
        // lazy behaviour rather than breaking the worker.
        putenv('O3SHOP_BASELINE_CAPTURED');
    }
}
