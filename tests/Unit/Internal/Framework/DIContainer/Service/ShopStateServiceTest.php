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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\DIContainer\Service;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateService;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;

class ShopStateServiceTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/o3-shopstate-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpDir);
    }

    private function makeContext(string $configPath, string $tableName = 'oxconfig'): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getConfigFilePath')->willReturn($configPath);
        $context->method('getConfigTableName')->willReturn($tableName);
        return $context;
    }

    public function testIsLaunchedReturnsFalseWhenUnifiedNamespacesAreNotGenerated(): void
    {
        // class_exists('NotARealClass\\Nope') is false → short-circuits the
        // && chain to false without touching files or DB.
        $service = new ShopStateService(
            $this->makeContext($this->tmpDir . '/config.inc.php'),
            '\\NotARealClass\\Nope_' . uniqid()
        );
        $this->assertFalse($service->isLaunched());
    }

    public function testIsLaunchedReturnsFalseWhenConfigFileMissing(): void
    {
        // Use a real loaded class (this test class) so the first guard passes.
        // Path is missing → file_exists returns false → result is false.
        $service = new ShopStateService(
            $this->makeContext($this->tmpDir . '/this/path/does/not/exist.php'),
            self::class
        );
        $this->assertFalse($service->isLaunched());
    }

    public function testIsLaunchedReturnsFalseWhenConfigTableQueryThrows(): void
    {
        // Real config file with bogus DB credentials so the PDO connection
        // (or table-existence query) throws — caught and returns false.
        $configPath = $this->tmpDir . '/config.inc.php';
        file_put_contents(
            $configPath,
            '<?php' . "\n"
            . '$this->dbHost = "127.0.0.1";' . "\n"
            . '$this->dbPort = "1";' . "\n"
            . '$this->dbName = "no_such_database_for_o3_unit_test_' . uniqid() . '";' . "\n"
            . '$this->dbUser = "nope";' . "\n"
            . '$this->dbPwd  = "nope";' . "\n"
        );

        $service = new ShopStateService($this->makeContext($configPath), self::class);
        // doesConfigTableExist swallows the PDOException and returns false.
        $this->assertFalse(@$service->isLaunched());
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
