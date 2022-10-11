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
 * Tests for Content_List class
 */
class ContentListTest extends \OxidTestCase
{

    /**
     * Content_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter("folder", "sTestFolder");

        // testing..
        $oView = oxNew('Content_List');
        $sTplName = $oView->render();
        $aViewData = $oView->getViewData();
        $this->assertEquals($this->getConfig()->getConfigParam('afolder'), $aViewData["CMSFOLDER_EMAILS"]);
        $this->assertEquals("sTestFolder", $aViewData["folder"]);

        $this->assertEquals('content_list.tpl', $sTplName);
    }

    /**
     * Content_List::PrepareWhereQuery() test case
     *
     * @return null
     */
    public function testPrepareWhereQueryUserDefinedFolder()
    {
        $this->setRequestParameter("folder", "testFolder");
        $sViewName = getviewName("oxcontents");

        // defining parameters
        $oView = oxNew('Content_List');
        $sResQ = $oView->UNITprepareWhereQuery(array(), "");

        $sQ = " and {$sViewName}.oxfolder = 'testFolder'";

        $this->assertEquals($sQ, $sResQ);
    }
}
