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

use OxidEsales\EshopCommunity\Application\Model\Discount;

use \Exception;
use \oxField;
use \oxTestModules;

/**
 * Tests for Discount_Main class
 */
class DiscountMainTest extends \OxidTestCase
{

    /**
     * Test tear down
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable("oxdiscount");

        parent::tearDown();
    }

    /**
     * Discount_Main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction("oxdiscount", "isDerived", "{return true;}");
        $this->setRequestParameter("oxid", "testId");

        // testing..
        $oView = oxNew('Discount_Main');
        $this->assertEquals('discount_main.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['edit']));
        $this->assertTrue($aViewData['edit'] instanceof discount);
    }

    /**
     * Discount_Main::Render() test case
     *
     * @return null
     */
    public function testRenderNoRealObjectId()
    {
        $this->setRequestParameter("oxid", "-1");

        // testing..
        $oView = oxNew('Discount_Main');
        $this->assertEquals('discount_main.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['oxid']));
        $this->assertEquals("-1", $aViewData['oxid']);
    }

    /**
     * Discount_Main::Save() test case
     *
     * @return null
     */
    public function testSave()
    {
        // testing..
        oxTestModules::addFunction('oxdiscount', 'save', '{ throw new Exception( "save" ); }');
        $this->getConfig()->setConfigParam("blAllowSharedEdit", true);

        // testing..
        try {
            $oView = oxNew('Discount_Main');
            $oView->save();
        } catch (Exception $oExcp) {
            $this->assertEquals("save", $oExcp->getMessage(), "error in Discount_Main::save()");

            return;
        }
        $this->fail("error in Discount_Main::save()");
    }

    /**
     * Discount_Main::Saveinnlang() test case
     *
     * @return null
     */
    public function testSaveinnlang()
    {
        // testing..
        oxTestModules::addFunction('oxdiscount', 'save', '{ throw new Exception( "save" ); }');
        $this->getConfig()->setConfigParam("blAllowSharedEdit", true);

        // testing..
        try {
            $oView = oxNew('Discount_Main');
            $oView->saveinnlang();
        } catch (Exception $oExcp) {
            $this->assertEquals("save", $oExcp->getMessage(), "error in Discount_Main::save()");

            return;
        }
        $this->fail("error in Discount_Main::save()");
    }

    /**
     * Discount_Main::getItemDiscountProductTitle() test case
     *
     * @return null
     */
    public function testgetItemDiscountProductTitle()
    {
        $sId = '1131';
        $sTitleDe = 'Flaschenverschluss EGO';
        $sTitleEn = 'Bottle Cap EGO';

        $oTestDiscount = oxNew('oxDiscount');
        $oTestDiscount->setId("_testDiscountId");
        $oTestDiscount->oxdiscount__oxshopid = new oxField($this->getConfig()->getBaseShopId());
        $oTestDiscount->oxdiscount__oxactive = new oxField(1);
        $oTestDiscount->oxdiscount__oxtitle = new oxField("Test");
        $oTestDiscount->oxdiscount__oxamount = new oxField(1);
        $oTestDiscount->oxdiscount__oxamountto = new oxField(10);
        $oTestDiscount->oxdiscount__oxitmartid = new oxField($sId);
        $oTestDiscount->oxdiscount__oxprice = new oxField(1);
        $oTestDiscount->oxdiscount__oxaddsumtype = new oxField("%");
        $oTestDiscount->oxdiscount__oxaddsum = new oxField(10);
        $oTestDiscount->save();

        $oView = $this->getProxyClass("Discount_Main");

        $oView->setNonPublicVar("_iEditLang", 0);
        $this->setRequestParameter("oxid", '-1');
        $this->assertEquals(" -- ", $oView->getItemDiscountProductTitle());

        $oView->setNonPublicVar("_iEditLang", 0);
        $this->setRequestParameter("oxid", "_testDiscountId");
        $this->assertEquals("$sId $sTitleDe", $oView->getItemDiscountProductTitle());

        $oView->setNonPublicVar("_iEditLang", 1);
        $this->setRequestParameter("oxid", "_testDiscountId");
        $this->assertEquals("$sId $sTitleEn", $oView->getItemDiscountProductTitle());
    }
}
