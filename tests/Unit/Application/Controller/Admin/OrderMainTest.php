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

use OxidEsales\EshopCommunity\Core\ShopIdCalculator;
use OxidEsales\EshopCommunity\Application\Model\Order;
use \Exception;
use \oxTestModules;

/**
 * Tests for Order_Main class
 */
class OrderMainTest extends \OxidTestCase
{
    /**
     * tear down the test.
     */
    protected function tearDown(): void
    {
        $_POST = array();
        parent::tearDown();
    }

    /**
     * Order_Main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction('oxorder', 'load', '{ $this->oxorder__oxdeltype = new oxField("test"); $this->oxorder__oxtotalbrutsum = new oxField(10); $this->oxorder__oxcurrate = new oxField(10); }');
        $this->setRequestParameter("oxid", "testId");

        // testing..
        $oView = oxNew('Order_Main');
        $this->assertEquals('order_main.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['edit']));
        $this->assertTrue($aViewData['edit'] instanceof order);
    }

    /**
     * Statistic_Main::Render() test case
     *
     * @return null
     */
    public function testRenderNoRealObjectId()
    {
        $this->setRequestParameter("oxid", "-1");

        // testing..
        $oView = oxNew('Order_Main');
        $this->assertEquals('order_main.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['oxid']));
        $this->assertEquals("-1", $aViewData['oxid']);
    }

    /**
     * Order_Main::senddownloadlinks() test case
     *
     * @return null
     */
    public function testSenddownloadlinks()
    {
        //
        oxTestModules::addFunction('oxorder', 'load', '{ return true; }');
        oxTestModules::addFunction('oxemail', 'sendDownloadLinksMail', '{ throw new Exception( "sendDownloadLinksMail" ); }');

        $this->setRequestParameter("oxid", "testId");

        // testing..
        try {
            $oView = oxNew('Order_Main');
            $oView->senddownloadlinks();
        } catch (Exception $oExcp) {
            $this->assertEquals("sendDownloadLinksMail", $oExcp->getMessage(), "error in Order_Main::senddownloadlinks()");

            return;
        }
        $this->fail("error in Order_Main::senddownloadlinks()");
    }

    /**
     * Order_Main::Resetorder() test case
     *
     * @return null
     */
    public function testResetorder()
    {
        //
        oxTestModules::addFunction('oxorder', 'load', '{ return true; }');
        oxTestModules::addFunction('oxorder', 'save', '{ throw new Exception( "recalculateOrder" ); }');

        // testing..
        try {
            $oView = oxNew('Order_Main');
            $oView->resetorder();
        } catch (Exception $oExcp) {
            $this->assertEquals("recalculateOrder", $oExcp->getMessage(), "error in Order_Main::resetorder()");

            return;
        }
        $this->fail("error in Order_Main::resetorder()");
    }
}
