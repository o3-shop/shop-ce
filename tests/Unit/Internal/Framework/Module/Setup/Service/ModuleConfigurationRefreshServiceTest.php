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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Cache\ModuleCacheServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ExtensionChainServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationHandlingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationRefreshService;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleServicesActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ModuleConfigurationRefreshServiceTest extends TestCase
{
    public function testRefreshActiveModuleWithFailuresIsolatesAFailingShop(): void
    {
        $result = $this->createServiceWithFailingShop(2)->refreshActiveModuleWithFailures('mymodule');

        $this->assertSame([1, 3], $result['refreshed']);
        $this->assertSame([2 => 'shop 2 broken'], $result['failures']);
    }

    public function testRefreshInvalidatesTheModuleCache(): void
    {
        $handlingService = $this->createMock(ModuleConfigurationHandlingServiceInterface::class);
        $handlingService->expects($this->once())
            ->method('handleOnRefresh')
            ->with($this->isInstanceOf(ModuleConfiguration::class), 1);

        $moduleServicesActivationService = $this->createMock(ModuleServicesActivationServiceInterface::class);
        $moduleServicesActivationService->expects($this->once())->method('activateModuleServices');
        $classExtensionChainService = $this->createMock(ExtensionChainServiceInterface::class);
        $classExtensionChainService->expects($this->once())->method('updateChain')->with(1);

        $moduleCacheService = $this->createMock(ModuleCacheServiceInterface::class);
        $moduleCacheService->expects($this->once())
            ->method('invalidateModuleCache')
            ->with('mymodule', 1);

        $service = new ModuleConfigurationRefreshService(
            $handlingService,
            $moduleServicesActivationService,
            $classExtensionChainService,
            $this->createMock(ProjectConfigurationDaoInterface::class),
            $this->createMock(ModuleStateServiceInterface::class),
            $moduleCacheService
        );

        $service->refresh((new ModuleConfiguration())->setId('mymodule'), 1);
    }

    private function createServiceWithFailingShop(int $failingShopId): ModuleConfigurationRefreshService
    {
        return $this->createService($failingShopId);
    }

    private function createService(?int $failingShopId = null): ModuleConfigurationRefreshService
    {
        $projectConfiguration = new ProjectConfiguration();
        foreach ([1, 2, 3] as $shopId) {
            $shopConfiguration = new ShopConfiguration();
            $shopConfiguration->addModuleConfiguration((new ModuleConfiguration())->setId('mymodule'));
            $projectConfiguration->addShopConfiguration($shopId, $shopConfiguration);
        }

        $projectConfigurationDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectConfigurationDao->method('getConfiguration')->willReturn($projectConfiguration);

        $moduleStateService = $this->createMock(ModuleStateServiceInterface::class);
        $moduleStateService->method('isActive')->willReturn(true);

        $service = $this->getMockBuilder(ModuleConfigurationRefreshService::class)
            ->setConstructorArgs([
                $this->createMock(ModuleConfigurationHandlingServiceInterface::class),
                $this->createMock(ModuleServicesActivationServiceInterface::class),
                $this->createMock(ExtensionChainServiceInterface::class),
                $projectConfigurationDao,
                $moduleStateService,
                $this->createMock(ModuleCacheServiceInterface::class),
            ])
            ->onlyMethods(['refresh'])
            ->getMock();

        $service->method('refresh')->willReturnCallback(
            function ($moduleConfiguration, int $shopId) use ($failingShopId): void {
                if ($failingShopId !== null && $shopId === $failingShopId) {
                    throw new \RuntimeException('shop ' . $shopId . ' broken');
                }
            }
        );

        return $service;
    }
}
