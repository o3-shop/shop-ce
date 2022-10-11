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

use \oxField;
use \Exception;
use \oxDb;
use \oxTestModules;

/**
 * Testing Order_Remark class
 */
class OrderRemarkTest extends \OxidTestCase
{

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        oxDb::getDB()->execute('delete from oxremark where oxtext = "test text"');
        $this->cleanUpTable('oxorder');
        parent::tearDown();
    }

    /**
     * order_remark::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter("oxid", "testId");
        $this->setRequestParameter("rem_oxid", "testId");

        $oView = oxNew('order_remark');
        $this->assertEquals("order_remark.tpl", $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['allremark']));
        $this->assertTrue($aViewData['allremark'] instanceof \OxidEsales\EshopCommunity\Core\Model\ListModel);
    }

    /**
     * order_remark::save() test case
     *
     * @return null
     */
    public function testSave()
    {
        $this->setRequestParameter('oxid', '_testOrder');
        $this->setRequestParameter('remarktext', 'test text');
        $oOrder = oxNew('oxbase');
        $oOrder->init('oxorder');
        $oOrder->setId('_testOrder');
        $oOrder->oxorder__oxuserid = new oxField('oxdefaultadmin');
        $oOrder->save();
        $oView = oxNew('order_remark');
        $oView->save();
        $oRemark = oxNew("oxRemark");
        $oRemark->load("_testRemark");
        $this->assertEquals('r', oxDb::getDB()->getOne('select oxtype from oxremark where oxtext = "test text"'));
        $this->assertEquals('oxdefaultadmin', oxDb::getDB()->getOne('select oxparentid from oxremark where oxtext = "test text"'));
    }

    /**
     * order_remark::Render() test case
     *
     * @return null
     */
    public function testDelete()
    {
        oxTestModules::addFunction('oxRemark', 'delete', '{ throw new Exception( "delete" ); }');

        // testing..
        try {
            $oView = oxNew('order_remark');
            $oView->delete();
        } catch (Exception $oExcp) {
            $this->assertEquals("delete", $oExcp->getMessage(), "Error in order_remark::delete()");

            return;
        }
        $this->fail("Error in order_remark::delete()");
    }
}
