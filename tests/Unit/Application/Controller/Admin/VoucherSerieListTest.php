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

use \oxTestModules;

/**
 * Tests for VoucherSerie_List class
 */
class VoucherSerieListTest extends \OxidTestCase
{

    /**
     * VoucherSerie_List::DeleteEntry() test case
     *
     * @return null
     */
    public function testDeleteEntry()
    {
        $this->markTestSkipped('Overwork due => tests are stoping without message.');

        oxTestModules::addFunction("oxUtilsServer", "getOxCookie", "{return array(1);}");
        oxTestModules::addFunction("oxUtils", "checkAccessRights", "{return true;}");
        oxTestModules::addFunction('oxvoucherserie', 'load', '{ return true; }');
        oxTestModules::addFunction('oxvoucherserie', 'deleteVoucherList', '{ return true; }');

        $oSess = $this->getMock(\OxidEsales\Eshop\Core\Session::class, array('checkSessionChallenge'));
        $oSess->expects($this->any())->method('checkSessionChallenge')->will($this->returnValue(true));

        $oView = $this->getMock($this->getProxyClassName('VoucherSerie_List'), array('getSession'));
        $oView->expects($this->any())->method('getSession')->will($this->returnValue($oSess));

        $oView->deleteEntry();
    }

    /**
     * VoucherSerie_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('VoucherSerie_List');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertNull($aViewData["allowSharedEdit"]);
        $this->assertNull($aViewData["malladmin"]);
        $this->assertNull($aViewData["updatelist"]);
        $this->assertNull($aViewData["sort"]);

        $this->assertEquals('voucherserie_list.tpl', $sTplName);
    }
}
