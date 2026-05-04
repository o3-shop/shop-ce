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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Module\ModuleInstaller;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;

/**
 * Drives the deprecated ModuleInstaller's activate / deactivate /
 * getModulesWithExtendedClass entry points by seeding a real
 * ShopConfiguration via the DI container — the same pattern
 * ModuleListContainerTest uses — and cleans up after each test.
 */
class ModuleInstallerCoverageTest extends \OxidTestCase
{
    /** @var string[] */
    private $seededModuleIds = [];

    protected function tearDown(): void
    {
        $shopConfigDao = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfig = $shopConfigDao->get();

        foreach ($this->seededModuleIds as $moduleId) {
            $this->deactivateIfActive($moduleId);
            if ($shopConfig->hasModuleConfiguration($moduleId)) {
                $shopConfig->deleteModuleConfiguration($moduleId);
            }
        }
        $shopConfigDao->save($shopConfig);
        $this->seededModuleIds = [];

        parent::tearDown();
    }

    private function seedModule(string $id, string $path): void
    {
        $config = new ModuleConfiguration();
        $config->setId($id)->setPath($path);

        $shopConfigDao = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfig = $shopConfigDao->get();
        $shopConfig->addModuleConfiguration($config);
        $shopConfigDao->save($shopConfig);

        $this->seededModuleIds[] = $id;
    }

    private function deactivateIfActive(string $moduleId): void
    {
        $stateService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleStateServiceInterface::class);
        $shopId = (int) Registry::getConfig()->getShopId();
        if ($stateService->isActive($moduleId, $shopId)) {
            $stateService->setDeactivated($moduleId, $shopId);
        }
    }

    private function makeModuleStub(string $moduleId): Module
    {
        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $module->method('getId')->willReturn($moduleId);
        return $module;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::activate
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::getModuleActivationBridge
     */
    public function testActivateSucceedsForRegisteredModule(): void
    {
        $this->seedModule('cov_installer_a', 'cov/cov_installer_a');

        $installer = oxNew(ModuleInstaller::class);
        $this->assertTrue($installer->activate($this->makeModuleStub('cov_installer_a')));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::deactivate
     */
    public function testDeactivateSucceedsForActiveModule(): void
    {
        $this->seedModule('cov_installer_b', 'cov/cov_installer_b');

        $installer = oxNew(ModuleInstaller::class);
        $module = $this->makeModuleStub('cov_installer_b');

        $this->assertTrue($installer->activate($module));
        $this->assertTrue($installer->deactivate($module));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleInstaller::getModulesWithExtendedClass
     */
    public function testGetModulesWithExtendedClassDelegatesToConfig(): void
    {
        // The method is a thin wrapper around Config::getModulesWithExtendedClass.
        // We don't seed any extensions here — getConfigParam('aModules') likely
        // empty — so the result is an empty array but the call shouldn't throw.
        $installer = oxNew(ModuleInstaller::class);
        $this->assertIsArray($installer->getModulesWithExtendedClass());
    }
}
