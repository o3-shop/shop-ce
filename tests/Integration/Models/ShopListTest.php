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
namespace OxidEsales\EshopCommunity\Tests\Integration\Models;

/**
 * Testing oxShopList class
 */
class ShopListTest extends \OxidTestCase
{
    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        for ($i = 2; $i < 5; $i++) {
            if ($this->getTestConfig()->getShopEdition() == 'EE') {
                $query = "INSERT INTO `oxshops` (OXID, OXACTIVE, OXNAME, OXPARENTID) VALUES ($i, 1, 'Test Shop $i', 1)";
            } else {
                $query = "INSERT INTO `oxshops` (OXID, OXACTIVE, OXNAME) VALUES ($i, 1, 'Test Shop $i')";
            }
            $this->addToDatabase($query, 'oxshops');
        }

        $this->addTableForCleanup('oxshops');
    }

    /**
     * All shop list test
     */
    public function testGetAll()
    {
        $oShopList = oxNew('oxShopList');
        $oShopList->getAll();
        $this->assertEquals(4, $oShopList->count());
    }

    /**
     * Tests method getOne for returning shop list
     */
    public function testGetIdTitleList()
    {
        $oShopList = oxNew('oxShopList');
        $oShopList->getIdTitleList();
        $this->assertEquals(4, $oShopList->count());
    }
}
