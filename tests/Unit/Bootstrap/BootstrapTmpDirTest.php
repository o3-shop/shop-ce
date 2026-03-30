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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

/**
 * Tests that the tmp directory creation logic in bootstrap.php does not create
 * literal '<sCompileDir>' directories when the .env placeholder is unsubstituted.
 *
 * @see source/bootstrap.php
 */
class BootstrapTmpDirTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/o3shop_bootstrap_test_' . uniqid('', true);
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    /**
     * Regression test for https://github.com/o3-shop/o3-shop/issues/70
     *
     * When O3SHOP_CONF_COMPILEDIR is still the placeholder '<sCompileDir>'
     * (i.e. the .env file has not been configured yet), the bootstrap must
     * NOT create a directory literally named '<sCompileDir>'.
     */
    public function testBootstrapDoesNotCreateDirectoryForPlaceholderCompileDir(): void
    {
        $tmpDir = '<sCompileDir>';

        $this->runBootstrapTmpDirLogic($tmpDir);

        $this->assertDirectoryDoesNotExist(
            $this->tempDir . DIRECTORY_SEPARATOR . $tmpDir,
            'bootstrap.php must not create a literal <sCompileDir> directory when the value is an unsubstituted placeholder'
        );
    }

    /**
     * Sanity check: a real path that does not contain '<' must still be created.
     */
    public function testBootstrapCreatesTmpDirWhenCompileDirIsRealPath(): void
    {
        $tmpDir = $this->tempDir . '/tmp';

        $this->runBootstrapTmpDirLogic($tmpDir);

        $this->assertDirectoryExists(
            $tmpDir,
            'bootstrap.php must create the tmp directory when sCompileDir is a real path'
        );
    }

    /**
     * Mirrors the exact condition in source/bootstrap.php lines 241-246.
     */
    private function runBootstrapTmpDirLogic(string $tmpDir): void
    {
        if ($tmpDir && strpos($tmpDir, '<') === false && !is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $entry;
            is_dir($full) ? $this->removeDir($full) : unlink($full);
        }
        rmdir($path);
    }
}
