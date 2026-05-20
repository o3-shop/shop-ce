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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleClassExtensionsMergingService;
use PHPUnit\Framework\TestCase;

class ModuleClassExtensionsMergingServiceTest extends TestCase
{
    private function makeModule(string $id, array $extensions): ModuleConfiguration
    {
        $module = new ModuleConfiguration();
        $module->setId($id);
        foreach ($extensions as [$shopClass, $moduleClass]) {
            $module->addClassExtension(new ClassExtension($shopClass, $moduleClass));
        }
        return $module;
    }

    public function testAddsExtensionsForBrandNewModule(): void
    {
        // Shop has no prior knowledge of this module → all incoming extensions
        // get appended as-is.
        $shopConfig = new ShopConfiguration();
        $module = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
            ['oxorder', 'mymodule/Order'],
        ]);

        $chain = (new ModuleClassExtensionsMergingService())->merge($shopConfig, $module);

        $array = $chain->getChain();
        $this->assertSame(['mymodule/Article'], $array['oxarticle']);
        $this->assertSame(['mymodule/Order'], $array['oxorder']);
    }

    public function testAddsNewExtensionsToExistingModule(): void
    {
        // Existing: mymodule extends oxarticle.
        $existing = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
        ]);
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration($existing);
        $shopConfig->getClassExtensionsChain()->addExtensions($existing->getClassExtensions());

        // New version of mymodule adds oxorder extension.
        $newVersion = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
            ['oxorder', 'mymodule/Order'],
        ]);

        $chain = (new ModuleClassExtensionsMergingService())->merge($shopConfig, $newVersion);

        $array = $chain->getChain();
        $this->assertSame(['mymodule/Article'], $array['oxarticle']);
        $this->assertSame(['mymodule/Order'], $array['oxorder']);
    }

    public function testReplacesExtensionWhenModuleExtensionClassNameChanges(): void
    {
        // Same shop class extended; module renamed its extension class.
        $existing = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/OldArticle'],
        ]);
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration($existing);
        $shopConfig->getClassExtensionsChain()->addExtensions($existing->getClassExtensions());

        $newVersion = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/NewArticle'],
        ]);

        $chain = (new ModuleClassExtensionsMergingService())->merge($shopConfig, $newVersion);

        $array = $chain->getChain();
        // The chain order is preserved (admin can reorder), only the class
        // name swapped.
        $this->assertSame(['mymodule/NewArticle'], array_values($array['oxarticle']));
    }

    public function testRemovesExtensionsThatNoLongerExtendAShopClass(): void
    {
        // Existing: mymodule extends oxarticle + oxorder.
        $existing = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
            ['oxorder', 'mymodule/Order'],
        ]);
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration($existing);
        $shopConfig->getClassExtensionsChain()->addExtensions($existing->getClassExtensions());

        // New version dropped the oxorder extension.
        $newVersion = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
        ]);

        $chain = (new ModuleClassExtensionsMergingService())->merge($shopConfig, $newVersion);
        $array = $chain->getChain();

        $this->assertArrayHasKey('oxarticle', $array);
        $this->assertArrayNotHasKey(
            'oxorder',
            $array,
            'A shop class no longer extended by the new module version must be dropped from the chain.'
        );
    }

    public function testNoOpWhenNothingChanges(): void
    {
        $existing = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
        ]);
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration($existing);
        $shopConfig->getClassExtensionsChain()->addExtensions($existing->getClassExtensions());

        $sameAgain = $this->makeModule('mymodule', [
            ['oxarticle', 'mymodule/Article'],
        ]);

        $chain = (new ModuleClassExtensionsMergingService())->merge($shopConfig, $sameAgain);
        $this->assertSame(['mymodule/Article'], array_values($chain->getChain()['oxarticle']));
    }
}
