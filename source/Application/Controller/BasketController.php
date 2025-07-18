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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketContentMarkGenerator;
use OxidEsales\Eshop\Application\Model\ListObject;
use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use Psr\Log\LoggerInterface;

/**
 * Current session shopping cart (basket item list).
 * Contains with user selected articles (with detail information), list of
 * similar products, top offer articles.
 * O3-Shop -> SHOPPING CART.
 */
class BasketController extends FrontendController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/checkout/basket.tpl';

    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_blIsOrderStep = true;

    /**
     * all basket articles
     *
     * @var object
     */
    protected $_oBasketArticles = null;

    /**
     * Similar List
     *
     * @var object
     */
    protected $_oSimilarList = null;

    /**
     * Recomm List
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_oRecommList = null;

    /**
     * First basket product object. It is used to load
     * recommendation list info and similar product list
     *
     * @var Article
     */
    protected $_oFirstBasketProduct = null;

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Wrapping objects list
     *
     * @var ListModel
     */
    protected $_oWrappings = null;

    /**
     * Card objects list
     *
     * @var ListModel
     */
    protected $_oCards = null;

    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_aSimilarRecommListIds = null;

    /**
     * Executes parent::render(), creates list with basket articles
     * Returns name of template file basket::_sThisTemplate (for Search
     * engines return "content.tpl" template to avoid fake orders etc.).
     *
     * @return  string   $this->_sThisTemplate  current template file name
     */
    public function render()
    {
        if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
            Registry::getSession()->getBasketReservations()->renewExpiration();
        }

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * Return the current articles from the basket
     *
     * @return object | bool
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketArticles()
    {
        if ($this->_oBasketArticles === null) {
            $this->_oBasketArticles = false;

            // passing basket articles
            if ($oBasket = Registry::getSession()->getBasket()) {
                $this->_oBasketArticles = $oBasket->getBasketArticles();
            }
        }

        return $this->_oBasketArticles;
    }

    /**
     * return the basket articles
     *
     * @return object | bool
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getFirstBasketProduct()
    {
        if ($this->_oFirstBasketProduct === null) {
            $this->_oFirstBasketProduct = false;

            $aBasketArticles = $this->getBasketArticles();
            if (is_array($aBasketArticles) && $oProduct = reset($aBasketArticles)) {
                $this->_oFirstBasketProduct = $oProduct;
            }
        }

        return $this->_oFirstBasketProduct;
    }

    /**
     * return the similar articles
     *
     * @return object | bool
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketSimilarList()
    {
        if ($this->_oSimilarList === null) {
            $this->_oSimilarList = false;

            // similar product info
            if ($oProduct = $this->getFirstBasketProduct()) {
                $this->_oSimilarList = $oProduct->getSimilarProducts();
            }
        }

        return $this->_oSimilarList;
    }

    /**
     * Return array of id to form recommend list.
     *
     * @return array
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     */
    public function getSimilarRecommListIds()
    {
        if ($this->_aSimilarRecommListIds === null) {
            $this->_aSimilarRecommListIds = false;

            if ($oProduct = $this->getFirstBasketProduct()) {
                $this->_aSimilarRecommListIds = [$oProduct->getId()];
            }
        }

        return $this->_aSimilarRecommListIds;
    }

    /**
     * return the Link back to shop
     *
     * @return bool
     */
    public function showBackToShop()
    {
        $iNewBasketItemMessage = Registry::getConfig()->getConfigParam('iNewBasketItemMessage');
        $sBackToShop = Registry::getSession()->getVariable('_backtoshop');

        return ($iNewBasketItemMessage == 3 && $sBackToShop);
    }

    /**
     * Assigns voucher to current basket
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addVoucher()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            $this->getContainer()->get(LoggerInterface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }

        if (!$this->getViewConfig()->getShowVouchers()) {
            return;
        }

        $oBasket = Registry::getSession()->getBasket();
        $oBasket->addVoucher(Registry::getRequest()->getRequestEscapedParameter('voucherNr'));
    }

    /**
     * Removes voucher from basket (calls Basket::removeVoucher())
     *
     * @return void
     */
    public function removeVoucher()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            $this->getContainer()->get(LoggerInterface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }

        if (!$this->getViewConfig()->getShowVouchers()) {
            return;
        }

        $oBasket = Registry::getSession()->getBasket();
        $oBasket->removeVoucher(Registry::getRequest()->getRequestEscapedParameter('voucherId'));
    }

    /**
     * Redirects user back to previous part of shop (list, details, ...) from basket.
     * Used with option "Display Message when Product is added to Cart" set to "Open Basket"
     * ($myConfig->iNewBasketItemMessage == 3)
     *
     * @return string|void   $sBackLink  back link
     */
    public function backToShop()
    {
        if (Registry::getConfig()->getConfigParam('iNewBasketItemMessage') == 3) {
            $oSession = Registry::getSession();
            if ($sBackLink = $oSession->getVariable('_backtoshop')) {
                $oSession->deleteVariable('_backtoshop');

                return $sBackLink;
            }
        }
    }

    /**
     * Returns a name of the view variable containing the error/exception messages
     *
     * @return null
     */
    public function getErrorDestination()
    {
        return 'basket';
    }

    /**
     * Returns wrapping options availability state (TRUE/FALSE)
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function isWrapping()
    {
        if (!$this->getViewConfig()->getShowGiftWrapping()) {
            return false;
        }

        if ($this->_iWrapCnt === null) {
            $this->_iWrapCnt = 0;

            $oWrap = oxNew(Wrapping::class);
            $this->_iWrapCnt += $oWrap->getWrappingCount('WRAP');
            $this->_iWrapCnt += $oWrap->getWrappingCount('CARD');
        }

        return (bool) $this->_iWrapCnt;
    }

    /**
     * Return basket wrappings list if available
     *
     * @return ListObject
     */
    public function getWrappingList()
    {
        if ($this->_oWrappings === null) {
            $this->_oWrappings = new ListModel();

            // load wrapping papers
            if ($this->getViewConfig()->getShowGiftWrapping()) {
                $this->_oWrappings = oxNew(Wrapping::class)->getWrappingList('WRAP');
            }
        }

        return $this->_oWrappings;
    }

    /**
     * Returns greeting cards list if available
     *
     * @return ListObject
     */
    public function getCardList()
    {
        if ($this->_oCards === null) {
            $this->_oCards = new ListModel();

            // load gift cards
            if ($this->getViewConfig()->getShowGiftWrapping()) {
                $this->_oCards = oxNew(Wrapping::class)->getWrappingList('CARD');
            }
        }

        return $this->_oCards;
    }

    /**
     * Updates wrapping data in session basket object
     * (Session::getBasket()) - adds wrapping info to
     * each article in basket (if possible). Plus adds
     * gift message and chosen card ( takes from GET/POST/session;
     * oBasket::giftmessage, oBasket::chosencard). Then sets
     * basket back to session (Session::setBasket()).
     */
    public function changeWrapping()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            $this->getContainer()->get(LoggerInterface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }

        $oRequest = Registry::getRequest();

        if ($this->getViewConfig()->getShowGiftWrapping()) {
            $oBasket = Registry::getSession()->getBasket();

            $this->setWrappingInfo($oBasket, $oRequest->getRequestEscapedParameter('wrapping'));

            $oBasket->setCardMessage($oRequest->getRequestEscapedParameter('giftmessage'));
            $oBasket->setCardId($oRequest->getRequestEscapedParameter('chosencard'));
            $oBasket->onUpdate();
        }
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];

        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('CART', $iBaseLanguage, false);
        $aPath['link']  = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Method returns object with explanation marks for articles in basket.
     *
     * @return BasketContentMarkGenerator
     */
    public function getBasketContentMarkGenerator()
    {
        /** @var BasketContentMarkGenerator $oBasketContentMarkGenerator */
        return oxNew(BasketContentMarkGenerator::class, Registry::getSession()->getBasket());
    }

    /**
     * Sets basket wrapping
     *
     * @param Basket $oBasket
     * @param array                                      $aWrapping
     * @deprecated underscore prefix violates PSR12, will be renamed to "setWrappingInfo" in next major
     */
    protected function _setWrappingInfo($oBasket, $aWrapping) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setWrappingInfo($oBasket, $aWrapping);
    }

    /**
     * Sets basket wrapping
     *
     * @param Basket $oBasket
     * @param array $aWrapping
     */
    protected function setWrappingInfo($oBasket, $aWrapping)
    {
        if (is_array($aWrapping) && count($aWrapping)) {
            foreach ($oBasket->getContents() as $sKey => $oBasketItem) {
                if (isset($aWrapping[$sKey])) {
                    $oBasketItem->setWrapping($aWrapping[$sKey]);
                }
            }
        }
    }
}
