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

use \Exception;
use \oxRegistry;
use \oxTestModules;

/**
 * oxcmp_utils tests
 */
class CmpUtilsTest extends \OxidTestCase
{
    /**
     * Testing oxcmp_utils::toCompareList()
     *
     * @return null
     */
    public function testToCompareListAdding()
    {
        $this->getConfig()->setConfigParam("bl_showCompareList", true);
        oxTestModules::addFunction('oxUtils', 'isSearchEngine', '{ return false; }');

        $this->setRequestParameter("addcompare", true);
        $this->setRequestParameter('removecompare', null);

        /** @var oxArticle|PHPUnit\Framework\MockObject\MockObject $oProduct */
        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, array("getId", "setOnComparisonList"));
        $oProduct->expects($this->exactly(2))->method('getId')->will($this->returnValue("1126"));
        $oProduct->expects($this->exactly(2))->method('setOnComparisonList')->with($this->equalTo(true));

        /** @var oxView|PHPUnit\Framework\MockObject\MockObject $oParentView */
        $oParentView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("getViewProduct", "getViewProductList"));
        $oParentView->expects($this->once())->method('getViewProduct')->will($this->returnValue($oProduct));
        $oParentView->expects($this->once())->method('getViewProductList')->will($this->returnValue(array($oProduct)));

        /** @var oxcmp_utils|PHPUnit\Framework\MockObject\MockObject $oCmp */
        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("getParent"));
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParentView));
        $oCmp->toCompareList("1126");
    }

    /**
     * Testing oxcmp_utils::toCompareList()
     *
     * @return null
     */
    public function testToCompareListRemoving()
    {
        $this->getConfig()->setConfigParam("bl_showCompareList", true);
        oxTestModules::addFunction('oxUtils', 'isSearchEngine', '{ return false; }');

        $this->setRequestParameter("addcompare", null);
        $this->setRequestParameter('removecompare', true);
        $this->setRequestParameter('aFiltcompproducts', array("1126"));

        /** @var oxArticle|PHPUnit\Framework\MockObject\MockObject $oProduct */
        $oProduct = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, array("getId", "setOnComparisonList"));
        $oProduct->expects($this->exactly(2))->method('getId')->will($this->returnValue("1126"));
        $oProduct->expects($this->exactly(2))->method('setOnComparisonList')->with($this->equalTo(false));

        /** @var oxView|PHPUnit\Framework\MockObject\MockObject $oParentView */
        $oParentView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("getViewProduct", "getViewProductList"));
        $oParentView->expects($this->once())->method('getViewProduct')->will($this->returnValue($oProduct));
        $oParentView->expects($this->once())->method('getViewProductList')->will($this->returnValue(array($oProduct)));

        /** @var oxcmp_utils|PHPUnit\Framework\MockObject\MockObject $oCmp */
        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("getParent"));
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParentView));
        $oCmp->toCompareList("1126");
    }

    /**
     * Testing oxcmp_utils::toNoticeList()
     *
     * @return null
     */
    public function testToNoticeList()
    {
        /** @var oxSession|PHPUnit\Framework\MockObject\MockObject $oSession */
        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, array('checkSessionChallenge'));
        $oSession->expects($this->once())->method('checkSessionChallenge')->will($this->returnValue(true));
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oSession);

        /** @var oxcmp_utils|PHPUnit\Framework\MockObject\MockObject $oCmp */
        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("_toList"));
        $oCmp->expects($this->once())->method('_toList')->with($this->equalTo('noticelist'), $this->equalTo('1126'), $this->equalTo(999), $this->equalTo('sel'));
        $oCmp->toNoticeList('1126', 999, 'sel');
    }

    /**
     * Testing oxcmp_utils::toWishList()
     *
     * @return null
     */
    public function testToWishList()
    {
        /** @var oxSession|PHPUnit\Framework\MockObject\MockObject $oSession */
        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, array('checkSessionChallenge'));
        $oSession->expects($this->exactly(2))->method('checkSessionChallenge')->will($this->returnValue(true));
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oSession);

        $this->getConfig()->setConfigParam("bl_showWishlist", false);

        /** @var oxcmp_utils|PHPUnit\Framework\MockObject\MockObject $oCmp */
        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("_toList"));
        $oCmp->expects($this->never())->method('_toList');
        $oCmp->toWishList('1126', 999, 'sel');

        $this->getConfig()->setConfigParam("bl_showWishlist", true);

        /** @var oxcmp_utils|PHPUnit\Framework\MockObject\MockObject $oCmp */
        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("_toList"));
        $oCmp->expects($this->once())->method('_toList')->with($this->equalTo('wishlist'), $this->equalTo('1126'), $this->equalTo(999), $this->equalTo('sel'));
        $oCmp->toWishList('1126', 999, 'sel');
    }

    /**
     * Testing oxcmp_utils::_toList()
     *
     * @return null
     */
    public function testToList()
    {
        $this->getConfig()->setConfigParam("blAllowUnevenAmounts", false);

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, array("addItemToBasket", "getItemCount"));
        $oBasket->expects($this->once())->method('addItemToBasket')->with($this->equalTo("1126"), $this->equalTo(999), $this->equalTo('sel'));
        $oBasket->expects($this->once())->method('getItemCount');

        $oUser = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, array("getBasket"));
        $oUser->expects($this->once())->method('getBasket')->with($this->equalTo('testList'))->will($this->returnValue($oBasket));

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("getUser"));
        $oCmp->expects($this->once())->method('getUser')->will($this->returnValue($oUser));
        $oCmp->UNITtoList('testList', '1126', 999, 'sel');
    }

    /**
     * Testing oxcmp_utils::render()
     *
     * @return null
     */
    public function testRenderCompareIsOff()
    {
        $this->getConfig()->setConfigParam("bl_showCompareList", false);
        $this->setRequestParameter('wishid', "testWishId");
        oxTestModules::addFunction('oxuser', 'load', '{ return true; }');

        $oParentView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("setMenueList"));
        $oParentView->expects($this->at(0))->method('setMenueList');

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("getParent"));
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParentView));
        $this->assertNull($oCmp->render());
    }

    /**
     * Testing oxcmp_utils::render()
     *
     * @return null
     */
    public function testRender()
    {
        $this->getConfig()->setConfigParam("bl_showCompareList", true);
        $this->getConfig()->setConfigParam("blDisableNavBars", false);

        $this->getSession()->setVariable('wishid', "testWishId");
        $this->getSession()->setVariable('aFiltcompproducts', array("1126"));

        oxTestModules::addFunction('oxuser', 'load', '{ return true; }');

        $oParentView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, array("setMenueList"));
        $oParentView->expects($this->at(0))->method('setMenueList');

        $oCmp = $this->getMock(\OxidEsales\Eshop\Application\Component\UtilsComponent::class, array("getParent"));
        $oCmp->expects($this->once())->method('getParent')->will($this->returnValue($oParentView));
        $oCmp->render();
    }
}
