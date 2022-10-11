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

use \Exception;
use \oxTestModules;

/**
 * Tests for User_Extend class
 */
class UserExtendTest extends \OxidTestCase
{

    /**
     * User_Extend::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter("oxid", "oxdefaultadmin");

        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\UserExtend::class, array("_allowAdminEdit"));
        $oView->expects($this->once())->method('_allowAdminEdit')->will($this->returnValue(false));
        $this->assertEquals('user_extend.tpl', $oView->render());
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['edit']));
        $this->assertTrue($aViewData['edit'] instanceof user);
        $this->assertTrue(isset($aViewData['readonly']));
        $this->assertTrue($aViewData['readonly']);
    }

    /**
     * User_Extend::Save() test case
     *
     * @return null
     */
    public function testSave()
    {
        // testing..
        oxTestModules::addFunction('oxuser', 'load', '{ return true; }');
        oxTestModules::addFunction('oxuser', 'assign', '{ return true; }');
        oxTestModules::addFunction('oxuser', 'save', '{ throw new Exception( "save" ); }');

        oxTestModules::addFunction('oxnewssubscribed', 'loadFromUserId', '{ return true; }');
        oxTestModules::addFunction('oxnewssubscribed', 'setOptInStatus', '{ return true; }');
        oxTestModules::addFunction('oxnewssubscribed', 'setOptInEmailStatus', '{ return true; }');

        $this->setRequestParameter("oxid", "testId");
        $this->setRequestParameter("editnews", "1");
        $this->setRequestParameter("editval", array("oxaddress__oxid" => "testOxId"));

        // testing..
        try {
            $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\UserExtend::class, array("_allowAdminEdit"));
            $oView->expects($this->at(0))->method('_allowAdminEdit')->with($this->equalTo("testId"))->will($this->returnValue(true));
            $oView->save();
        } catch (Exception $oExcp) {
            $this->assertEquals("save", $oExcp->getMessage(), "Error in User_Extend::save()");

            return;
        }
        $this->fail("Error in User_Extend::save()");
    }
}
