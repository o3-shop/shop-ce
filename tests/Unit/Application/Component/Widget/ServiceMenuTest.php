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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component\Widget;

use \oxTestModules;

/**
 * Tests for oxwServiceMenu class
 */
class ServiceMenuTest extends \OxidTestCase
{
    /**
     * Testing oxwServiceMenu::getCompareItemsCnt()
     *
     * @return null
     */
    public function testGetCompareItemsCnt()
    {
        $oCompare = $this->getMock(\OxidEsales\Eshop\Application\Controller\CompareController::class, array("getCompareItemsCnt"));
        $oCompare->expects($this->once())->method("getCompareItemsCnt")->will($this->returnValue(10));
        oxTestModules::addModuleObject('compare', $oCompare);

        $oServiceMenu = oxNew('oxwServiceMenu');
        $this->assertEquals(10, $oServiceMenu->getCompareItemsCnt());
    }

    /**
     * Testing oxwServiceMenu::getCompareItems()
     *
     * @return null
     */
    public function testGetCompareItems()
    {
        $aItems = array("testId1" => "testVal1", "testId2" => "testVal2", "testId3" => "testVal3");
        $oCompare = $this->getMock(\OxidEsales\Eshop\Application\Controller\CompareController::class, array("getCompareItems"));
        $oCompare->expects($this->once())->method("getCompareItems")->will($this->returnValue($aItems));
        oxTestModules::addModuleObject('compare', $oCompare);

        $oServiceMenu = oxNew('oxwServiceMenu');
        $this->assertEquals($aItems, $oServiceMenu->getCompareItems());
    }

    /**
     * Testing oxwServiceMenu::getCompareItems()
     *
     * @return null
     */
    public function testGetCompareItemsInJson()
    {
        $aItems = array("testId1" => "testVal1", "testId2" => "testVal2", "testId3" => "testVal3");
        $aResult = '{"testId1":"testVal1","testId2":"testVal2","testId3":"testVal3"}';
        $oCompare = $this->getMock(\OxidEsales\Eshop\Application\Controller\CompareController::class, array("getCompareItems"));
        $oCompare->expects($this->once())->method("getCompareItems")->will($this->returnValue($aItems));
        oxTestModules::addModuleObject('compare', $oCompare);

        $oServiceMenu = oxNew('oxwServiceMenu');
        $this->assertEquals($aResult, $oServiceMenu->getCompareItems(true));
    }
}
