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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Migration\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\ComposerLockInspector;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;

final class ComposerLockInspectorTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = sys_get_temp_dir() . '/o3-composer-lock-' . uniqid('', true);
        mkdir($this->rootPath, 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->rootPath . '/composer.lock');
        @rmdir($this->rootPath);
        parent::tearDown();
    }

    private function writeLock(array $lock): void
    {
        file_put_contents($this->rootPath . '/composer.lock', json_encode($lock));
    }

    private function makeInspector(): ComposerLockInspector
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getShopRootPath')->willReturn($this->rootPath);

        return new ComposerLockInspector($context);
    }

    public function testExistsIsFalseWhenNoLockFile(): void
    {
        $inspector = $this->makeInspector();

        $this->assertFalse($inspector->exists());
        $this->assertSame([], $inspector->findInstalledPackagesByPrefix('oxid-esales/'));
    }

    public function testFindsLeftoverUpstreamPackagesAcrossBothSections(): void
    {
        $this->writeLock([
            'packages' => [
                ['name' => 'o3-shop/shop-ce'],
                ['name' => 'oxid-esales/oxideshop-ce'],
            ],
            'packages-dev' => [
                ['name' => 'oxid-esales/testing-library'],
                ['name' => 'phpunit/phpunit'],
            ],
        ]);
        $inspector = $this->makeInspector();

        $this->assertTrue($inspector->exists());
        $this->assertSame(
            ['oxid-esales/oxideshop-ce', 'oxid-esales/testing-library'],
            $inspector->findInstalledPackagesByPrefix('oxid-esales/')
        );
    }

    public function testReturnsEmptyWhenSwapIsClean(): void
    {
        $this->writeLock([
            'packages' => [
                ['name' => 'o3-shop/shop-ce'],
                ['name' => 'o3-shop/shop-facts'],
            ],
        ]);
        $inspector = $this->makeInspector();

        $this->assertSame([], $inspector->findInstalledPackagesByPrefix('oxid-esales/'));
    }

    public function testInvalidJsonYieldsNoPackages(): void
    {
        file_put_contents($this->rootPath . '/composer.lock', 'not-json');
        $inspector = $this->makeInspector();

        $this->assertTrue($inspector->exists());
        $this->assertSame([], $inspector->findInstalledPackagesByPrefix('oxid-esales/'));
    }
}
