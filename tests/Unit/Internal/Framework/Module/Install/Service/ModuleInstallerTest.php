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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Install\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\BootstrapModuleInstaller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstaller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use PHPUnit\Framework\TestCase;

class ModuleInstallerTest extends TestCase
{
    private function makePackage(string $sourcePath = '/path/to/module'): OxidEshopPackage
    {
        $package = $this->getMockBuilder(OxidEshopPackage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $package->method('getPackageSourcePath')->willReturn($sourcePath);
        return $package;
    }

    public function testInstallDelegatesToBootstrapInstaller(): void
    {
        $package = $this->makePackage();
        $bootstrap = $this->getMockBuilder(BootstrapModuleInstaller::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bootstrap->expects($this->once())->method('install')->with($package);

        $installer = new ModuleInstaller(
            $bootstrap,
            $this->createMock(ModuleActivationServiceInterface::class),
            $this->createMock(ModuleConfigurationDaoInterface::class),
            $this->createMock(ShopConfigurationDaoInterface::class),
            $this->createMock(ModuleStateServiceInterface::class)
        );
        $installer->install($package);
    }

    public function testIsInstalledDelegatesToBootstrap(): void
    {
        $package = $this->makePackage();
        $bootstrap = $this->getMockBuilder(BootstrapModuleInstaller::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bootstrap->expects($this->once())->method('isInstalled')->with($package)->willReturn(true);

        $installer = new ModuleInstaller(
            $bootstrap,
            $this->createMock(ModuleActivationServiceInterface::class),
            $this->createMock(ModuleConfigurationDaoInterface::class),
            $this->createMock(ShopConfigurationDaoInterface::class),
            $this->createMock(ModuleStateServiceInterface::class)
        );
        $this->assertTrue($installer->isInstalled($package));
    }

    public function testUninstallDeactivatesActiveShopsAndDelegatesToBootstrap(): void
    {
        $package = $this->makePackage('/source/mymodule');

        $moduleConfiguration = $this->createMock(ModuleConfiguration::class);
        $moduleConfiguration->method('getId')->willReturn('mymodule');

        $configDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $configDao->expects($this->once())
            ->method('get')
            ->with('/source/mymodule')
            ->willReturn($moduleConfiguration);

        $shopConfigA = $this->createMock(ShopConfiguration::class);
        $shopConfigA->method('hasModuleConfiguration')->with('mymodule')->willReturn(true);
        $shopConfigB = $this->createMock(ShopConfiguration::class);
        $shopConfigB->method('hasModuleConfiguration')->with('mymodule')->willReturn(false);

        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([
            1 => $shopConfigA,
            2 => $shopConfigB,
        ]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturnMap([
            ['mymodule', 1, true],
        ]);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        // Only shop 1 has the module AND active state → exactly one deactivate call.
        $activationService->expects($this->once())
            ->method('deactivate')
            ->with('mymodule', 1);

        $bootstrap = $this->getMockBuilder(BootstrapModuleInstaller::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bootstrap->expects($this->once())->method('uninstall')->with($package);

        $installer = new ModuleInstaller(
            $bootstrap,
            $activationService,
            $configDao,
            $shopConfigDao,
            $stateService
        );
        $installer->uninstall($package);
    }

    public function testUninstallSkipsDeactivationWhenModuleNotPresentInAShop(): void
    {
        $package = $this->makePackage();

        $moduleConfiguration = $this->createMock(ModuleConfiguration::class);
        $moduleConfiguration->method('getId')->willReturn('mymodule');

        $configDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $configDao->method('get')->willReturn($moduleConfiguration);

        // Shop has the module but it's not active → no deactivate call.
        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn(true);

        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(false);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->never())->method('deactivate');

        $bootstrap = $this->getMockBuilder(BootstrapModuleInstaller::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Bootstrap uninstall still runs — even when nothing was active.
        $bootstrap->expects($this->once())->method('uninstall');

        (new ModuleInstaller(
            $bootstrap,
            $activationService,
            $configDao,
            $shopConfigDao,
            $stateService
        ))->uninstall($package);
    }
}
