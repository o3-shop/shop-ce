<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Cache;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Cache\ClassPropertyShopConfigurationCache;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use PHPUnit\Framework\TestCase;

final class ClassPropertyShopConfigurationCacheTest extends TestCase
{
    public function testPut(): void
    {
        $configuration = new ShopConfiguration();

        $cache = new ClassPropertyShopConfigurationCache();
        $cache->put(2, $configuration);

        $this->assertSame($configuration, $cache->get(2));
    }

    public function testExists(): void
    {
        $cache = new ClassPropertyShopConfigurationCache();

        $this->assertFalse($cache->exists(1));

        $cache->put(1, new ShopConfiguration());

        $this->assertTrue($cache->exists(1));
    }

    public function testEvict(): void
    {
        $cache = new ClassPropertyShopConfigurationCache();
        $cache->put(3, new ShopConfiguration());
        $cache->evict(3);

        $this->assertFalse($cache->exists(3));
    }
}
