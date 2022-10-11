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
 * Tests for Actions_List class
 */
class ActionsListTest extends \OxidTestCase
{

    /**
     * Actions_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = $this->getProxyClass("Actions_List");
        $sTplName = $oView->render();
        $aViewData = $oView->getViewData();

        $this->assertEquals('oxactions', $oView->getNonPublicVar("_sListClass"));
        $this->assertEquals(array('oxactions' => array('oxtitle' => 'asc')), $oView->getListSorting());
        $this->assertEquals('actions_list.tpl', $sTplName);
    }

    /**
     * Actions_List::Render() test case
     *
     * @return null
     */
    public function testPromotionsRender()
    {
        $this->setRequestParameter("displaytype", "testType");

        $oView = $this->getProxyClass("Actions_List");
        $sTplName = $oView->render();
        $aViewData = $oView->getViewData();

        $this->assertEquals('oxactions', $oView->getNonPublicVar("_sListClass"));
        $this->assertEquals(array('oxactions' => array('oxtitle' => 'asc')), $oView->getListSorting());
        $this->assertEquals('testType', $aViewData['displaytype']);
        $this->assertEquals('actions_list.tpl', $sTplName);
    }

    /**
     * Actions_List::_prepareWhereQuery() test case
     */
    public function testPrepareWhereQuery()
    {
        $iTime = time();
        oxTestModules::addFunction('oxUtilsDate', 'getTime', '{ return ' . $iTime . '; }');
        $sTable = getViewName("oxactions");
        $sNow = date('Y-m-d H:i:s', $iTime);

        $oView = oxNew('Actions_List');

        $sQ = " and $sTable.oxactivefrom < '$sNow' and $sTable.oxactiveto > '$sNow' ";
        $this->setRequestParameter('displaytype', 1);
        $this->assertEquals($sQ, $oView->UNITprepareWhereQuery(array(), ""));

        $sQ = " and $sTable.oxactivefrom > '$sNow' ";
        $this->setRequestParameter('displaytype', 2);
        $this->assertEquals($sQ, $oView->UNITprepareWhereQuery(array(), ""));

        $sQ = " and $sTable.oxactiveto < '$sNow' and $sTable.oxactiveto != '0000-00-00 00:00:00' ";
        $this->setRequestParameter('displaytype', 3);
        $this->assertEquals($sQ, $oView->UNITprepareWhereQuery(array(), ""));
    }
}
