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

use OxidEsales\Eshop\Core\Module\ModuleList;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;

/**
 * Drives the container-dependent ModuleList methods by seeding the real
 * ShopConfiguration with throw-away ModuleConfigurations and cleaning them up
 * after each test. Same pattern as tests/Unit/Core/Module/ModuleTest.php.
 */
class ModuleListContainerTest extends \OxidTestCase
{
    /** @var string[] module ids the test seeded into shop config */
    private $seededModuleIds = [];

    protected function tearDown(): void
    {
        $shopConfigDao = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfig = $shopConfigDao->get();

        foreach ($this->seededModuleIds as $moduleId) {
            if ($shopConfig->hasModuleConfiguration($moduleId)) {
                $shopConfig->deleteModuleConfiguration($moduleId);
            }
        }
        $shopConfigDao->save($shopConfig);
        $this->seededModuleIds = [];

        parent::tearDown();
    }

    private function seedModule(string $id, string $path, array $extensionPairs = []): ModuleConfiguration
    {
        $moduleConfig = new ModuleConfiguration();
        $moduleConfig->setId($id)->setPath($path);
        foreach ($extensionPairs as [$shopClass, $moduleClass]) {
            $moduleConfig->addClassExtension(new ClassExtension($shopClass, $moduleClass));
        }

        $shopConfigDao = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopConfigurationDaoBridgeInterface::class);
        $shopConfig = $shopConfigDao->get();
        $shopConfig->addModuleConfiguration($moduleConfig);
        // deleteModuleConfiguration() removes the same extensions from the
        // chain — pre-add them here so cleanup in tearDown() doesn't trip
        // ExtensionNotInChainException.
        $shopConfig->getClassExtensionsChain()->addExtensions($moduleConfig->getClassExtensions());
        $shopConfigDao->save($shopConfig);

        $this->seededModuleIds[] = $id;
        return $moduleConfig;
    }

    private function activateModule(string $moduleId): void
    {
        $stateService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleStateServiceInterface::class);
        $shopId = (int) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
        if (!$stateService->isActive($moduleId, $shopId)) {
            $stateService->setActive($moduleId, $shopId);
        }
    }

    private function deactivateModule(string $moduleId): void
    {
        $stateService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleStateServiceInterface::class);
        $shopId = (int) \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId();
        if ($stateService->isActive($moduleId, $shopId)) {
            $stateService->setDeactivated($moduleId, $shopId);
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getModules
     */
    public function testGetModulesReturnsExtensionsForAllSeededModules(): void
    {
        $this->seedModule('cov_a', 'cov/cov_a', [['ShopClassA', 'cov_a/ExtA']]);
        $this->seedModule('cov_b', 'cov/cov_b', [['ShopClassB', 'cov_b/ExtB']]);

        $extensions = (new ModuleList())->getModules();

        $this->assertSame('cov_a/ExtA', $extensions['ShopClassA']);
        $this->assertSame('cov_b/ExtB', $extensions['ShopClassB']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getModules
     */
    public function testGetModulesAmpersandJoinsMultipleExtensionsForSameShopClass(): void
    {
        $this->seedModule('cov_c', 'cov/cov_c', [['SharedShopClass', 'cov_c/ExtC']]);
        $this->seedModule('cov_d', 'cov/cov_d', [['SharedShopClass', 'cov_d/ExtD']]);

        $extensions = (new ModuleList())->getModules();

        $this->assertContains($extensions['SharedShopClass'], [
            'cov_c/ExtC&cov_d/ExtD',
            'cov_d/ExtD&cov_c/ExtC',
        ]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getModulesWithExtendedClass
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::parseModuleChains
     */
    public function testGetModulesWithExtendedClassParsesIntoNestedArrays(): void
    {
        $this->seedModule('cov_e', 'cov/cov_e', [['ShopClassE', 'cov_e/ExtE']]);

        $modules = (new ModuleList())->getModulesWithExtendedClass();

        $this->assertSame(['ShopClassE' => ['cov_e/ExtE']], array_intersect_key(
            $modules,
            ['ShopClassE' => true]
        ));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getDisabledModules
     */
    public function testGetDisabledModulesIncludesInactiveSeededModules(): void
    {
        $this->seedModule('cov_disabled_x', 'cov/cov_disabled_x', [['Sx', 'cov_disabled_x/Ex']]);
        $this->deactivateModule('cov_disabled_x');

        $disabled = (new ModuleList())->getDisabledModules();
        $this->assertContains('cov_disabled_x', $disabled);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getDisabledModuleInfo
     */
    public function testGetDisabledModuleInfoMapsIdToPath(): void
    {
        $this->seedModule('cov_disabled_y', 'cov/cov_disabled_y');
        $this->deactivateModule('cov_disabled_y');

        $info = (new ModuleList())->getDisabledModuleInfo();
        $this->assertSame('cov/cov_disabled_y', $info['cov_disabled_y']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getDisabledModuleClasses
     */
    public function testGetDisabledModuleClassesCollectsExtensionClassNames(): void
    {
        $this->seedModule('cov_disabled_z', 'cov/cov_disabled_z', [
            ['Sz1', 'cov_disabled_z/Ez1'],
            ['Sz2', 'cov_disabled_z/Ez2'],
        ]);
        $this->deactivateModule('cov_disabled_z');

        $classes = (new ModuleList())->getDisabledModuleClasses();

        $this->assertContains('cov_disabled_z/Ez1', $classes);
        $this->assertContains('cov_disabled_z/Ez2', $classes);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getModuleIds
     */
    public function testGetModuleIdsReturnsAllSeededModuleIds(): void
    {
        $this->seedModule('cov_ids_1', 'cov/cov_ids_1');
        $this->seedModule('cov_ids_2', 'cov/cov_ids_2');

        $ids = (new ModuleList())->getModuleIds();

        $this->assertContains('cov_ids_1', $ids);
        $this->assertContains('cov_ids_2', $ids);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::extractModulePaths
     */
    public function testExtractModulePathsTakesPrefixBeforeFirstSlash(): void
    {
        $this->seedModule('cov_extract', 'cov/cov_extract', [
            ['SExt', 'cov_extract/Sub/Ext'],
        ]);

        $paths = (new ModuleList())->extractModulePaths();

        $this->assertArrayHasKey('cov_extract', $paths);
        $this->assertSame('cov_extract', $paths['cov_extract']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getModuleExtensions
     */
    public function testGetModuleExtensionsReturnsExtensionsForActiveModule(): void
    {
        $this->seedModule('cov_active', 'cov/cov_active', [
            ['ShopActive', 'cov_active/ActiveExt'],
        ]);
        $this->activateModule('cov_active');

        $extensions = (new ModuleList())->getModuleExtensions('cov_active');

        $this->assertSame(
            ['ShopActive' => ['cov_active/ActiveExt']],
            $extensions
        );

        $this->deactivateModule('cov_active');
    }
}
