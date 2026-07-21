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

$target = dirname(__DIR__, 2)
    . '/vendor/o3-shop/testing-library/library/Helper/ProjectConfigurationHelper.php';

if (!is_file($target)) {
    fwrite(STDERR, "apply-parallel-patches: target not found: $target\n");
    exit(1);
}

$contents = file_get_contents($target);

if (strpos($contents, 'O3SHOP_TEST_CONFIGURATION_DIR') !== false) {
    echo "apply-parallel-patches: ProjectConfigurationHelper already patched.\n";
    exit(0);
}

$search = <<<'PHP'
    public function getConfigurationDirectoryPath(): string
    {
PHP;

$inject = <<<'PHP'
    public function getConfigurationDirectoryPath(): string
    {
        $testConfigurationDir = getenv('O3SHOP_TEST_CONFIGURATION_DIR');
        if ($testConfigurationDir !== false && $testConfigurationDir !== '') {
            // No trailing slash: the handler appends '-backup' directly to build
            // the sibling backup directory path (…/var/configuration_N-backup).
            return rtrim($testConfigurationDir, '/');
        }

PHP;

if (strpos($contents, $search) === false) {
    fwrite(STDERR, "apply-parallel-patches: anchor method not found; upstream layout changed.\n");
    exit(1);
}

$patched = str_replace($search, $inject, $contents);

if (file_put_contents($target, $patched) === false) {
    fwrite(STDERR, "apply-parallel-patches: failed to write $target\n");
    exit(1);
}

echo "apply-parallel-patches: ProjectConfigurationHelper patched for per-worker config isolation.\n";
exit(0);
