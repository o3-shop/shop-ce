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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ProjectConfigurationDataMapper;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ShopConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProjectConfigurationDataMapperTest extends TestCase
{
    public function testEnvironmentsMapping()
    {
        $configurationData = [
            'shops' => [],
        ];

        $projectConfiguration = new ProjectConfiguration();

        $projectConfigurationDataMapper = new ProjectConfigurationDataMapper(
            $this->getMockBuilder(ShopConfigurationDataMapperInterface::class)->getMock()
        );

        $this->assertEquals(
            $projectConfiguration,
            $projectConfigurationDataMapper->fromData($configurationData)
        );

        $this->assertEquals(
            $configurationData,
            $projectConfigurationDataMapper->toData($projectConfiguration)
        );
    }

    public function testShopsMapping()
    {
        $configurationData = [
            'shops' => [
                '1' => [],
                '2' => [],
            ],
        ];

        $projectConfiguration = new ProjectConfiguration();

        $projectConfiguration->addShopConfiguration(1, new ShopConfiguration());
        $projectConfiguration->addShopConfiguration(2, new ShopConfiguration());

        $shopConfigurationDataMapper = $this
            ->getMockBuilder(ShopConfigurationDataMapperInterface::class)
            ->getMock();

        $shopConfigurationDataMapper
            ->method('fromData')
            ->with($this->equalTo([]))
            ->willReturn(new ShopConfiguration());

        $projectConfigurationDataMapper = new ProjectConfigurationDataMapper($shopConfigurationDataMapper);

        $this->assertEquals(
            $projectConfiguration,
            $projectConfigurationDataMapper->fromData($configurationData)
        );

        $this->assertEquals(
            $configurationData,
            $projectConfigurationDataMapper->toData($projectConfiguration)
        );
    }
}
