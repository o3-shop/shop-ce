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
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;

/**
 * Drives Module's container-dependent surface that ModuleTest cannot reach
 * without seeded ShopConfiguration: getExtensions(), hasExtendClass(),
 * getModuleIdByClassName(), isActive(), getModulePaths().
 */
class ModuleCoverageTest extends \OxidTestCase
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
            $stateService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ModuleStateServiceInterface::class);
            $shopId = (int) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
            if ($stateService->isActive($moduleId, $shopId)) {
                $stateService->setDeactivated($moduleId, $shopId);
            }
            if ($shopConfig->hasModuleConfiguration($moduleId)) {
                $shopConfig->deleteModuleConfiguration($moduleId);
            }
        }
        $shopConfigDao->save($shopConfig);
        $this->seededModuleIds = [];
        parent::tearDown();
    }

    private function seedModule(string $id, string $path, array $extensionPairs = []): void
    {
        $config = new ModuleConfiguration();
        $config->setId($id)->setPath($path);
        foreach ($extensionPairs as [$shopClass, $moduleClass]) {
            $config->addClassExtension(new ClassExtension($shopClass, $moduleClass));
        }

        $shopConfigDao = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfig = $shopConfigDao->get();
        $shopConfig->addModuleConfiguration($config);
        $shopConfig->getClassExtensionsChain()->addExtensions($config->getClassExtensions());
        $shopConfigDao->save($shopConfig);

        $this->seededModuleIds[] = $id;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::getExtensions
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::hasExtendClass
     */
    public function testGetExtensionsReturnsClassNameMapForRegisteredModule(): void
    {
        $this->seedModule('cov_mod_a', 'cov/cov_mod_a', [
            ['SomeShopClass', 'cov_mod_a/Sub/Ext'],
        ]);

        $module = oxNew(Module::class);
        $module->setModuleData(['id' => 'cov_mod_a']);

        $this->assertSame(
            ['SomeShopClass' => 'cov_mod_a/Sub/Ext'],
            $module->getExtensions()
        );
        $this->assertTrue($module->hasExtendClass());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::getExtensions
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::hasExtendClass
     */
    public function testGetExtensionsReturnsEmptyArrayWhenModuleHasNoExtensions(): void
    {
        $this->seedModule('cov_mod_b', 'cov/cov_mod_b');

        $module = oxNew(Module::class);
        $module->setModuleData(['id' => 'cov_mod_b']);

        $this->assertSame([], $module->getExtensions());
        $this->assertFalse($module->hasExtendClass());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::getModuleIdByClassName
     */
    public function testGetModuleIdByClassNameMatchesAcrossSeededConfigurations(): void
    {
        $this->seedModule('cov_mod_c', 'cov/cov_mod_c', [
            ['ShopX', 'cov_mod_c/X'],
        ]);
        $this->seedModule('cov_mod_d', 'cov/cov_mod_d', [
            ['ShopY', 'cov_mod_d/Y'],
        ]);

        $module = oxNew(Module::class);
        $this->assertSame('cov_mod_c', $module->getModuleIdByClassName('cov_mod_c/X'));
        $this->assertSame('cov_mod_d', $module->getModuleIdByClassName('cov_mod_d/Y'));
        $this->assertSame('', $module->getModuleIdByClassName('nope/Unknown'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::isActive
     */
    public function testIsActiveReturnsFalseWhenModuleHasNoLoadedId(): void
    {
        $module = oxNew(Module::class);
        $this->assertFalse($module->isActive());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::isActive
     */
    public function testIsActiveReflectsModuleStateServiceForLoadedModule(): void
    {
        $this->seedModule('cov_mod_e', 'cov/cov_mod_e');
        $module = oxNew(Module::class);
        $module->setModuleData(['id' => 'cov_mod_e']);

        $this->assertFalse($module->isActive(), 'fresh seeded module is inactive');

        $stateService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleStateServiceInterface::class);
        $shopId = (int) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
        $stateService->setActive('cov_mod_e', $shopId);

        $this->assertTrue($module->isActive(), 'after explicit activation');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\Module::getModulePaths
     */
    public function testGetModulePathsExposesIdToPathFromShopConfiguration(): void
    {
        $this->seedModule('cov_mod_f', 'cov/cov_mod_f');

        $module = oxNew(Module::class);
        $paths = $module->getModulePaths();

        $this->assertArrayHasKey('cov_mod_f', $paths);
        $this->assertSame('cov/cov_mod_f', $paths['cov_mod_f']);
    }
}
