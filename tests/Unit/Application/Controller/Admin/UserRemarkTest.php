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

use OxidEsales\EshopCommunity\Application\Model\User;
use OxidEsales\EshopCommunity\Core\Model\ListModel;

use \oxField;
use \Exception;
use \oxTestModules;

/**
 * Testing User_Remark class
 */
class UserRemarkTest extends \OxidTestCase
{

    /**
     * user_remark::render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction('oxRemark', 'load($sId)', '{$this->oxremark__oxtext = new oxField("text-$sId");$this->oxremark__oxheader = new oxField("header-$sId");}');
        $this->setRequestParameter("oxid", "testId");
        $this->setRequestParameter("rem_oxid", "testId");

        $oView = oxNew('user_remark');
        $this->assertEquals("user_remark.tpl", $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['edit']));
        $this->assertTrue($aViewData['edit'] instanceof user);
        $this->assertTrue($aViewData['allremark'] instanceof ListModel);
        $this->assertEquals('text-testId', $aViewData['remarktext']);
        $this->assertEquals('header-testId', $aViewData['remarkheader']);
    }

    /**
     * user_remark::Save() test case
     *
     * @return null
     */
    public function testSave()
    {
        oxTestModules::addFunction('oxremark', 'load', '{ return true; }');
        oxTestModules::addFunction('oxremark', 'save', '{ throw new Exception( "save" ); }');

        $this->setRequestParameter('oxid', 'oxdefaultadmin');
        $this->setRequestParameter('remarktext', 'test text');
        $this->setRequestParameter('remarkheader', 'test header');

        try {
            $oView = oxNew('user_remark');
            $oView->save();
        } catch (Exception $oExcp) {
            $this->assertEquals("save", $oExcp->getMessage(), "Error in user_remark::save()");

            return;
        }

        $this->fail("Error in user_remark::save()");
    }

    /**
     * user_remark::testDelete() test case
     *
     * @return null
     */
    public function testDelete()
    {
        oxTestModules::addFunction('oxremark', 'delete', '{ throw new Exception( "delete" ); }');

        try {
            $oView = oxNew('user_remark');
            $oView->delete();
        } catch (Exception $oExcp) {
            $this->assertEquals("delete", $oExcp->getMessage(), "Error in user_remark::delete()");

            return;
        }

        $this->fail("Error in user_remark::delete()");
    }
}
