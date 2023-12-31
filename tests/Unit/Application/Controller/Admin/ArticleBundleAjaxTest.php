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

/**
 * Tests for Actions_Order_Ajax class
 */
class ArticleBundleAjaxTest extends \OxidTestCase
{
    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $setupArticleSql = "insert into oxarticles set oxid='_testArticleBundle', oxshopid=1, oxtitle='_testArticleBundle', oxbundleid='_testBundleId'";

        oxDb::getDb()->execute($setupArticleSql);
    }

    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        oxDb::getDb()->execute("delete from oxarticles where oxid='_testArticleBundle'");

        parent::tearDown();
    }

    public function getArticleViewTable()
    {
        return $this->getTestConfig()->getShopEdition() == 'EE' ? 'oxv_oxarticles_1_de' : 'oxv_oxarticles_de';
    }

    public function getObject2CategoryViewTable()
    {
        return $this->getTestConfig()->getShopEdition() == 'EE' ? 'oxv_oxobject2category_1' : 'oxobject2category';
    }

    /**
     * ArticleBundleAjax::_getQuery() test case
     */
    public function testGetQuery()
    {
        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("from " . $this->getArticleViewTable() . " where 1  and " . $this->getArticleViewTable() . ".oxparentid = ''  and " . $this->getArticleViewTable() . ".oxid IS NOT NULL  and " . $this->getArticleViewTable() . ".oxid != ''", trim($oView->UNITgetQuery()));
    }

    /**
     * ArticleBundleAjax::_getQuery() test case
     */
    public function testGetQueryVariantsSelectionTrue()
    {
        $this->getConfig()->setConfigParam("blVariantsSelection", true);
        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("from " . $this->getArticleViewTable() . " where 1  and " . $this->getArticleViewTable() . ".oxid IS NOT NULL  and " . $this->getArticleViewTable() . ".oxid != ''", trim($oView->UNITgetQuery()));
    }

    /**
     * ArticleBundleAjax::_getQuery() test case
     */
    public function testGetQueryOxid()
    {
        $sOxid = '_testBundleOxid';
        $this->setRequestParameter("oxid", $sOxid);

        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("and " . $this->getArticleViewTable() . ".oxid IS NOT NULL  and " . $this->getArticleViewTable() . ".oxid != ''", trim($oView->UNITgetQuery()));
    }

    /**
     * ArticleBundleAjax::_getQuery() test case
     */
    public function testGetQuerySynchoxidOxid()
    {
        $sSynchoxid = '_testBundleSynchoxid';
        $this->setRequestParameter("synchoxid", $sSynchoxid);
        $sOxid = '_testBundleOxid';
        $this->setRequestParameter("oxid", $sOxid);

        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("from " . $this->getObject2CategoryViewTable() . " as oxobject2category left join " . $this->getArticleViewTable() . " on  " . $this->getArticleViewTable() . ".oxid=oxobject2category.oxobjectid  where oxobject2category.oxcatnid = '$sOxid'  and " . $this->getArticleViewTable() . ".oxid IS NOT NULL  and " . $this->getArticleViewTable() . ".oxid != '$sSynchoxid'", trim($oView->UNITgetQuery()));
    }

    /**
     * ArticleBundleAjax::_getQuery() test case
     */
    public function testGetQuerySynchoxidOxidVariantsSelectionTrue()
    {
        $sSynchoxid = '_testBundleSynchoxid';
        $this->setRequestParameter("synchoxid", $sSynchoxid);
        $sOxid = '_testBundleOxid';
        $this->setRequestParameter("oxid", $sOxid);
        $this->getConfig()->setConfigParam("blVariantsSelection", true);

        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("from " . $this->getObject2CategoryViewTable() . " as oxobject2category left join " . $this->getArticleViewTable() . " on  (" . $this->getArticleViewTable() . ".oxid=oxobject2category.oxobjectid or " . $this->getArticleViewTable() . ".oxparentid=oxobject2category.oxobjectid) where oxobject2category.oxcatnid = '$sOxid'  and " . $this->getArticleViewTable() . ".oxid IS NOT NULL  and " . $this->getArticleViewTable() . ".oxid != '$sSynchoxid'", trim($oView->UNITgetQuery()));
    }

    /**
     * ArticleBundleAjax::_addFilter() test case
     */
    public function testAddFilter()
    {
        $sParam = 'parameter';
        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals($sParam, trim($oView->UNITaddFilter($sParam)));
    }

    /**
     * ArticleBundleAjax::_addFilter() test case
     */
    public function testAddFilterVariantsSelectionTrue()
    {
        $sParam = 'parameter';
        $this->getConfig()->setConfigParam("blVariantsSelection", true);
        $oView = oxNew('article_bundle_ajax');
        $this->assertEquals("$sParam group by " . $this->getArticleViewTable() . ".oxid", trim($oView->UNITaddFilter($sParam)));
    }

    /**
     * ArticleBundleAjax::removeArticleBundle() test case
     */
    public function testRemoveArticleBundle()
    {
        $bundledArticleId = '_testArticleBundle';
        $this->setRequestParameter("oxid", $bundledArticleId);
        $this->getConfig()->setConfigParam("blVariantsSelection", true);

        $this->assertEquals(1, oxDb::getDb()->getOne("select count(oxid) from oxarticles where oxid='$bundledArticleId' and oxbundleid != ''"));
        $view = oxNew('article_bundle_ajax');
        $view->removeArticleBundle();
        $this->assertEquals(0, oxDb::getDb()->getOne("select count(oxid) from oxarticles where oxid='$bundledArticleId' and oxbundleid != ''"));
    }

    /**
     * ArticleBundleAjax::addArticleBundle() test case
     */
    public function testAddArticleBundle()
    {
        $bundledArticleId = '_testArticleBundle';
        $this->setRequestParameter("oxid", $bundledArticleId);
        $bundleId = '_testArticleBundle';
        $this->setRequestParameter("oxbundleid", $bundleId);

        $this->assertEquals(0, oxDb::getDb()->getOne("select count(oxid) from oxarticles where oxid='$bundledArticleId' and oxbundleid='$bundleId'"));
        $view = oxNew('article_bundle_ajax');
        $view->addArticleBundle();
        $this->assertEquals(1, oxDb::getDb()->getOne("select count(oxid) from oxarticles where oxid='$bundledArticleId' and oxbundleid='$bundleId'"));
    }
}
