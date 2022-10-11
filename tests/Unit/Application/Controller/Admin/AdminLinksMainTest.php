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

use \oxDb;
use \oxTestModules;

/**
 * Tests for Adminlinks_Main class
 */
class AdminLinksMainTest extends \OxidTestCase
{

    /**
     * Adminlinks_Main::render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter("oxid", -1);
        $this->setRequestParameter("saved_oxid", -1);

        // testing..
        $oView = oxNew('Adminlinks_main');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertEquals('-1', $aViewData["oxid"]);
        $this->assertEquals('adminlinks_main.tpl', $sTplName);
    }

    /**
     * Adminlinks_Main::Render() test case
     *
     * @return null
     */
    public function testRenderWithExistingLink()
    {
        $this->setRequestParameter("oxid", oxDb::getDb()->getOne("select oxid from oxlinks"));

        // testing..
        $oView = oxNew('Adminlinks_main');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertNotNull($aViewData["edit"]);
        $this->assertEquals("adminlinks_main.tpl", $sTplName);
    }

    /**
     * Adminlinks_Main::save() test case
     *
     * @return null
     */
    public function testSaveinnlang()
    {
        $this->setRequestParameter("oxid", "xxx");

        // testing..
        $oView = oxNew('Adminlinks_main');
        $oView->saveinnlang();
        $aViewData = $oView->getViewData();

        $this->assertNotNull($aViewData["updatelist"]);
        $this->assertEquals(1, $aViewData["updatelist"]);
    }

    /**
     * Adminlinks_Main::save() test case
     *
     * @return null
     */
    public function testSave()
    {
        $this->setRequestParameter("oxid", "xxx");

        // testing..
        $oView = oxNew('Adminlinks_main');
        $oView->save();
        $aViewData = $oView->getViewData();

        $this->assertNotNull($aViewData["updatelist"]);
        $this->assertEquals(1, $aViewData["updatelist"]);
    }
}
