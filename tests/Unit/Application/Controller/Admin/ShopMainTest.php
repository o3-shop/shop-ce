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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use \Exception;
use \oxTestModules;

/**
 * Tests for Shop_Main class
 */
class ShopMainTest extends \OxidTestCase
{
    /**
     * Shop_Main::Render() test case
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Shop_Main');

        $this->setRequestParameter("oxid", $this->getConfig()->getBaseShopId());
        $this->assertEquals('shop_main.tpl', $oView->render());
    }

    /**
     * Shop_Main::Save() test case
     *
     * @return null
     */
    public function testSaveSuccess()
    {
        // testing..
        oxTestModules::addFunction('oxshop', 'save', '{ throw new Exception( "save" ); }');

        // testing..
        try {
            $oView = oxNew('Shop_Main');
            $oView->save();
        } catch (Exception $oExcp) {
            $this->assertEquals("save", $oExcp->getMessage(), "error in Shop_Main::save()");

            return;
        }
        $this->fail("error in Shop_Main::save()");
    }
}
