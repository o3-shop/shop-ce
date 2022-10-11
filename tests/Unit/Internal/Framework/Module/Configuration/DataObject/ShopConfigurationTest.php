<?php
declare(strict_types = 1);

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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use PHPUnit\Framework\TestCase;

final class ShopConfigurationTest extends TestCase
{
    /** @var ShopConfiguration */
    private $shopConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopConfiguration = new ShopConfiguration();
    }

    public function testGetModuleConfiguration(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModuleId');

        $this->shopConfiguration->addModuleConfiguration($moduleConfiguration);
        $this->assertSame($moduleConfiguration, $this->shopConfiguration->getModuleConfiguration('testModuleId'));
    }

    public function testGetModuleConfigurations(): void
    {
        $moduleConfiguration1 = new ModuleConfiguration();
        $moduleConfiguration1->setId('firstModule');

        $moduleConfiguration2 = new ModuleConfiguration();
        $moduleConfiguration2->setId('secondModule');

        $this->shopConfiguration
             ->addModuleConfiguration($moduleConfiguration1)
             ->addModuleConfiguration($moduleConfiguration2);

        $this->assertSame(
            [
                'firstModule'   => $moduleConfiguration1,
                'secondModule'  => $moduleConfiguration2,
            ],
            $this->shopConfiguration->getModuleConfigurations()
        );
    }

    public function testHasModuleConfiguration(): void
    {
        $testModuleId = 'testModuleId';

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId($testModuleId);

        $this->assertFalse(
            $this->shopConfiguration->hasModuleConfiguration($testModuleId)
        );

        $this->shopConfiguration->addModuleConfiguration($moduleConfiguration);

        $this->assertTrue(
            $this->shopConfiguration->hasModuleConfiguration($testModuleId)
        );
    }

    public function testGetModuleConfigurationThrowsExceptionIfModuleIdNotPresent(): void
    {
        $this->expectException(ModuleConfigurationNotFoundException::class);
        $this->shopConfiguration->getModuleConfiguration('moduleIdNotPresent');
    }

    public function testDeleteModuleConfiguration(): void
    {
        $testModuleId = 'testModuleId';

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId($testModuleId);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);

        $shopConfiguration->deleteModuleConfiguration($testModuleId);

        $this->assertFalse($shopConfiguration->hasModuleConfiguration($testModuleId));
    }

    public function testDeleteModuleConfigurationRemovesModuleExtensionFromChain(): void
    {
        $moduleExtensionToStay = new ModuleConfiguration\ClassExtension(
            'shopClass',
            'moduleExtensionToStay'
        );
        $moduleConfigurationToStay = new ModuleConfiguration();
        $moduleConfigurationToStay->setId('moduleToStay');
        $moduleConfigurationToStay->addClassExtension($moduleExtensionToStay);

        $moduleExtensionToDelete = new ModuleConfiguration\ClassExtension(
            'shopClass',
            'moduleExtensionToDelete'
        );
        $moduleConfigurationToDelete = new ModuleConfiguration();
        $moduleConfigurationToDelete->setId('moduleToDelete');
        $moduleConfigurationToDelete->addClassExtension($moduleExtensionToDelete);

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration
            ->addModuleConfiguration($moduleConfigurationToDelete)
            ->addModuleConfiguration($moduleConfigurationToStay);

        $shopConfiguration->getClassExtensionsChain()->addExtension($moduleExtensionToStay);
        $shopConfiguration->getClassExtensionsChain()->addExtension($moduleExtensionToDelete);

        $shopConfiguration->deleteModuleConfiguration('moduleToDelete');

        $expectedClassExtensionChain = new ClassExtensionsChain();
        $expectedClassExtensionChain->addExtension($moduleExtensionToStay);

        $this->assertEquals(
            $expectedClassExtensionChain,
            $shopConfiguration->getClassExtensionsChain()
        );
    }

    public function testDeleteModuleConfigurationThrowsExceptionIfModuleIdNotPresent(): void
    {
        $this->expectException(ModuleConfigurationNotFoundException::class);
        $this->shopConfiguration->deleteModuleConfiguration('moduleIdNotPresent');
    }

    public function testChains(): void
    {
        $chain = new ClassExtensionsChain();

        $this->shopConfiguration->setClassExtensionsChain($chain);

        $this->assertSame(
            $chain,
            $this->shopConfiguration->getClassExtensionsChain()
        );
    }

    public function testDefaultChains(): void
    {
        $chain = new ClassExtensionsChain();

        $this->assertEquals(
            $chain,
            $this->shopConfiguration->getClassExtensionsChain()
        );
    }
}
