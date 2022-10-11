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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

/**
 * Class Unit_Core_oxOnlineRequestTest
 *
 * @covers oxOnlineRequest
 */
class OnlineRequestTest extends \OxidTestCase
{
    public function testClusterIdGenerationWhenNotSet()
    {
        $this->getConfig()->setConfigParam('sClusterId', '');
        $request = oxNew('oxOnlineRequest');
        $this->assertNotEquals('', $request->clusterId);
    }

    public function testClusterIdIsNotRegenerationWhenAlreadySet()
    {
        $this->getConfig()->setConfigParam('sClusterId', 'generated_unique_cluster_id');
        $request = oxNew('oxOnlineRequest');
        $this->assertSame('generated_unique_cluster_id', $request->clusterId);
    }

    public function testDefaultParametersSetOnConstruct()
    {
        $config = $this->getConfig();

        $config->setConfigParam('sClusterId', 'generated_unique_cluster_id');
        $request = oxNew('oxOnlineRequest');

        $this->assertSame('generated_unique_cluster_id', $request->clusterId);
        $this->assertSame($config->getEdition(), $request->edition);
        $this->assertSame($config->getVersion(), $request->version);
        $this->assertSame($config->getShopUrl(), $request->shopUrl);
    }
}
