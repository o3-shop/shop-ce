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
use OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

/**
 * Drives the deprecated ModuleExtensionsCleaner's public cleanExtensions()
 * entry point, which goes through filterExtensionsByModuleId() — the
 * container-using private method the existing ModuleExtensionsCleanerTest
 * cannot reach. Same seed/cleanup pattern as ModuleListContainerTest.
 */
class ModuleExtensionsCleanerCoverageTest extends \OxidTestCase
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

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner::cleanExtensions
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner::filterExtensionsByModuleId
     */
    public function testCleanExtensionsRemovesStaleExtensionPathFromInstalledChain(): void
    {
        $this->seedModule('cov_cleaner', 'cov/cov_cleaner');

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensions', 'getId'])
            ->getMock();
        $module->method('getId')->willReturn('cov_cleaner');
        // Module's metadata declares only one extension. Anything else under
        // its path is "garbage".
        $module->method('getExtensions')->willReturn([
            'oxarticle' => 'cov/cov_cleaner/MyArticle',
        ]);

        $installed = [
            'oxarticle' => [
                'cov/cov_cleaner/MyArticle',
                'cov/cov_cleaner/StaleExt',
                'unrelated/Module/Ext',
            ],
        ];

        $cleaner = new ModuleExtensionsCleaner();
        $cleaned = $cleaner->cleanExtensions($installed, $module);

        $this->assertContains('cov/cov_cleaner/MyArticle', $cleaned['oxarticle']);
        $this->assertContains('unrelated/Module/Ext', $cleaned['oxarticle']);
        $this->assertNotContains('cov/cov_cleaner/StaleExt', $cleaned['oxarticle']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner::cleanExtensions
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner::filterExtensionsByModuleId
     */
    public function testCleanExtensionsIgnoresInstalledExtensionsThatBelongToOtherModules(): void
    {
        $this->seedModule('cov_cleaner_b', 'cov/cov_cleaner_b');

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensions', 'getId'])
            ->getMock();
        $module->method('getId')->willReturn('cov_cleaner_b');
        $module->method('getExtensions')->willReturn([]);

        // Installed entries are all under OTHER modules' paths, so the
        // cleaner sees no installed extensions for this module and exits
        // early — input chain comes back unchanged.
        $installed = [
            'oxarticle' => ['some/other/module/Ext1', 'yet/another/Ext2'],
        ];

        $cleaner = new ModuleExtensionsCleaner();
        $cleaned = $cleaner->cleanExtensions($installed, $module);

        $this->assertSame($installed, $cleaned);
    }
}
