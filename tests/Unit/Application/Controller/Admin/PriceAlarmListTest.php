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

use \stdClass;

/**
 * Tests for PriceAlarm_List class
 */
class PriceAlarmListTest extends \OxidTestCase
{

    /**
     * PriceAlarm_List::BuildSelectString() test case
     *
     * @return null
     */
    public function testBuildSelectString()
    {
        $sViewName = getViewName("oxpricealarm");
        $sArtViewName = getViewName("oxarticles");

        $sSql = "select {$sViewName}.*, {$sArtViewName}.oxtitle AS articletitle, ";
        $sSql .= "oxuser.oxlname as userlname, oxuser.oxfname as userfname ";
        $sSql .= "from {$sViewName} ";
        $sSql .= "left join {$sArtViewName} on {$sArtViewName}.oxid = {$sViewName}.oxartid ";
        $sSql .= "left join oxuser on oxuser.oxid = {$sViewName}.oxuserid WHERE 1 ";

        // testing..
        $oView = oxNew('PriceAlarm_List');
        $this->assertEquals($sSql, $oView->UNITbuildSelectString(new stdClass()));
    }

    /**
     * PriceAlarm_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $oView = oxNew('PriceAlarm_List');
        $this->assertEquals('pricealarm_list.tpl', $oView->render());
    }

    /**
     * PriceAlarm_List::BuildWhere() test case
     *
     * @return null
     */
    public function testBuildWhere()
    {
        $this->setRequestParameter('where', array("oxpricealarm" => array("oxprice" => 15), "oxarticles" => array("oxprice" => 15)));

        $sViewName = getViewName("oxpricealarm");
        $sArtViewName = getViewName("oxarticles");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\PriceAlarmList::class, array("_authorize"));
        $oView->expects($this->any())->method('_authorize')->will($this->returnValue(true));
        $oView->init();

        $queryWhereParts = $oView->buildWhere();
        $this->assertEquals('%15%', $queryWhereParts[$sViewName . '.oxprice']);
        $this->assertEquals('%15%', $queryWhereParts[$sArtViewName . '.oxprice']);
    }
}
