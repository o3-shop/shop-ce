<?php
declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\DataMapper;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ModuleConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ShopConfigurationDataMapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ShopConfigurationDataMapperTest extends TestCase
{
    public function testModulesMapping()
    {
        $configurationData = [
            'modules' => [
                'happyModule' => [],
                'funnyModule' => [],
            ],
            'moduleChains' => [
                ClassExtensionsChain::NAME => [],
            ],
        ];

        $shopConfigurationDataMapper = new ShopConfigurationDataMapper(
            $this->getModuleConfigurationMapper()
        );

        $shopConfiguration = $shopConfigurationDataMapper->fromData($configurationData);

        $this->assertEquals(
            $configurationData,
            $shopConfigurationDataMapper->toData($shopConfiguration)
        );
    }

    public function testChainsMapping()
    {
        $configurationData = [
            'modules'      => [],
            'moduleChains' => [
                ClassExtensionsChain::NAME => [],
            ],
        ];

        $shopConfigurationDataMapper = new ShopConfigurationDataMapper(
            $this->getModuleConfigurationMapper()
        );

        $shopConfiguration = $shopConfigurationDataMapper->fromData($configurationData);

        $this->assertSame(
            $configurationData,
            $shopConfigurationDataMapper->toData($shopConfiguration)
        );
    }

    private function getModuleConfigurationMapper()
    {
        $moduleConfigurationDataMapper = $this
            ->getMockBuilder(ModuleConfigurationDataMapperInterface::class)
            ->getMock();

        $moduleConfigurationDataMapper
            ->method('fromData')
            ->willReturn(new ModuleConfiguration());

        $moduleConfigurationDataMapper
            ->method('toData')
            ->willReturn([]);

        return $moduleConfigurationDataMapper;
    }
}
