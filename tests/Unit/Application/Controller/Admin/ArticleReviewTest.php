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
use \oxDb;
use \oxTestModules;

/**
 * Tests for Article_Review class
 */
class ArticleReviewTest extends \OxidTestCase
{

    /**
     * Article_Review test setup
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();

        $articleId = $this->getTestArticleId();

        oxDb::getDb()->execute(
            'replace into oxreviews (OXID, OXACTIVE, OXOBJECTID, OXTYPE, OXTEXT, OXUSERID, OXCREATE, OXLANG, OXRATING)
                        values ("_test_i1", 1, "' . $articleId . '", "oxarticle", "aa", "' . oxADMIN_LOGIN . '", "0000-00-00 00:00:00", "0", "3")'
        );
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxreviews');
        parent::tearDown();
    }

    /**
     * Article_Review::render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->setRequestParameter("oxid", $this->getTestArticleId());
        $this->setRequestParameter("rev_oxid", "_test_i1");
        oxTestModules::addFunction('oxarticle', 'isDerived', '{ return true; }');

        // testing..
        $oView = oxNew('Article_Review');
        $sTplName = $oView->render();

        // testing view data
        $aViewData = $oView->getViewData();
        $this->assertTrue($aViewData["edit"] instanceof Article);

        $this->assertEquals('article_review.tpl', $sTplName);
    }

    /**
     * Article_Review::delete() test case
     *
     * @return null
     */
    public function testSave()
    {
        $oReview = oxNew("oxreview");
        $oReview->setId("_testReviewId");
        $oReview->oxreviews__oxactive = new oxField(1);
        $oReview->oxreviews__oxobjectid = new oxField("_testObjectId");
        $oReview->oxreviews__oxtype = new oxField("oxarticle");
        $oReview->save();

        $oDb = oxDb::getDb();

        $this->assertTrue((bool) $oDb->getOne("select 1 from oxreviews where oxid = '_testReviewId'"));

        $this->setRequestParameter("rev_oxid", "_testReviewId");
        $this->setRequestParameter("editval", array('oxreviews__oxtext' => 6, 'oxreviews__oxrating' => 6));
        $this->getConfig()->setConfigParam("blGBModerate", "_testReviewId");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ArticleReview::class, array("resetContentCache"));
        $oView->expects($this->once())->method('resetContentCache');
        $oView->save();

        $this->assertTrue((bool) $oDb->getOne("select 1 from oxreviews where oxtext = '6' and oxrating = '6'"));
    }

    /**
     * Article_Review::delete() test case
     *
     * @return null
     */
    public function testDelete()
    {
        $oReview = oxNew("oxreview");
        $oReview->setId("testReviewId");
        $oReview->oxreviews__oxactive = new oxField(1);
        $oReview->oxreviews__oxobjectid = new oxField("testObjectId");
        $oReview->oxreviews__oxtype = new oxField("oxarticle");
        $oReview->save();

        $oDb = oxDb::getDb();

        $this->assertTrue((bool) $oDb->getOne("select 1 from oxreviews where oxid = 'testReviewId'"));
        $this->setRequestParameter("rev_oxid", "testReviewId");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ArticleReview::class, array("resetContentCache"));
        $oView->expects($this->once())->method('resetContentCache');

        $oView->delete();

        $this->assertFalse((bool) $oDb->getOne("select 1 from oxreviews where oxid = 'testReviewId'"));
    }

    /**
     * Article_Review::_getReviewList() test case
     *
     * @return null
     */
    public function testGetReviewList()
    {
        $articleVariantId = $this->getTestArticleVariantId();
        oxDb::getDb()->execute(
            'replace into oxreviews (OXID, OXACTIVE, OXOBJECTID, OXTYPE, OXTEXT, OXUSERID, OXCREATE, OXLANG, OXRATING)
                        values ("_test_i2", 1, "' . $articleVariantId . '", "oxarticle", "aa", "' . oxADMIN_LOGIN . '", "0000-00-00 00:00:00", "0", "3")'
        );
        oxTestModules::publicize('article_review', '_getReviewList');
        $o = oxNew('article_review');
        $oA = oxNew('oxArticle');
        $oA->load($this->getTestArticleId());
        $this->getConfig()->setConfigParam('blShowVariantReviews', false);
        $this->assertEquals(1, count($o->p_getReviewList($oA)));
        $this->getConfig()->setConfigParam('blShowVariantReviews', true);
        $this->assertEquals(2, count($o->p_getReviewList($oA)));
    }

    /**
     * @return string Test Article Id
     */
    protected function getTestArticleId()
    {
        return $this->getTestConfig()->getShopEdition() == 'EE' ? '2363' : '2077';
    }

    /**
     * @return string Test Article Variant Id
     */
    protected function getTestArticleVariantId()
    {
        return $this->getTestConfig()->getShopEdition() == 'EE' ? '2363-01' : '8a142c4100e0b2f57.59530204';
    }
}
