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

use DomainException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use PHPUnit\Framework\TestCase;

class ProjectConfigurationTest extends TestCase
{
    /** @var ProjectConfiguration */
    private $projectConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectConfiguration = new ProjectConfiguration();
    }

    public function testGetShopConfiguration()
    {
        $shopConfiguration = new ShopConfiguration();
        $this->projectConfiguration->addShopConfiguration(0, $shopConfiguration);
        $this->assertSame($shopConfiguration, $this->projectConfiguration->getShopConfiguration(0));
    }

    public function testGetShopConfigurations()
    {
        $shopConfiguration = new ShopConfiguration();
        $this->projectConfiguration->addShopConfiguration(0, $shopConfiguration);
        $this->projectConfiguration->addShopConfiguration(1, $shopConfiguration);

        $this->assertSame(
            [
                0 => $shopConfiguration,
                1 => $shopConfiguration,
            ],
            $this->projectConfiguration->getShopConfigurations()
        );
    }


    public function testGetShopConfigurationThrowsExceptionWithNotExistingShopId()
    {
        $this->expectException(DomainException::class);
        $this->projectConfiguration->getShopConfiguration(0);
    }

    public function testGetShopIdsOfShopConfigurations()
    {
        $shopConfiguration = new ShopConfiguration();
        $this->projectConfiguration->addShopConfiguration(1, $shopConfiguration);
        $this->projectConfiguration->addShopConfiguration(2, $shopConfiguration);
        $this->assertEquals([1,2], $this->projectConfiguration->getShopConfigurationIds());
    }

    public function testDeleteShopConfiguration()
    {
        $shopConfiguration = new ShopConfiguration();
        $this->projectConfiguration->addShopConfiguration(1, $shopConfiguration);
        $this->projectConfiguration->addShopConfiguration(2, $shopConfiguration);
        $this->projectConfiguration->deleteShopConfiguration(1);
        $this->assertEquals([2], $this->projectConfiguration->getShopConfigurationIds());
    }

    public function testDeleteShopConfigurationThrowsExceptionWithNotExistingShopId()
    {
        $this->expectException(DomainException::class);
        $this->projectConfiguration->deleteShopConfiguration(0);
    }
}
