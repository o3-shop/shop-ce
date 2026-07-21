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
 * Idempotently patches the o3-shop/testing-library so that the project
 * configuration directory (var/configuration) can be redirected per ParaTest
 * worker via the O3SHOP_TEST_CONFIGURATION_DIR environment variable.
 *
 * The testing-library is installed by Composer and has no test seam for this,
 * so — exactly as the CI already does for its MariaDB "--skip-ssl" fix — the
 * test runner patches the vendored file in place before a parallel run. The
 * patch is a no-op when the env var is unset, so it is safe to leave applied.
 *
 * Re-run safe: if the marker is already present, nothing happens.
 *
 * Exit code 0 on success (patched or already patched), 1 on failure.
 */

$vendorTestingLib = dirname(__DIR__, 2) . '/vendor/o3-shop/testing-library';

/**
 * Idempotently replace $search with $inject in $file, keyed by a $marker that
 * is present in $inject. Returns true on success (patched or already patched).
 */
$applyPatch = function (string $file, string $marker, string $search, string $inject, string $label): bool {
    if (!is_file($file)) {
        fwrite(STDERR, "apply-parallel-patches: target not found: $file\n");
        return false;
    }
    $contents = file_get_contents($file);
    if (strpos($contents, $marker) !== false) {
        echo "apply-parallel-patches: $label already patched.\n";
        return true;
    }
    if (strpos($contents, $search) === false) {
        fwrite(STDERR, "apply-parallel-patches: $label anchor not found; upstream layout changed.\n");
        return false;
    }
    if (file_put_contents($file, str_replace($search, $inject, $contents)) === false) {
        fwrite(STDERR, "apply-parallel-patches: failed to write $file\n");
        return false;
    }
    echo "apply-parallel-patches: $label patched.\n";
    return true;
};

// 1) Per-worker project-configuration directory isolation.
$ok = $applyPatch(
    $vendorTestingLib . '/library/Helper/ProjectConfigurationHelper.php',
    'O3SHOP_TEST_CONFIGURATION_DIR',
    <<<'PHP'
    public function getConfigurationDirectoryPath(): string
    {
PHP,
    <<<'PHP'
    public function getConfigurationDirectoryPath(): string
    {
        $testConfigurationDir = getenv('O3SHOP_TEST_CONFIGURATION_DIR');
        if ($testConfigurationDir !== false && $testConfigurationDir !== '') {
            // No trailing slash: the handler appends '-backup' directly to build
            // the sibling backup directory path (…/var/configuration_N-backup).
            return rtrim($testConfigurationDir, '/');
        }

PHP,
    'ProjectConfigurationHelper (per-worker config isolation)'
);

// 2) Pristine DB baseline: skip the lazy first-UnitTestCase dumpDB when the
//    per-worker bootstrap already captured a clean baseline right after install
//    (O3SHOP_BASELINE_CAPTURED=1). Otherwise a test running before the first
//    UnitTestCase can poison the baseline and every per-class restore preserves
//    the pollution (nondeterministic missing-demo-row stragglers).
$ok = $applyPatch(
    $vendorTestingLib . '/library/UnitTestCase.php',
    'O3SHOP_BASELINE_CAPTURED',
    <<<'PHP'
    protected function backupDatabase()
    {
        $oDbRestore = self::_getDbRestore();
        $oDbRestore->dumpDB();
    }
PHP,
    <<<'PHP'
    protected function backupDatabase()
    {
        if (getenv('O3SHOP_BASELINE_CAPTURED') === '1') {
            // A pristine baseline was already captured post-install by the
            // ParaTest per-worker bootstrap; do not overwrite it with a
            // possibly-polluted later snapshot.
            return;
        }
        $oDbRestore = self::_getDbRestore();
        $oDbRestore->dumpDB();
    }
PHP,
    'UnitTestCase::backupDatabase (pristine baseline)'
) && $ok;

exit($ok ? 0 : 1);
