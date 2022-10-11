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

/**
 * Tests for Dynscreen class
 */
class DynscreenTest extends \OxidTestCase
{

    /**
     * Dynscreen::SetupNavigation() test case
     *
     * @return null
     */
    public function testSetupNavigation()
    {
        $sNode = "testNode";
        $this->setRequestParameter("menu", $sNode);
        $this->setRequestParameter('actedit', 1);

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, array("getListUrl", "getEditUrl", "getTabs", "getActiveTab", "getBtn"));
        $oNavigation->expects($this->any())->method('getActiveTab')->will($this->returnValue("testEdit"));
        $oNavigation->expects($this->once())->method('getListUrl')->with($this->equalTo($sNode))->will($this->returnValue("testListUrl"));
        $oNavigation->expects($this->once())->method('getEditUrl')->with($this->equalTo($sNode), $this->equalTo(1))->will($this->returnValue("testEditUrl"));
        $oNavigation->expects($this->once())->method('getTabs')->with($this->equalTo($sNode), $this->equalTo(1))->will($this->returnValue("editTabs"));
        $oNavigation->expects($this->once())->method('getBtn')->with($this->equalTo($sNode))->will($this->returnValue("testBtn"));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\DynamicScreenController::class, array("getNavigation"));
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));

        $oView->UNITsetupNavigation($sNode);
        $this->assertEquals("testListUrl&actedit=1", $oView->getViewDataElement("listurl"));
        $this->assertEquals("?testEditUrl&actedit=1", $oView->getViewDataElement("editurl"));
        $this->assertEquals("editTabs", $oView->getViewDataElement("editnavi"));
        $this->assertEquals("testEdit", $oView->getViewDataElement("actlocation"));
        $this->assertEquals("testEdit", $oView->getViewDataElement("default_edit"));
        $this->assertEquals(1, $oView->getViewDataElement("actedit"));
        $this->assertEquals("testBtn", $oView->getViewDataElement("bottom_buttons"));
    }

    /**
     * Dynscreen::GetViewId() test case
     *
     * @return null
     */
    public function testGetViewId()
    {
        $oView = oxNew('Dynscreen');
        $this->assertEquals('dyn_menu', $oView->getViewId());
    }

    /**
     * Dynscreen::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\DynamicScreenController::class, array("_setupNavigation"));
        $oView->expects($this->once())->method('_setupNavigation');
        $this->assertEquals('dynscreen.tpl', $oView->render());
    }
}
