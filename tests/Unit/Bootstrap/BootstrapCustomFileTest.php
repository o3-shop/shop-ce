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
 * Tests the optional bootstrap.custom.php load guard added to source/bootstrap.php.
 *
 * Mirrors the exact conditional in source/bootstrap.php in isolation against a
 * temp directory, the same way BootstrapTmpDirTest mirrors the tmp-dir logic.
 *
 * @see source/bootstrap.php
 */
class BootstrapCustomFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/o3shop_bootstrap_custom_test_' . uniqid('', true);
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    /**
     * When no bootstrap.custom.php is present, the guard must not fire and must
     * not raise any warning/error.
     */
    public function testAbsentCustomFileIsSkippedWithoutWarning(): void
    {
        $loaded = $this->runCustomBootstrapLoadLogic($this->tempDir, $sideEffect);

        $this->assertFalse($loaded, 'Guard must not fire when bootstrap.custom.php is absent.');
        $this->assertNull($sideEffect, 'No custom file means no side effect.');
    }

    /**
     * When bootstrap.custom.php is present, it must be executed.
     */
    public function testPresentCustomFileIsExecuted(): void
    {
        file_put_contents(
            $this->tempDir . DIRECTORY_SEPARATOR . 'bootstrap.custom.php',
            "<?php \$sideEffect = 'executed';\n"
        );

        $loaded = $this->runCustomBootstrapLoadLogic($this->tempDir, $sideEffect);

        $this->assertTrue($loaded, 'Guard must fire when bootstrap.custom.php is present.');
        $this->assertSame('executed', $sideEffect, 'Custom file must run and set its side effect.');
    }

    /**
     * Mirrors the exact guard in source/bootstrap.php:
     *   if (is_readable(OX_BASE_PATH . 'bootstrap.custom.php')) {
     *       require OX_BASE_PATH . 'bootstrap.custom.php';
     *   }
     *
     * $sideEffect is passed by reference so a required file can be observed to
     * have run (it shares this method's local scope, exactly as the required
     * file shares bootstrap.php's scope).
     */
    private function runCustomBootstrapLoadLogic(string $baseDir, &$sideEffect): bool
    {
        $sideEffect = null;
        $customFile = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bootstrap.custom.php';
        if (is_readable($customFile)) {
            require $customFile;
            return true;
        }
        return false;
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
