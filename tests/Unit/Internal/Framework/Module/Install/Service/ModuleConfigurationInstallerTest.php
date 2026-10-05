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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleConfigurationMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstaller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationInstallerTest extends TestCase
{
    private function makeContext(string $modulesPath = '/var/www/html/source/modules'): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getModulesPath')->willReturn($modulesPath);
        return $context;
    }

    private function makeModuleConfig(string $id): ModuleConfiguration
    {
        $config = new ModuleConfiguration();
        $config->setId($id);
        return $config;
    }

    public function testInstallSetsRelativePathAndPersistsConfiguration(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->expects($this->once())
            ->method('get')
            ->with('/source/mymodule')
            ->willReturn($moduleConfig);

        $shopConfigA = $this->createMock(ShopConfiguration::class);
        $shopConfigB = $this->createMock(ShopConfiguration::class);

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([
            1 => $shopConfigA,
            2 => $shopConfigB,
        ]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);
        $projectDao->expects($this->once())->method('save')->with($projectConfig);

        $merger = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        // Merger called once per shop.
        $merger->expects($this->exactly(2))->method('merge');

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext('/var/www/html/source/modules'),
            $merger,
            $metadataDao
        );

        $installer->install('/source/mymodule', '/var/www/html/source/modules/mymodule');

        // The relative path was set on the module configuration.
        $this->assertSame('mymodule', $moduleConfig->getPath());
    }

    public function testInstallKeepsRelativePathAsIsWhenAlreadyRelative(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($moduleConfig);

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext(),
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $metadataDao
        );
        $installer->install('/anywhere', 'mymodule'); // already relative

        $this->assertSame('mymodule', $moduleConfig->getPath());
    }

    public function testUninstallRemovesModuleFromAllShopsThatHaveIt(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($moduleConfig);

        $shopWithModule = $this->createMock(ShopConfiguration::class);
        $shopWithModule->method('hasModuleConfiguration')->with('mymodule')->willReturn(true);
        $shopWithModule->expects($this->once())->method('deleteModuleConfiguration')->with('mymodule');

        $shopWithoutModule = $this->createMock(ShopConfiguration::class);
        $shopWithoutModule->method('hasModuleConfiguration')->with('mymodule')->willReturn(false);
        $shopWithoutModule->expects($this->never())->method('deleteModuleConfiguration');

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([
            1 => $shopWithModule,
            2 => $shopWithoutModule,
        ]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);
        $projectDao->expects($this->once())->method('save')->with($projectConfig);

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext(),
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $metadataDao
        );
        $installer->uninstall('/source/mymodule');
    }

    public function testUninstallByIdRemovesFromShopsThatHaveTheModule(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $shopWithModule = $this->createMock(ShopConfiguration::class);
        $shopWithModule->method('getModuleConfiguration')->with('mymodule')->willReturn($moduleConfig);
        $shopWithModule->expects($this->once())->method('deleteModuleConfiguration')->with('mymodule');

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([1 => $shopWithModule]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);
        $projectDao->expects($this->once())->method('save');

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext(),
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $this->createMock(ModuleConfigurationDaoInterface::class)
        );
        $installer->uninstallById('mymodule');
    }

    public function testIsInstalledReturnsTrueWhenAnyShopHasTheModule(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($moduleConfig);

        $shopA = $this->createMock(ShopConfiguration::class);
        $shopA->method('hasModuleConfiguration')->with('mymodule')->willReturn(false);
        $shopB = $this->createMock(ShopConfiguration::class);
        $shopB->method('hasModuleConfiguration')->with('mymodule')->willReturn(true);

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([1 => $shopA, 2 => $shopB]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext(),
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $metadataDao
        );
        $this->assertTrue($installer->isInstalled('/source/mymodule'));
    }

    public function testIsInstalledReturnsFalseWhenNoShopHasTheModule(): void
    {
        $moduleConfig = $this->makeModuleConfig('mymodule');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($moduleConfig);

        $shop = $this->createMock(ShopConfiguration::class);
        $shop->method('hasModuleConfiguration')->willReturn(false);

        $projectConfig = $this->createMock(ProjectConfiguration::class);
        $projectConfig->method('getShopConfigurations')->willReturn([1 => $shop]);

        $projectDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectDao->method('getConfiguration')->willReturn($projectConfig);

        $installer = new ModuleConfigurationInstaller(
            $projectDao,
            $this->makeContext(),
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $metadataDao
        );
        $this->assertFalse($installer->isInstalled('/source/mymodule'));
    }
}
