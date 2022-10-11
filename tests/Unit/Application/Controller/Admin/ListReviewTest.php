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

use DOMDocument;

/**
 * Tests for List_Review class
 */
class ListReviewTest extends \OxidTestCase
{

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxlinks');
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxcontents');
        $this->cleanUpTable('oxobject2category');

        if (isset($_POST['oxid'])) {
            unset($_POST['oxid']);
        }

        $this->getConfig()->setGlobalParameter('ListCoreTable', null);

        parent::tearDown();
    }

    /**
     * List_Review::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $oNavTree = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, array("getDomXml"));
        $oNavTree->expects($this->once())->method('getDomXml')->will($this->returnValue(new DOMDocument));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ListReview::class, array("getNavigation"));
        $oView->expects($this->at(0))->method('getNavigation')->will($this->returnValue($oNavTree));
        $this->assertEquals("list_review.tpl", $oView->render());
    }

    /**
     * Testing if methods removes parent id checking from sql
     *
     * @return null
     */
    public function testPrepareWhereQuery()
    {
        $oArtList = oxNew('Article_List');
        $sSql = $oArtList->UNITbuildSelectString(oxNew('oxArticle'));
        $sSql = $oArtList->UNITprepareWhereQuery(array(), $sSql);

        // checking if exists string oxarticle.oxparentid = ''
        $blCheckForParent = preg_match("/\s+and\s+" . getViewName('oxarticles') . ".oxparentid\s+=\s+''/", $sSql);
        $this->assertTrue((bool) $blCheckForParent);

        $oList = oxNew('List_Review');
        $sSql = $oList->UNITbuildSelectString("");
        $sSql = $oList->UNITprepareWhereQuery(array(), $sSql);

        // checking if not exists string oxarticle.oxparentid = ''
        $blCheckForParent = preg_match("/\s+and\s+" . getViewName('oxarticles') . ".oxparentid\s+=\s+''/", $sSql);
        $this->assertFalse((bool) $blCheckForParent);
    }

    /**
     * Testing if methods removes parent id checking from sql
     *
     * @return null
     */
    public function testPrepareWhereQueryCase2()
    {
        $oArtList = oxNew('Article_List');
        $sSql = $oArtList->UNITbuildSelectString(oxNew('oxArticle'));
        $sSql = $oArtList->UNITprepareWhereQuery(array(), $sSql);

        // checking if exists string oxarticle.oxparentid = ''
        $blCheckForParent = preg_match("/\s+and\s+" . getViewName('oxarticles') . ".oxparentid\s+=\s+''/", $sSql);
        $this->assertTrue((bool) $blCheckForParent);

        $oList = oxNew('List_Review');
        $sSql = $oList->UNITbuildSelectString("");
        $sSql = $oList->UNITprepareWhereQuery(array(), $sSql);

        // checking if not exists string oxarticle.oxparentid = ''
        $blCheckForParent = preg_match("/\s+and\s+" . getViewName('oxarticles') . ".oxparentid\s+=\s+''/", $sSql);
        $this->assertFalse((bool) $blCheckForParent);
    }
}
