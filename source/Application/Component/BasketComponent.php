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

namespace OxidEsales\EshopCommunity\Application\Component;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BasketChangedEvent;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Main shopping basket manager. Arranges shopping basket
 * contents, updates amounts, prices, taxes etc.
 *
 * @subpackage oxcmp
 */
class BasketComponent extends BaseController
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Last call function name
     *
     * @var string
     */
    protected $_sLastCallFnc = null;

    /**
     * Parameters which are kept when redirecting after user
     * puts something to basket
     *
     * @var array
     */
    public $aRedirectParams = ['cnid', // category id
                                    'mnid', // manufacturer id
                                    'anid', // active article id
                                    'tpl', // spec. template
                                    'listtype', // list type
                                    'searchcnid', // search category
                                    'searchvendor', // search vendor
                                    'searchmanufacturer', // search manufacturer
                                    // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
                                    'searchrecomm', // search recomendation
                                    'recommid' // recomm. list id
                                    // END deprecated
    ];

    /**
     * Initiates component.
     */
    public function init()
    {
        $oConfig = Registry::getConfig();
        if ($oConfig->getConfigParam('blPsBasketReservationEnabled')) {
            if ($oReservations = Registry::getSession()->getBasketReservations()) {
                if (!$oReservations->getTimeLeft()) {
                    $oBasket = Registry::getSession()->getBasket();
                    if ($oBasket && $oBasket->getProductsCount()) {
                        $this->emptyBasket($oBasket);
                    }
                }
                $iLimit = (int) $oConfig->getConfigParam('iBasketReservationCleanPerRequest');
                if (!$iLimit) {
                    $iLimit = 200;
                }
                $oReservations->discardUnusedReservations($iLimit);
            }
        }

        parent::init();

        // Basket exclude
        if (Registry::getConfig()->getConfigParam('blBasketExcludeEnabled')) {
            if ($oBasket = Registry::getSession()->getBasket()) {
                $this->getParent()->setRootCatChanged($this->isRootCatChanged() && $oBasket->getContents());
            }
        }
    }

    /**
     * Loads basket ($oBasket = $mySession->getBasket()), calls oBasket->calculateBasket,
     * executes parent::render() and returns basket object.
     *
     * @return object   $oBasket    basket object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        // recalculating
        if ($oBasket = Registry::getSession()->getBasket()) {
            $oBasket->calculateBasket(false);
        }

        parent::render();

        return $oBasket;
    }

    /**
     * Basket content update controller.
     * Before adding article - check if client is not a search engine. If
     * yes - exits method by returning false. If no - executes
     * oxcmp_basket::_addItems() and puts article to basket.
     * Returns position where to redirect user browser.
     *
     * @param null $sProductId Product ID (default null)
     * @param null $dAmount Product amount (default null)
     * @param null $aSel (default null)
     * @param null $aPersParam (default null)
     * @param bool $blOverride If true amount in basket is replaced by $dAmount otherwise amount is increased by
     *                           $dAmount (default false)
     *
     * @return void|string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws ObjectException
     */
    public function toBasket($sProductId = null, $dAmount = null, $aSel = null, $aPersParam = null, $blOverride = false)
    {
        if (
            Registry::getSession()->getId() &&
            Registry::getSession()->isActualSidInCookie() &&
            !Registry::getSession()->checkSessionChallenge()
        ) {
            $this->getContainer()->get(LoggerInterface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }

        // adding to basket is not allowed ?
        $myConfig = Registry::getConfig();
        if (Registry::getUtils()->isSearchEngine()) {
            return;
        }

        // adding articles
        if ($aProducts = $this->getItems($sProductId, $dAmount, $aSel, $aPersParam, $blOverride)) {
            $this->setLastCallFnc('tobasket');

            $database = DatabaseProvider::getDb();
            $database->startTransaction();
            try {
                $oBasketItem = $this->_addItems($aProducts);
                //reserve active basket
                if ($myConfig->getConfigParam('blPsBasketReservationEnabled')) {
                    $basket = Registry::getSession()->getBasket();
                    Registry::getSession()->getBasketReservations()->reserveBasket($basket);
                }
            } catch (Exception $exception) {
                $database->rollbackTransaction();
                unset($oBasketItem);
                throw $exception;
            }
            $database->commitTransaction();

            // new basket item marker
            if ($oBasketItem && $myConfig->getConfigParam('iNewBasketItemMessage') != 0) {
                $oNewItem = new stdClass();
                $oNewItem->sTitle = $oBasketItem->getTitle();
                $oNewItem->sId = $oBasketItem->getProductId();
                $oNewItem->dAmount = $oBasketItem->getAmount();
                $oNewItem->dBundledAmount = $oBasketItem->getdBundledAmount();

                // passing article
                Registry::getSession()->setVariable('_newitem', $oNewItem);
            }

            // redirect to basket
            $redirectUrl = $this->getRedirectUrl();
            $this->dispatchEvent(new BasketChangedEvent($this));

            return $redirectUrl;
        }
    }

    /**
     * Similar to tobasket, except that as product id "bindex" parameter is (can be) taken
     *
     * @param null $sProductId Product ID (default null)
     * @param null $dAmount Product amount (default null)
     * @param null $aSel (default null)
     * @param null $aPersParam (default null)
     * @param bool $blOverride If true means increase amount of chosen article (default false)
     *
     * @return void
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws ObjectException
     */
    public function changeBasket(
        $sProductId = null,
        $dAmount = null,
        $aSel = null,
        $aPersParam = null,
        $blOverride = true
    ) {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        // adding to basket is not allowed ?
        if (Registry::getUtils()->isSearchEngine()) {
            return;
        }

        // fetching item ID
        if (!$sProductId) {
            $sBasketItemId = Registry::getRequest()->getRequestEscapedParameter('bindex');

            if ($sBasketItemId) {
                $oBasket = Registry::getSession()->getBasket();
                //take params
                $aBasketContents = $oBasket->getContents();
                $oItem = $aBasketContents[$sBasketItemId];

                $sProductId = isset($oItem) ? $oItem->getProductId() : null;
            } else {
                $sProductId = Registry::getRequest()->getRequestEscapedParameter('aid');
            }
        }

        // fetching other needed info
        $dAmount = isset($dAmount) ? $dAmount : Registry::getRequest()->getRequestEscapedParameter('am');
        $aSel = isset($aSel) ? $aSel : Registry::getRequest()->getRequestEscapedParameter('sel');
        $aPersParam = $aPersParam ?: Registry::getRequest()->getRequestEscapedParameter('persparam');

        // adding articles
        if ($aProducts = $this->getItems($sProductId, $dAmount, $aSel, $aPersParam, $blOverride)) {
            // information that last call was changebasket
            $oBasket = Registry::getSession()->getBasket();
            $oBasket->onUpdate();
            $this->setLastCallFnc('changebasket');

            $database = DatabaseProvider::getDb();
            $database->startTransaction();
            try {
                $oBasketItem = $this->_addItems($aProducts);
                // reserve active basket
                if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
                    Registry::getSession()->getBasketReservations()->reserveBasket($oBasket);
                }
            } catch (Exception $exception) {
                $database->rollbackTransaction();
                unset($oBasketItem);
                throw $exception;
            }
            $database->commitTransaction();
        }
    }

    /**
     * Formats and returns redirect URL where shop must be redirected after
     * storing something to basket
     *
     * @return string   $sClass.$sPosition  redirection URL
     * @deprecated underscore prefix violates PSR12, will be renamed to "getRedirectUrl" in next major
     */
    protected function _getRedirectUrl() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getRedirectUrl();
    }
    
    /**
     * Formats and returns redirect URL where shop must be redirected after
     * storing something to basket
     *
     * @return string   $sClass.$sPosition  redirection URL
     */
    protected function getRedirectUrl()
    {

        // active controller id
        $controllerId = Registry::getConfig()->getRequestControllerId();
        $controllerId = $controllerId ? $controllerId . '?' : 'start?';
        $sPosition = '';

        // setting redirect parameters
        foreach ($this->aRedirectParams as $sParamName) {
            $sParamVal = Registry::getRequest()->getRequestEscapedParameter($sParamName);
            $sPosition .= $sParamVal ? $sParamName . '=' . $sParamVal . '&' : '';
        }

        // special treatment
        // search param
        $sParam = rawurlencode(Registry::getRequest()->getRequestEscapedParameter('searchparam', true));
        $sPosition .= $sParam ? 'searchparam=' . $sParam . '&' : '';

        // current page number
        $iPageNr = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
        $sPosition .= ($iPageNr > 0) ? 'pgNr=' . $iPageNr . '&' : '';

        // reload and backbutton blocker
        if (Registry::getConfig()->getConfigParam('iNewBasketItemMessage') == 3) {
            // saving return to shop link to session
            Registry::getSession()->setVariable('_backtoshop', $controllerId . $sPosition);

            // redirecting to basket
            $controllerId = 'basket?';
        }

        return $controllerId . $sPosition;
    }

    /**
     * Cleans and returns persisted parameters.
     *
     * @param array $persistedParameters key-value parameters (optional). If not passed - takes parameters from request.
     *
     * @return array|null cleaned up parameters or null, if there are no non-empty parameters
     */
    protected function getPersistedParameters($persistedParameters = null)
    {
        $persistedParameters = ($persistedParameters ?: Registry::getRequest()->getRequestEscapedParameter('persparam'));
        if (!is_array($persistedParameters)) {
            return null;
        }
        return array_filter($persistedParameters) ?: null;
    }

    /**
     * Collects and returns array of items to add to basket. Product info is taken not only from
     * given parameters, but additionally from request 'aproducts' parameter
     *
     * @param string $sProductId product ID
     * @param double $dAmount    product amount
     * @param array  $aSel       product select lists
     * @param array  $aPersParam product persistent parameters
     * @param bool   $blOverride amount override status
     *
     * @return array|bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "getItems" in next major
     */
    protected function _getItems( // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
        $sProductId = null,
        $dAmount = null,
        $aSel = null,
        $aPersParam = null,
        $blOverride = false
    ) {
        return $this->getItems($sProductId, $dAmount, $aSel, $aPersParam, $blOverride);
    }
    
    /**
     * Collects and returns array of items to add to basket. Product info is taken not only from
     * given parameters, but additionally from request 'aproducts' parameter
     *
     * @param string $sProductId product ID
     * @param double $dAmount    product amount
     * @param array  $aSel       product select lists
     * @param array  $aPersParam product persistent parameters
     * @param bool   $blOverride amount override status
     *
     * @return array|bool
     */
    protected function getItems(
        $sProductId = null,
        $dAmount = null,
        $aSel = null,
        $aPersParam = null,
        $blOverride = false
    ) {
        // collecting items to add
        $aProducts = Registry::getRequest()->getRequestEscapedParameter('aproducts');

        // collecting specified item
        $sProductId = $sProductId ?: Registry::getRequest()->getRequestEscapedParameter('aid');
        if ($sProductId) {
            // additionally fetching current product info
            $dAmount = isset($dAmount) ? $dAmount : Registry::getRequest()->getRequestEscapedParameter('am');

            // select lists
            $aSel = isset($aSel) ? $aSel : Registry::getRequest()->getRequestEscapedParameter('sel');

            // persistent parameters
            if (empty($aPersParam)) {
                $aPersParam = $this->getPersistedParameters();
            }

            $sBasketItemId = Registry::getRequest()->getRequestEscapedParameter('bindex');

            $aProducts[$sProductId] = ['am'           => $dAmount,
                                       'sel'          => $aSel,
                                       'persparam'    => $aPersParam,
                                       'override'     => $blOverride,
                                       'basketitemid' => $sBasketItemId
            ];
        }

        if (is_array($aProducts) && count($aProducts)) {
            if (Registry::getRequest()->getRequestEscapedParameter('removeBtn') !== null) {
                //setting amount to 0 if removing article from basket
                foreach ($aProducts as $sProductId => $aProduct) {
                    if (isset($aProduct['remove']) && $aProduct['remove']) {
                        $aProducts[$sProductId]['am'] = 0;
                    } else {
                        unset($aProducts[$sProductId]);
                    }
                }
            }

            return $aProducts;
        }

        return false;
    }

    /**
     * Adds all articles user wants to add to basket. Returns
     * last added to basket item.
     *
     * @param array $products products to add array
     *
     * @return  object  $oBasketItem    last added basket item
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws ObjectException
     */
    protected function _addItems($products) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $activeView = Registry::getConfig()->getActiveView();
        $errorDestination = $activeView->getErrorDestination();

        $basket = Registry::getSession()->getBasket();
        $basketInfo = $basket->getBasketSummary();

        $basketItemAmounts = [];

        foreach ($products as $addProductId => $productInfo) {
            $data = $this->prepareProductInformation($addProductId, $productInfo);
            $productAmount = 0;
            if (isset($basketInfo->aArticles[$data['id']])) {
                $productAmount = $basketInfo->aArticles[$data['id']];
            }
            $products[$addProductId]['oldam'] = $productAmount;

            //If we already changed articles, so they now exactly match existing ones,
            //we need to make sure we get the amounts correct
            if (isset($basketItemAmounts[$data['oldBasketItemId']])) {
                $data['amount'] = $data['amount'] + $basketItemAmounts[$data['oldBasketItemId']];
            }

            $basketItem = $this->addItemToBasket($basket, $data, $errorDestination);

            if (($basketItem instanceof BasketItem)) {
                $basketItemKey = $basketItem->getBasketItemKey();
                if ($basketItemKey) {
                    if (! isset($basketItemAmounts[$basketItemKey])) {
                        $basketItemAmounts[$basketItemKey] = 0;
                    }
                    $basketItemAmounts[$basketItemKey] += $data['amount'];
                }
            }

            if (!$basketItem) {
                $info = $basket->getBasketSummary();
                $products[$addProductId]['am'] = $info->aArticles[$data['id']] ?? 0;
            }
        }

        //if basket empty remove possible gift card
        if ($basket->getProductsCount() == 0) {
            $basket->setCardId(null);
        }

        // information that last call was tobasket
        $this->setLastCall($this->getLastCallFnc(), $products, $basketInfo);

        return $basketItem;
    }

    /**
     * Setting last call data to session (data used by econda)
     *
     * @param string $sCallName    name of action ('tobasket', 'changebasket')
     * @param array  $aProductInfo data which comes from request when you press button "to basket"
     * @param array  $aBasketInfo  array returned by \OxidEsales\Eshop\Application\Model\Basket::getBasketSummary()
     * @deprecated underscore prefix violates PSR12, will be renamed to "setLastCall" in next major
     */
    protected function _setLastCall($sCallName, $aProductInfo, $aBasketInfo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setLastCall($sCallName, $aProductInfo, $aBasketInfo);
    }
    
    /**
     * Setting last call data to session (data used by econda)
     *
     * @param string $sCallName    name of action ('tobasket', 'changebasket')
     * @param array  $aProductInfo data which comes from request when you press button "to basket"
     * @param array  $aBasketInfo  array returned by \OxidEsales\Eshop\Application\Model\Basket::getBasketSummary()
     */
    protected function setLastCall($sCallName, $aProductInfo, $aBasketInfo)
    {
        Registry::getSession()->setVariable('aLastcall', [$sCallName => $aProductInfo]);
    }

    /**
     * Setting last call function name (data used by econda)
     *
     * @param string $sCallName name of action ('tobasket', 'changebasket')
     * @deprecated underscore prefix violates PSR12, will be renamed to "setLastCallFnc" in next major
     */
    protected function _setLastCallFnc($sCallName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setLastCallFnc($sCallName);
    }

    /**
     * Setting last call function name (data used by econda)
     *
     * @param string $sCallName name of action ('tobasket', 'changebasket')
     */
    protected function setLastCallFnc($sCallName)
    {
        $this->_sLastCallFnc = $sCallName;
    }

    /**
     * Getting last call function name (data used by econda)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLastCallFnc" in next major
     */
    protected function _getLastCallFnc() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLastCallFnc();
    }
    
    /**
     * Getting last call function name (data used by econda)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLastCallFnc" in next major
     */
    protected function getLastCallFnc()
    {
        return $this->_sLastCallFnc;
    }

    /**
     * Returns true if active root category was changed
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function isRootCatChanged()
    {
        // in Basket
        $oBasket = Registry::getSession()->getBasket();
        if ($oBasket->showCatChangeWarning()) {
            $oBasket->setCatChangeWarningState(false);

            return true;
        }

        // in Category, only then category is empty ant not equal to default category
        $sDefCat = Registry::getConfig()->getActiveShop()->oxshops__oxdefcat->value;
        $sActCat = Registry::getRequest()->getRequestEscapedParameter('cnid');
        $oActCat = oxNew(Category::class);
        if ($sActCat && $sActCat != $sDefCat && $oActCat->load($sActCat)) {
            $sActRoot = $oActCat->oxcategories__oxrootid->value;
            if ($oBasket->getBasketRootCatId() && $sActRoot != $oBasket->getBasketRootCatId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Executes user choice:
     *
     * - if user clicked on "Proceed to checkout" - redirects to basket,
     * - if clicked "Continue shopping" - clear basket
     *
     * @return void|string
     */
    public function executeUserChoice()
    {
        $this->dispatchEvent(new BasketChangedEvent($this));

        // redirect to basket
        if (Registry::getRequest()->getRequestEscapedParameter("tobasket")) {
            return "basket";
        } else {
            // clear basket
            Registry::getSession()->getBasket()->deleteBasket();
            $this->getParent()->setRootCatChanged(false);
        }
    }

    /**
     * Deletes user basket object from session and saved one from DB if needed.
     *
     * @param Basket $oBasket
     */
    protected function emptyBasket($oBasket)
    {
        $oBasket->deleteBasket();
    }

    /**
     * Prepare information for adding product to basket.
     *
     * @param string $addProductId
     * @param array  $productInfo
     *
     * @return array
     */
    protected function prepareProductInformation($addProductId, $productInfo)
    {
        $return = [];

        $return['id'] = isset($productInfo['aid']) ? $productInfo['aid'] : $addProductId;
        $return['amount'] = isset($productInfo['am']) ? $productInfo['am'] : 0;
        $return['selectList'] = isset($productInfo['sel']) ? $productInfo['sel'] : null;

        $return['persistentParameters'] = $this->getPersistedParameters($productInfo['persparam']);
        $return['override'] = isset($productInfo['override']) ? $productInfo['override'] : null;
        $return['bundle'] = isset($productInfo['bundle']);
        $return['oldBasketItemId'] = isset($productInfo['basketitemid']) ? $productInfo['basketitemid'] : null;

        return $return;
    }

    /**
     * Add one item to basket. Handle eventual errors.
     *
     * @param Basket $basket
     * @param array $itemData
     * @param string $errorDestination
     *
     * @return object
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function addItemToBasket($basket, $itemData, $errorDestination)
    {
        $basketItem = null;

        try {
            $basketItem = $basket->addToBasket(
                $itemData['id'],
                $itemData['amount'],
                $itemData['selectList'],
                $itemData['persistentParameters'],
                $itemData['override'],
                $itemData['bundle'],
                $itemData['oldBasketItemId']
            );
        } catch (OutOfStockException $exception) {
            $exception->setDestination($errorDestination);
            // #950 Change error destination to basket popup
            if (!$errorDestination && Registry::getConfig()->getConfigParam('iNewBasketItemMessage') == 2) {
                $errorDestination = 'popup';
            }
            Registry::getUtilsView()->addErrorToDisplay($exception, false, (bool) $errorDestination, $errorDestination);
        } catch (ArticleInputException $exception) {
            //add to display at specific position
            $exception->setDestination($errorDestination);
            Registry::getUtilsView()->addErrorToDisplay($exception, false, (bool) $errorDestination, $errorDestination);
        } catch (NoArticleException $exception) {
            //ignored, best solution F ?
        }

        return $basketItem;
    }
}
