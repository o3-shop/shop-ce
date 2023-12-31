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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use OxidEsales\EshopCommunity\Application\Model\ArticleList;

use \oxField;
use \oxTestModules;

/**
 * Testing start class
 */
class StartTest extends \OxidTestCase
{
    public function testGetTitleSuffix()
    {
        $oShop = oxNew('oxShop');
        $oShop->oxshops__oxstarttitle = $this->getMock(\OxidEsales\Eshop\Core\Field::class, array('__get'));
        $oShop->oxshops__oxstarttitle->expects($this->once())->method('__get')->will($this->returnValue('testsuffix'));

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getActiveShop'));
        $oConfig->expects($this->once())->method('getActiveShop')->will($this->returnValue($oShop));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\StartController::class, array('getConfig'));
        $oView->expects($this->once())->method('getConfig')->will($this->returnValue($oConfig));
        $this->assertEquals('testsuffix', $oView->getTitleSuffix());
    }

    public function testGetCanonicalUrl()
    {
        oxTestModules::addFunction("oxutils", "seoIsActive", "{return true;}");

        $oViewConfig = $this->getMock(\OxidEsales\Eshop\Core\ViewConfig::class, array("getHomeLink"));
        $oViewConfig->expects($this->once())->method('getHomeLink')->will($this->returnValue("testHomeLink"));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\StartController::class, array("getViewConfig"));
        $oView->expects($this->once())->method('getViewConfig')->will($this->returnValue($oViewConfig));

        $this->assertEquals('testHomeLink', $oView->getCanonicalUrl());
    }

    public function testGetRealSeoCanonicalUrl()
    {
        oxTestModules::addFunction("oxutils", "seoIsActive", "{return true;}");

        $oView = oxNew('start');
        $this->assertEquals($this->getConfig()->getConfigParam("sShopURL"), $oView->getCanonicalUrl());
    }

    public function testGetTopArticleList()
    {
        $oStart = $this->getProxyClass('start');

        $aList = $oStart->getTopArticleList();
        $this->assertTrue($aList instanceof articlelist);
        $this->assertEquals(1, $aList->count());

        $expectedId = $this->getTestConfig()->getShopEdition() == 'EE'? "2275" : "1849";
        $this->assertEquals($expectedId, $aList->current()->getId());
    }

    public function testGetNewestArticles()
    {
        $oStart = $this->getProxyClass('start');

        $aList = $oStart->getNewestArticles();
        $this->assertTrue($aList instanceof articlelist);
        $this->assertEquals(4, $aList->count());
    }

    public function testGetCatOfferArticle()
    {
        $oStart = $this->getProxyClass('start');

        $oArt = $oStart->getCatOfferArticle();

        $expectedId = $this->getTestConfig()->getShopEdition() == 'EE'? "1351" : "1126";
        $this->assertEquals($expectedId, $oArt->getId());
    }

    public function testGetCatOfferArticleList()
    {
        $oStart = $this->getProxyClass('start');

        $aList = $oStart->getCatOfferArticleList();
        $this->assertTrue($aList instanceof articlelist);
        $this->assertEquals(2, $aList->count());
    }

    public function testPrepareMetaKeyword()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadAktion', 1);

        $oArticle = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, array('getLongDescription'));
        $oArticle->expects($this->once())->method('getLongDescription')->will($this->returnValue(new oxField('testlongdesc')));

        $oStart = $this->getMock(\OxidEsales\Eshop\Application\Controller\StartController::class, array('getFirstArticle'));
        $oStart->expects($this->once())->method('getFirstArticle')->will($this->returnValue($oArticle));

        $oView = oxNew('oxubase');
        $this->assertEquals($oView->UNITprepareMetaKeyword('testlongdesc'), $oStart->UNITprepareMetaKeyword(null));
    }

    public function testViewMetaKeywords()
    {
        oxTestModules::addFunction('oxUtilsServer', 'getServerVar', '{ if ( $aA[0] == "HTTP_HOST") { return "shop.com/"; } else { return "test.php";} }');

        $oStart = $this->getProxyClass('start');
        $oStart->render();
        $aMetaKeywords = $oStart->getMetaKeywords();

        $this->assertTrue(strlen($aMetaKeywords) > 0);
    }

    public function testPrepareMetaDescription()
    {
        $this->getConfig()->setConfigParam('bl_perfLoadAktion', 1);

        $oArticle = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, array('getLongDescription'));
        $oArticle->expects($this->once())->method('getLongDescription')->will($this->returnValue(new oxField('testlongdesc')));

        $oStart = $this->getMock(\OxidEsales\Eshop\Application\Controller\StartController::class, array('getFirstArticle'));
        $oStart->expects($this->once())->method('getFirstArticle')->will($this->returnValue($oArticle));

        $oView = oxNew('oxubase');
        $this->assertEquals($oView->UNITprepareMetaDescription('- testlongdesc'), $oStart->UNITprepareMetaDescription(null));
    }

    public function testViewMetaDescritpion()
    {
        oxTestModules::addFunction('oxUtilsServer', 'getServerVar', '{ if ( $aA[0] == "HTTP_HOST") { return "shop.com/"; } else { return "test.php";} }');

        $oStart = $this->getProxyClass('start');
        $oStart->render();
        $aMetaKeywords = $oStart->getMetaDescription();

        $this->assertTrue(strlen($aMetaKeywords) > 0);
    }

    public function testGetBanners()
    {
        $oArticleList = $this->getMock(\OxidEsales\Eshop\Application\Model\ActionList::class, array('loadBanners'));
        $oArticleList->expects($this->once())->method('loadBanners');

        oxTestModules::addModuleObject('oxActionList', $oArticleList);

        $oView = oxNew('start');
        $oView->getBanners();
    }
}
