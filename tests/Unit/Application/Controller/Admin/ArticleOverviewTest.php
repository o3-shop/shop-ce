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

use OxidEsales\EshopCommunity\Application\Model\Article;
use \oxField;
use \oxTestModules;

/**
 * Tests for Article_Overview class
 */
class ArticleOverviewTest extends \OxidTestCase
{

    /**
     * Tear down
     *
     * @return null
     */
    protected function tearDown(): void
    {
        //
        $this->cleanUpTable("oxorderarticles");

        parent::tearDown();
    }

    /**
     * Article_Overview::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction('oxarticle', 'isDerived', '{ return true; }');
        $this->setRequestParameter("oxid", "1126");

        $oBase = oxNew('oxbase');
        $oBase->init("oxorderarticles");
        $oBase->setId("_testOrderArticleId");
        $oBase->oxorderarticles__oxorderid = new oxField("testOrderId");
        $oBase->oxorderarticles__oxamount = new oxField(1);
        $oBase->oxorderarticles__oxartid = new oxField("1126");
        $oBase->oxorderarticles__oxordershopid = new oxField($this->getConfig()->getShopId());
        $oBase->save();

        // testing..
        $oView = oxNew('Article_Overview');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertTrue($aViewData["edit"] instanceof Article);
        $this->assertNull($aViewData["afolder"]);
        $this->assertNull($aViewData["aSubclass"]);

        $this->assertEquals('article_overview.tpl', $sTplName);
    }

    /**
     * Article_Overview::Render() test case
     *
     * @return null
     */
    public function testRenderPArentBuyable()
    {
        oxTestModules::addFunction('oxarticle', 'isDerived', '{ return true; }');
        $this->setRequestParameter("oxid", "1126");
        $this->getConfig()->setConfigParam("blVariantParentBuyable", true);

        // testing..
        $oView = oxNew('Article_Overview');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertTrue($aViewData["edit"] instanceof Article);
        $this->assertNull($aViewData["afolder"]);
        $this->assertNull($aViewData["aSubclass"]);

        $this->assertEquals('article_overview.tpl', $sTplName);
    }
}
