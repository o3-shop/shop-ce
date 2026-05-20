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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Command;

use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ClearCacheCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ClearCacheCommandTest extends \OxidTestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/o3-clear-cache-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpDir);
        parent::tearDown();
    }

    public function testCommandNameAndDescription(): void
    {
        $command = new ClearCacheCommand();
        $this->assertSame('oe:cache:clear', $command->getName());
        $this->assertStringContainsString('Clears the application cache', $command->getDescription());
    }

    public function testExecuteEmptiesCacheDirectoryButKeepsStructure(): void
    {
        // Seed the temp dir with files + a subdirectory + a .htaccess.
        file_put_contents($this->tmpDir . '/file1.cache', 'data');
        file_put_contents($this->tmpDir . '/.htaccess', 'deny from all');
        mkdir($this->tmpDir . '/sub');
        file_put_contents($this->tmpDir . '/sub/file2.cache', 'more');

        // Steer the ConfigFile so sCompileDir resolves to our tmp dir.
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->with('sCompileDir')->willReturn($this->tmpDir);
        Registry::set(ConfigFile::class, $configFile);

        $tester = new CommandTester(new ClearCacheCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Clearing cache', $tester->getDisplay());
        $this->assertStringContainsString('Cache cleared successfully', $tester->getDisplay());

        // Files removed.
        $this->assertFileDoesNotExist($this->tmpDir . '/file1.cache');
        $this->assertFileDoesNotExist($this->tmpDir . '/sub/file2.cache');
        // .htaccess preserved.
        $this->assertFileExists($this->tmpDir . '/.htaccess');
        // Subdirectory structure preserved (only contents removed).
        $this->assertDirectoryExists($this->tmpDir . '/sub');
    }

    public function testExecuteIsNoopWhenCompileDirDoesNotExist(): void
    {
        $missing = $this->tmpDir . '/this/path/does/not/exist';
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->with('sCompileDir')->willReturn($missing);
        Registry::set(ConfigFile::class, $configFile);

        $tester = new CommandTester(new ClearCacheCommand());
        // Must complete without error.
        $this->assertSame(0, $tester->execute([]));
    }

    private function rmrf(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->rmrf($path . '/' . $entry);
        }
        rmdir($path);
    }
}
