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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\ArrayStorageInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Filesystem\Filesystem;

class ShopEnvironmentConfigurationDaoTest extends TestCase
{
    private function makeContext(string $projectDir = '/tmp/shop-config/'): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getProjectConfigurationDirectory')->willReturn($projectDir);
        return $context;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::get
     */
    public function testGetReturnsEmptyArrayWhenConfigurationFileMissing(): void
    {
        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $factory->expects($this->never())->method('create');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(false);

        $node = $this->createMock(NodeInterface::class);
        $node->expects($this->never())->method('normalize');

        $dao = new ShopEnvironmentConfigurationDao($factory, $filesystem, $node, $this->makeContext());

        $this->assertSame([], $dao->get(1));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::get
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::getEnvironmentConfigurationFilePath
     */
    public function testGetReadsFromFileStorageWhenFileExists(): void
    {
        $expectedPath = '/tmp/shop-config/environment/1.yaml';
        $rawData = ['modules' => ['mod' => ['settings' => []]]];
        $normalised = ['modules' => ['mod' => ['settings' => []]]];

        $storage = $this->createMock(ArrayStorageInterface::class);
        $storage->expects($this->once())->method('get')->willReturn($rawData);

        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $factory->expects($this->once())
            ->method('create')
            ->with($expectedPath)
            ->willReturn($storage);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('exists')->with($expectedPath)->willReturn(true);

        $node = $this->createMock(NodeInterface::class);
        $node->expects($this->once())->method('normalize')->with($rawData)->willReturn($normalised);

        $dao = new ShopEnvironmentConfigurationDao($factory, $filesystem, $node, $this->makeContext());

        $this->assertSame($normalised, $dao->get(1));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::get
     */
    public function testGetWrapsInvalidConfigurationExceptionWithFilePathContext(): void
    {
        $expectedPath = '/tmp/shop-config/environment/7.yaml';

        $storage = $this->createMock(ArrayStorageInterface::class);
        $storage->method('get')->willReturn(['modules' => 'not an array']);

        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $factory->method('create')->willReturn($storage);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);

        $node = $this->createMock(NodeInterface::class);
        $node->method('normalize')->willThrowException(
            new InvalidConfigurationException('expected array, got string')
        );

        $dao = new ShopEnvironmentConfigurationDao($factory, $filesystem, $node, $this->makeContext());

        try {
            $dao->get(7);
            $this->fail('expected InvalidConfigurationException to be re-thrown');
        } catch (InvalidConfigurationException $exception) {
            $this->assertStringContainsString($expectedPath, $exception->getMessage());
            $this->assertStringContainsString('is broken', $exception->getMessage());
            $this->assertStringContainsString('expected array, got string', $exception->getMessage());
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::remove
     */
    public function testRemoveBackupsExistingFileToBakSibling(): void
    {
        $expectedPath = '/tmp/shop-config/environment/1.yaml';

        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $node = $this->createMock(NodeInterface::class);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('exists')->with($expectedPath)->willReturn(true);
        $filesystem->expects($this->once())
            ->method('rename')
            ->with($expectedPath, $expectedPath . '.bak', true);

        $dao = new ShopEnvironmentConfigurationDao($factory, $filesystem, $node, $this->makeContext());
        $dao->remove(1);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::remove
     */
    public function testRemoveIsNoopWhenFileDoesNotExist(): void
    {
        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $node = $this->createMock(NodeInterface::class);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(false);
        $filesystem->expects($this->never())->method('rename');

        $dao = new ShopEnvironmentConfigurationDao($factory, $filesystem, $node, $this->makeContext());
        $dao->remove(1);
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDao::getEnvironmentConfigurationFilePath
     */
    public function testEnvironmentConfigurationPathFollowsContextDirAndShopIdConvention(): void
    {
        // Indirect assertion: get() with shopId = 42 must call exists() with the
        // expected path under the configured project dir.
        $factory = $this->createMock(FileStorageFactoryInterface::class);
        $node = $this->createMock(NodeInterface::class);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->with('/srv/proj-cfg/environment/42.yaml')
            ->willReturn(false);

        $dao = new ShopEnvironmentConfigurationDao(
            $factory,
            $filesystem,
            $node,
            $this->makeContext('/srv/proj-cfg/')
        );
        $this->assertSame([], $dao->get(42));
    }
}
