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

namespace OxidEsales\EshopCommunity\Application\Model;

use Exception;
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Exception\VoucherException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\PriceList;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use OxidEsales\Eshop\Application\Model\BasketItem;
use stdClass;

/**
 * Basket manager
 *
 */
class Basket extends Base
{
    /**
     * Array or BasketItem objects
     *
     * @var array
     */
    protected $_aBasketContents = [];

    /**
     * Number of different product type in basket.
     * The value is updated only after recalculating a basket.
     *
     * @deprecated since v.6.0.0 (2017-08-17); Use getProductsCount() instead.
     *
     * @var int
     */
    protected $_iProductsCnt = 0;

    /**
     * Number of basket items.
     * The value is updated only after recalculating a basket.
     *
     * @deprecated since v.6.0.0 (2017-08-17); Use getItemsCount() instead.
     *
     * @var double
     */
    protected $_dItemsCnt = 0.0;

    /**
     * Basket weight.
     * The value is updated only after recalculating a basket.
     *
     * @deprecated since v.6.0.0 (2017-08-17); Use getWeight() instead.
     *
     * @var double
     */
    protected $_dWeight = 0.0;

    /**
     * Total basket price
     *
     * @var Price
     */
    protected $_oPrice = null;

    /**
     * Basket calculation mode netto
     *
     * @var bool
     */
    protected $_isCalculationModeNetto = null;

    /**
     * Basket netto sum
     *
     * @var float
     */
    protected $_dNettoSum = null;

    /**
     * Basket brutto sum
     *
     * @var float
     */
    protected $_dBruttoSum = null;

    /**
     * The list of all basket item prices
     *
     * @var PriceList
     */
    protected $_oProductsPriceList = null;

    /**
     * Basket discounts information
     *
     * @var array
     */
    protected $_aDiscounts = [];

    /**
     * Basket items discounts information
     *
     * @var array
     */
    protected $_aItemDiscounts = [];

    /**
     * Basket order ID. Usually this ID is set on last order step
     *
     * @var string
     */
    protected $_sOrderId = null;

    /**
     * Array of vouchers applied on basket price
     *
     * @var array
     */
    protected $_aVouchers = [];

    /**
     * Additional costs array of Price objects
     *
     * @var array
     */
    protected $_aCosts = [];

    /**
     * Sum price of articles applicable to discounts
     *
     * @var PriceList
     */
    protected $_oDiscountProductsPriceList = null;

    /**
     * Sum price of articles not applicable to discounts
     *
     * @var PriceList
     */
    protected $_oNotDiscountedProductsPriceList = null;

    /**
     * Basket recalculation marker
     *
     * @var bool
     */
    protected $_blUpdateNeeded = true;

    /**
     * oxBasket summary object, usually used for discount calculations etc
     *
     * @var array
     */
    protected $_aBasketSummary = null;

    /**
     * Basket Payment ID
     *
     * @var string
     */
    protected $_sPaymentId = null;

    /**
     * Basket Shipping set ID
     *
     * @var string
     */
    protected $_sShippingSetId = null;

    /**
     * Ref. to session user
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Total basket products discount price object (does not include voucher discount)
     *
     * @var Price
     */
    protected $_oTotalDiscount = null;

    /**
     * Basket voucher discount price object
     *
     * @var Price
     */
    protected $_oVoucherDiscount = null;

    /**
     * Basket currency
     *
     * @var stdClass
     */
    protected $_oCurrency = null;

    /**
     * Skip or not vouchers availability checking
     *
     * @var bool
     */
    protected $_blSkipVouchersAvailabilityChecking = null;

    /**
     * Netto price including discount and voucher
     *
     * @var double
     */
    protected $_dDiscountedProductNettoPrice = null;

    /**
     * All VAT values with discount and voucher
     *
     * @var array
     */
    protected $_aDiscountedVats = null;

    /**
     * Skip discounts marker
     *
     * @var boolean
     */
    protected $_blSkipDiscounts = false;

    /**
     * User set delivery costs
     *
     * @var Price
     */
    protected $_oDeliveryPrice = null;

    /**
     * Basket product stock check (live db check) status
     *
     * @var bool
     */
    protected $_blCheckStock = true;

    /**
     * discount calculation marker
     *
     * @var bool
     */
    protected $_blCalcDiscounts = true;

    /**
     * Basket category id
     *
     * @var string
     */
    protected $_sBasketCategoryId = null;

    /**
     * Category change warning state
     *
     * @var bool
     */
    protected $_blShowCatChangeWarning = false;

    /**
     * new basket item addition state
     *
     * @var bool
     */
    protected $_blNewITemAdded = null;

    /**
     * if basket has downloadable product
     *
     * @var bool
     */
    protected $_blDownloadableProducts = null;


    /**
     * Save basket to database if user is logged in
     *
     * @var bool
     */
    protected $_blSaveToDataBase = null;

    /**
     * Save card id
     *
     * @var string
     */
    protected $_sCardId = null;

    /**
     * Card message.
     *
     * @var string
     */
    protected $_sCardMessage = '';

    /**
     * Enables or disable saving to data base
     *
     * @param boolean $blSave
     */
    public function enableSaveToDataBase($blSave = true)
    {
        $this->_blSaveToDataBase = $blSave;
    }

    /**
     * Returns true if saving to data base enabled
     *
     * @return boolean
     */
    public function isSaveToDataBaseEnabled()
    {
        if (is_null($this->_blSaveToDataBase)) {
            $this->_blSaveToDataBase = (bool) !Registry::getConfig()->getConfigParam('blPerfNoBasketSaving');
        }

        return $this->_blSaveToDataBase;
    }


    /**
     * Return true if calculation mode is netto
     *
     * @return bool
     */
    public function isCalculationModeNetto()
    {
        if ($this->_isCalculationModeNetto === null) {
            $this->setCalculationModeNetto($this->isPriceViewModeNetto());
        }

        return $this->_isCalculationModeNetto;
    }

    /**
     * Set netto calculation mode
     *
     * @param bool $blNettoMode - true in netto; false - turn off
     */
    public function setCalculationModeNetto($blNettoMode = true)
    {
        $this->_isCalculationModeNetto = (bool) $blNettoMode;
    }

    /**
     * Return basket netto sum (in B2B view mode sum include discount)
     *
     * @return float
     */
    public function getNettoSum()
    {
        return $this->_dNettoSum;
    }

    /**
     * Return basket brutto sum (in B2C view mode sum include discount)
     *
     * @return float
     */
    public function getBruttoSum()
    {
        return $this->_dBruttoSum;
    }

    /**
     * Set basket netto sum
     *
     * @param float $dNettoSum sum of basket in netto mode
     */
    public function setNettoSum($dNettoSum)
    {
        $this->_dNettoSum = $dNettoSum;
    }

    /**
     * Set basket brutto sum
     *
     * @param float $dBruttoSum sum of basket in brutto mode
     */
    public function setBruttoSum($dBruttoSum)
    {
        $this->_dBruttoSum = $dBruttoSum;
    }

    /**
     * Checks if configuration allows basket usage or if user agent is search engine
     *
     * @return bool
     */
    public function isEnabled()
    {
        return !Registry::getUtils()->isSearchEngine();
    }

    /**
     * change old key to new one but retain key position in array
     *
     * @param string $sOldKey old key
     * @param string $sNewKey new key to place in old one's place
     * @param mixed  $value   (optional)
     * @deprecated underscore prefix violates PSR12, will be renamed to "changeBasketItemKey" in next major
     */
    protected function _changeBasketItemKey($sOldKey, $sNewKey, $value = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        reset($this->_aBasketContents);
        $iOldKeyPlace = 0;
        while (key($this->_aBasketContents) != $sOldKey && next($this->_aBasketContents)) {
            ++$iOldKeyPlace;
        }
        $aNewCopy = array_merge(
            array_slice($this->_aBasketContents, 0, $iOldKeyPlace, true),
            [$sNewKey => $value],
            array_slice($this->_aBasketContents, $iOldKeyPlace + 1, count($this->_aBasketContents) - $iOldKeyPlace, true)
        );
        $this->_aBasketContents = $aNewCopy;
    }

    /**
     * Adds user item to basket. Returns BasketItem object if adding succeeded
     *
     * @param string $sProductID id of product
     * @param double $dAmount product amount
     * @param mixed $aSel product select lists (default null)
     * @param mixed $aPersParam product persistent parameters (default null)
     * @param bool $blOverride marker to accumulate passed amount or renew (default false)
     * @param bool $blBundle marker if product is bundle or not (default false)
     * @param mixed $sOldBasketItemId id if old basket item if to change it
     *
     * @return object ArticleInputException, NoArticleException
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function addToBasket($sProductID, $dAmount, $aSel = null, $aPersParam = null, $blOverride = false, $blBundle = false, $sOldBasketItemId = null)
    {
        // enabled ?
        if (!$this->isEnabled()) {
            return null;
        }

        // basket exclude
        if (Registry::getConfig()->getConfigParam('blBasketExcludeEnabled')) {
            if (!$this->canAddProductToBasket($sProductID)) {
                $this->setCatChangeWarningState(true);

                return null;
            } else {
                $this->setCatChangeWarningState(false);
            }
        }

        $sItemId = $this->getItemKey($sProductID, $aSel, $aPersParam, $blBundle);
        if ($sOldBasketItemId && (strcmp($sOldBasketItemId, $sItemId) != 0)) {
            if (isset($this->_aBasketContents[$sItemId])) {
                // we are merging, so params will just go to the new key
                unset($this->_aBasketContents[$sOldBasketItemId]);
                // do not override stock
                $blOverride = false;
            } else {
                // value is null - means isset will fail and real values will be filled
                $this->_changeBasketItemKey($sOldBasketItemId, $sItemId);
            }
        }

        // after some checks item must be removed from basket
        $blRemoveItem = false;

        // initialling exception storage
        $oEx = null;

        if (isset($this->_aBasketContents[$sItemId])) {
            //updating existing
            try {
                // setting stock check status
                $this->_aBasketContents[$sItemId]->setStockCheckStatus($this->getStockCheckMode());
                //validate amount
                //possibly throws exception
                $this->_aBasketContents[$sItemId]->setAmount($dAmount, $blOverride, $sItemId);
            } catch (OutOfStockException $oEx) {
                // rethrow later
            }
        } else {
            //inserting new
            $oBasketItem = oxNew(BasketItem::class);
            try {
                $oBasketItem->setStockCheckStatus($this->getStockCheckMode());
                $oBasketItem->init($sProductID, $dAmount, $aSel, $aPersParam, $blBundle);
            } catch (NoArticleException $oEx) {
                // in this case that the article does not exist remove the item from the basket by setting its amount to 0
                //$oBasketItem->dAmount = 0;
                $blRemoveItem = true;
            } catch (OutOfStockException $oEx) {
                // rethrow later
            } catch (ArticleInputException $oEx) {
                // rethrow later
                $blRemoveItem = true;
            }

            $this->_aBasketContents[$sItemId] = $oBasketItem;
        }

        //in case amount is 0 removing item
        if ($this->_aBasketContents[$sItemId]->getAmount() == 0 || $blRemoveItem) {
            $this->removeItem($sItemId);
        } elseif ($blBundle) {
            //marking bundles
            $this->_aBasketContents[$sItemId]->setBundle(true);
        }

        //calling update method
        $this->onUpdate();

        if ($oEx) {
            throw $oEx;
        }

        // notifying that new basket item was added
        if (!$blBundle) {
            $this->_addedNewItem($sProductID, $dAmount, $aSel, $aPersParam, $blOverride, $blBundle, $sOldBasketItemId);
        }

        // returning basket item object
        if ($this->_aBasketContents[$sItemId] instanceof BasketItem) {
            $this->_aBasketContents[$sItemId]->setBasketItemKey($sItemId);
        }
        return $this->_aBasketContents[$sItemId];
    }

    /**
     * Adds order article to basket (method normally used while recalculating order)
     *
     * @param OrderArticle $oOrderArticle order article to store in basket
     *
     * @return BasketItem|void
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function addOrderArticleToBasket($oOrderArticle)
    {
        // adding only if amount > 0
        if ($oOrderArticle->oxorderarticles__oxamount->value > 0 && !$oOrderArticle->isBundle()) {
            $this->_isForOrderRecalculation = true;
            $sItemId = $oOrderArticle->getId();

            //inserting new
            $this->_aBasketContents[$sItemId] = oxNew(BasketItem::class);
            $this->_aBasketContents[$sItemId]->initFromOrderArticle($oOrderArticle);
            $this->_aBasketContents[$sItemId]->setWrapping($oOrderArticle->oxorderarticles__oxwrapid->value);
            $this->_aBasketContents[$sItemId]->setBundle($oOrderArticle->isBundle());

            //calling update method
            $this->onUpdate();

            return $this->_aBasketContents[$sItemId];
        } elseif ($oOrderArticle->isBundle()) {
            // deleting bundles, they are handled automatically
            $oOrderArticle->delete();
        }
    }

    /**
     * Sets stock control mode
     *
     * @param bool $blCheck stock control mode
     */
    public function setStockCheckMode($blCheck)
    {
        $this->_blCheckStock = $blCheck;
    }

    /**
     * Returns stock control mode
     *
     * @return bool
     */
    public function getStockCheckMode()
    {
        return $this->_blCheckStock;
    }

    /**
     * Returns unique basket item identifier which consist from product ID,
     * select lists data, persistent info and bundle property
     *
     * @param string $sProductId       basket item id
     * @param array  $aSel             basket item selectlists
     * @param array  $aPersParam       basket item persistent parameters
     * @param bool   $blBundle         bundle marker
     * @param string $sAdditionalParam possible additional information
     *
     * @return string
     */
    public function getItemKey($sProductId, $aSel = null, $aPersParam = null, $blBundle = false, $sAdditionalParam = '')
    {
        $aSel = ($aSel != null) ? $aSel : [0 => '0'];

        $sItemKey = md5($sProductId . '|' . serialize($aSel) . '|' . serialize($aPersParam) . '|' . (int) $blBundle . '|' . serialize($sAdditionalParam));

        return $sItemKey;
    }


    /**
     * Removes item from basket
     *
     * @param string $sItemKey basket item key
     */
    public function removeItem($sItemKey)
    {
        if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
            if (isset($this->_aBasketContents[$sItemKey])) {
                $sArticleId = $this->_aBasketContents[$sItemKey]->getProductId();
                if ($sArticleId) {
                    Registry::getSession()
                        ->getBasketReservations()
                        ->discardArticleReservation($sArticleId);
                }
            }
        }
        unset($this->_aBasketContents[$sItemKey]);

        // basket exclude
        if (!count($this->_aBasketContents) && Registry::getConfig()->getConfigParam('blBasketExcludeEnabled')) {
            $this->setBasketRootCatId(null);
        }
    }

    /**
     * Unsets bundled basket items from basket contents array
     * @deprecated underscore prefix violates PSR12, will be renamed to "clearBundles" in next major
     */
    protected function _clearBundles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        reset($this->_aBasketContents);
        foreach ($this->_aBasketContents as $sItemKey => $oBasketItem) {
            if ($oBasketItem->isBundle()) {
                $this->removeItem($sItemKey);
            }
        }
    }

    /**
     * Returns array of bundled articles IDs for basket item
     *
     * @param object $oBasketItem basket item object
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getArticleBundles" in next major
     */
    protected function _getArticleBundles($oBasketItem) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aBundles = [];

        if ($oBasketItem->isBundle()) {
            return $aBundles;
        }

        $oArticle = $oBasketItem->getArticle(true);
        if ($oArticle && $oArticle->oxarticles__oxbundleid->value) {
            $aBundles[$oArticle->oxarticles__oxbundleid->value] = 1;
        }

        return $aBundles;
    }

    /**
     * Returns array of bundled discount articles
     *
     * @param object $oBasketItem basket item object
     * @param array $aBundles array of found bundles
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getItemBundles" in next major
     */
    protected function _getItemBundles($oBasketItem, $aBundles = []) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($oBasketItem->isBundle()) {
            return [];
        }

        // does this object still exists ?
        if ($oArticle = $oBasketItem->getArticle()) {
            $aDiscounts = Registry::get(DiscountList::class)->getBasketItemBundleDiscounts($oArticle, $this, $this->getBasketUser());

            foreach ($aDiscounts as $oDiscount) {
                $iAmnt = $oDiscount->getBundleAmount($oBasketItem->getAmount());
                if ($iAmnt) {
                    //init array element
                    if (!isset($aBundles[$oDiscount->oxdiscount__oxitmartid->value])) {
                        $aBundles[$oDiscount->oxdiscount__oxitmartid->value] = 0;
                    }

                    if ($oDiscount->oxdiscount__oxitmmultiple->value) {
                        $aBundles[$oDiscount->oxdiscount__oxitmartid->value] += $iAmnt;
                    } else {
                        $aBundles[$oDiscount->oxdiscount__oxitmartid->value] = $iAmnt;
                    }
                }
            }
        }

        return $aBundles;
    }

    /**
     * Returns array of bundled discount articles for whole basket
     *
     * @param array $aBundles array of found bundles
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getBasketBundles" in next major
     */
    protected function _getBasketBundles($aBundles = []) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aDiscounts = Registry::get(DiscountList::class)->getBasketBundleDiscounts($this, $this->getBasketUser());

        // calculating amount of non bundled/discount items
        $dAmount = 0;
        foreach ($this->_aBasketContents as $oBasketItem) {
            if (!($oBasketItem->isBundle() || $oBasketItem->isDiscountArticle())) {
                $dAmount += $oBasketItem->getAmount();
            }
        }

        foreach ($aDiscounts as $oDiscount) {
            if ($oDiscount->oxdiscount__oxitmartid->value) {
                if (!isset($aBundles[$oDiscount->oxdiscount__oxitmartid->value])) {
                    $aBundles[$oDiscount->oxdiscount__oxitmartid->value] = 0;
                }

                $aBundles[$oDiscount->oxdiscount__oxitmartid->value] += $oDiscount->getBundleAmount($dAmount);
            }
        }

        return $aBundles;
    }

    /**
     * Iterates through basket contents and adds bundles to items + adds
     * global basket bundles
     * @deprecated underscore prefix violates PSR12, will be renamed to "addBundles" in next major
     */
    protected function _addBundles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aBundles = [];
        // iterating through articles and binding bundles
        foreach ($this->_aBasketContents as $key => $oBasketItem) {
            try {
                // adding discount type bundles
                if (!$oBasketItem->isDiscountArticle() && !$oBasketItem->isBundle()) {
                    $aBundles = $this->_getItemBundles($oBasketItem, $aBundles);
                } else {
                    continue;
                }

                // adding item type bundles
                $aArtBundles = $this->_getArticleBundles($oBasketItem);
                // adding bundles to basket
                $this->_addBundlesToBasket($aArtBundles);
            } catch (NoArticleException $oEx) {
                $this->removeItem($key);
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            } catch (ArticleInputException $oEx) {
                $this->removeItem($key);
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            }
        }

        // adding global basket bundles
        $aBundles = $this->_getBasketBundles($aBundles);

        // adding all bundles to basket
        if ($aBundles) {
            $this->_addBundlesToBasket($aBundles);
        }
    }

    /**
     * Adds bundles to basket
     *
     * @param array $aBundles added bundle articles
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addBundlesToBasket" in next major
     */
    protected function _addBundlesToBasket($aBundles) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($aBundles as $sBundleId => $dAmount) {
            if ($dAmount) {
                try {
                    if ($oBundleItem = $this->addToBasket($sBundleId, $dAmount, null, null, false, true)) {
                        $oBundleItem->setAsDiscountArticle(true);
                    }
                } catch (ArticleException $oEx) {
                    // caught and ignored
                    if ($oEx instanceof OutOfStockException && $oEx->getRemainingAmount() > 0) {
                        $sItemId = $this->getItemKey($sBundleId, null, null, true);
                        $this->_aBasketContents[$sItemId]->setAsDiscountArticle(true);
                    }
                }
            }
        }
    }

    /**
     * Iterates through basket items and calculates its prices and discounts
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcItemsPrice" in next major
     */
    protected function _calcItemsPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // resetting
        $this->setSkipDiscounts(false);
        $this->_iProductsCnt = 0; // count different types
        $this->_dItemsCnt = 0; // count of item units
        $this->_dWeight = 0; // basket weight

        $this->_oProductsPriceList = oxNew(PriceList::class);
        $this->_oDiscountProductsPriceList = oxNew(PriceList::class);
        $this->_oNotDiscountedProductsPriceList = oxNew(PriceList::class);

        $oDiscountList = Registry::get(DiscountList::class);

        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            $this->_iProductsCnt++;
            $this->_dItemsCnt += $oBasketItem->getAmount();
            $this->_dWeight += $oBasketItem->getWeight();

            if (!$oBasketItem->isDiscountArticle() && ($oArticle = $oBasketItem->getArticle(true))) {
                $oBasketPrice = $oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $this);
                $oBasketItem->setRegularUnitPrice(clone $oBasketPrice);

                if (!$oArticle->skipDiscounts() && $this->canCalcDiscounts()) {
                    // apply basket type discounts for item
                    $aDiscounts = $oDiscountList->getBasketItemDiscounts($oArticle, $this, $this->getBasketUser());
                    reset($aDiscounts);
                    /** @var Discount $oDiscount */
                    foreach ($aDiscounts as $oDiscount) {
                        $oBasketPrice->setDiscount($oDiscount->getAddSum(), $oDiscount->getAddSumType());
                    }
                    $oBasketPrice->calculateDiscount();
                } else {
                    $oBasketItem->setSkipDiscounts(true);
                    $this->setSkipDiscounts(true);
                }

                $oBasketItem->setPrice($oBasketPrice);
                $this->_oProductsPriceList->addToPriceList($oBasketItem->getPrice());

                //P collect discount values for basket items which are discountable
                if (!$oArticle->skipDiscounts()) {
                    $this->_oDiscountProductsPriceList->addToPriceList($oBasketItem->getPrice());
                } else {
                    $this->_oNotDiscountedProductsPriceList->addToPriceList($oBasketItem->getPrice());
                    $oBasketItem->setSkipDiscounts(true);
                    $this->setSkipDiscounts(true);
                }
            } elseif ($oBasketItem->isBundle()) {
                // if bundles price is set to zero
                $oPrice = oxNew(Price::class);
                $oBasketItem->setPrice($oPrice);
            }
        }
    }

    /**
     * Sets discount calculation mode
     *
     * @param bool $blCalcDiscounts calculate discounts or not
     */
    public function setDiscountCalcMode($blCalcDiscounts)
    {
        $this->_blCalcDiscounts = $blCalcDiscounts;
    }

    /**
     * Returns true if discount calculation is enabled
     *
     * @return bool
     */
    public function canCalcDiscounts()
    {
        return $this->_blCalcDiscounts;
    }

    /**
     * Merges two discount arrays. If there are two the same
     * discounts, discount values will be added.
     *
     * @param array $aDiscounts     Discount array
     * @param array $aItemDiscounts Discount array
     *
     * @return array $aDiscounts
     * @deprecated underscore prefix violates PSR12, will be renamed to "mergeDiscounts" in next major
     */
    protected function _mergeDiscounts($aDiscounts, $aItemDiscounts) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        foreach ($aItemDiscounts as $sKey => $oDiscount) {
            // add prices of the same discounts
            if (array_key_exists($sKey, $aDiscounts)) {
                $aDiscounts[$sKey]->dDiscount += $oDiscount->dDiscount;
            } else {
                $aDiscounts[$sKey] = $oDiscount;
            }
        }

        return $aDiscounts;
    }

    /**
     * Iterates through basket items and calculates its delivery costs
     *
     * @return Price
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcDeliveryCost" in next major
     */
    protected function _calcDeliveryCost() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_oDeliveryPrice !== null) {
            return $this->_oDeliveryPrice;
        }
        $myConfig = Registry::getConfig();
        $oDeliveryPrice = oxNew(Price::class);

        if (Registry::getConfig()->getConfigParam('blDeliveryVatOnTop')) {
            $oDeliveryPrice->setNettoPriceMode();
        } else {
            $oDeliveryPrice->setBruttoPriceMode();
        }

        // don't calculate if not logged in
        $oUser = $this->getBasketUser();

        if (!$oUser && !$myConfig->getConfigParam('blCalculateDelCostIfNotLoggedIn')) {
            return $oDeliveryPrice;
        }

        $fDelVATPercent = $this->getAdditionalServicesVatPercent();
        $oDeliveryPrice->setVat($fDelVATPercent);

        // list of active delivery costs
        if ($myConfig->getConfigParam('bl_perfLoadDelivery')) {
            $aDeliveryList = Registry::get(DeliveryList::class)->getDeliveryList(
                $this,
                $oUser,
                $this->_findDelivCountry(),
                $this->getShippingId()
            );

            if (count($aDeliveryList) > 0) {
                foreach ($aDeliveryList as $oDelivery) {
                    //debug trace
                    if ($myConfig->getConfigParam('iDebug') == 5) {
                        echo("DelCost : " . $oDelivery->oxdelivery__oxtitle->value . "<br>");
                    }
                    $oDeliveryPrice->addPrice($oDelivery->getDeliveryPrice($fDelVATPercent));
                }
            }
        }

        return $oDeliveryPrice;
    }

    /**
     * Basket user getter
     *
     * @return User
     */
    public function getBasketUser()
    {
        if ($this->_oUser == null) {
            return $this->getUser();
        }

        return $this->_oUser;
    }

    /**
     * Basket user setter
     *
     * @param User|null $oUser Basket user
     */
    public function setBasketUser($oUser)
    {
        $this->_oUser = $oUser;
    }

    /**
     * Get most used vat percent:
     *
     * @return double|void
     */
    public function getMostUsedVatPercent()
    {
        if ($this->_oProductsPriceList) {
            return $this->_oProductsPriceList->getMostUsedVatPercent();
        }
    }

    /**
     * Get most used vat percent:
     *
     * @return double|void
     */
    public function getAdditionalServicesVatPercent()
    {
        if ($this->_oProductsPriceList) {
            if (Registry::getConfig()->getConfigParam('sAdditionalServVATCalcMethod') == 'proportional') {
                return $this->_oProductsPriceList->getProportionalVatPercent();
            } else {
                return $this->_oProductsPriceList->getMostUsedVatPercent();
            }
        }
    }

    /**
     * Get most used vat percent:
     *
     * @return bool
     */
    public function isProportionalCalculationOn()
    {
        if (Registry::getConfig()->getConfigParam('sAdditionalServVATCalcMethod') == 'proportional') {
            return true;
        }

        return false;
    }


    //P
    /**
     * Performs final sum calculation and rounding.
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcTotalPrice" in next major
     */
    protected function _calcTotalPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // 1. add products price
        $dPrice = $this->_dBruttoSum;


        /** @var Price $oTotalPrice */
        $oTotalPrice = oxNew(Price::class);
        $oTotalPrice->setBruttoPriceMode();
        $oTotalPrice->setPrice($dPrice);

        // 2. subtract discounts
        if ($dPrice && !$this->isCalculationModeNetto()) {
            // 2.2 applying basket discounts
            $oTotalPrice->subtract($this->_oTotalDiscount->getBruttoPrice());

            // 2.3 applying voucher discounts
            if ($oVoucherDisc = $this->getVoucherDiscount()) {
                $oTotalPrice->subtract($oVoucherDisc->getBruttoPrice());
            }
        }

        // 2.3 add delivery cost
        if (isset($this->_aCosts['oxdelivery'])) {
            $oTotalPrice->add($this->_aCosts['oxdelivery']->getBruttoPrice());
        }

        // 2.4 add wrapping price
        if (isset($this->_aCosts['oxwrapping'])) {
            $oTotalPrice->add($this->_aCosts['oxwrapping']->getBruttoPrice());
        }
        if (isset($this->_aCosts['oxgiftcard'])) {
            $oTotalPrice->add($this->_aCosts['oxgiftcard']->getBruttoPrice());
        }

        // 2.5 add payment price
        if (isset($this->_aCosts['oxpayment'])) {
            $oTotalPrice->add($this->_aCosts['oxpayment']->getBruttoPrice());
        }

        $this->setPrice($oTotalPrice);
    }

    /**
     * Voucher discount setter
     *
     * @param double $dDiscount voucher discount value
     */
    public function setVoucherDiscount($dDiscount)
    {
        $this->_oVoucherDiscount = oxNew(Price::class);
        $this->_oVoucherDiscount->setBruttoPriceMode();
        $this->_oVoucherDiscount->add($dDiscount);
    }

    /**
     * Calculates voucher discount
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcVoucherDiscount" in next major
     */
    protected function _calcVoucherDiscount() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (Registry::getConfig()->getConfigParam('bl_showVouchers') && ($this->_oVoucherDiscount === null || ($this->_blUpdateNeeded && !$this->isAdmin()))) {
            $this->_oVoucherDiscount = $this->_getPriceObject();

            // calculating price to apply discount
            $dPrice = $this->_oDiscountProductsPriceList->getSum($this->isCalculationModeNetto()) - $this->_oTotalDiscount->getPrice();

            // recalculating
            if (count($this->_aVouchers)) {
                $oLang = Registry::getLang();
                foreach ($this->_aVouchers as $sVoucherId => $oStdVoucher) {
                    $oVoucher = oxNew(Voucher::class);
                    try { // checking
                        $oVoucher->load($oStdVoucher->sVoucherId);

                        if (!$this->_blSkipVouchersAvailabilityChecking) {
                            $oVoucher->checkBasketVoucherAvailability($this->_aVouchers, $dPrice);
                            $oVoucher->checkUserAvailability($this->getBasketUser());
                            $oVoucher->markAsReserved();
                        }

                        // assigning real voucher discount value as this is the only place where real value is calculated
                        $dVoucherDiscount = $oVoucher->getDiscountValue($dPrice);

                        if ($dVoucherDiscount > 0) {
                            $dVatPart = ($dPrice - $dVoucherDiscount) / $dPrice * 100;

                            if (!$this->_aDiscountedVats) {
                                if ($oPriceList = $this->getDiscountProductsPrice()) {
                                    $this->_aDiscountedVats = $oPriceList->getVatInfo($this->isCalculationModeNetto());
                                }
                            }

                            // apply discount to vat
                            foreach ($this->_aDiscountedVats as $sKey => $dVat) {
                                $this->_aDiscountedVats[$sKey] = Price::percent($dVat, $dVatPart);
                            }
                        }

                        // accumulating discount value
                        $this->_oVoucherDiscount->add($dVoucherDiscount);

                        // collecting formatted for preview
                        $oStdVoucher->fVoucherdiscount = $oLang->formatCurrency($dVoucherDiscount, $this->getBasketCurrency());
                        $oStdVoucher->dVoucherdiscount = $dVoucherDiscount;

                        // subtracting voucher discount
                        $dPrice = $dPrice - $dVoucherDiscount;
                    } catch (VoucherException $oEx) {
                        // removing voucher on error
                        $oVoucher->unMarkAsReserved();
                        unset($this->_aVouchers[$sVoucherId]);

                        // storing voucher error info
                        Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);
                    }
                }
            }
        }
    }

    /**
     * Performs netto price and VATs calculations including discounts and vouchers.
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyDiscounts" in next major
     */
    protected function _applyDiscounts() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        //apply discounts for brutto price
        $dDiscountedSum = $this->_getDiscountedProductsSum();

        $oUtils = Registry::getUtils();
        $dVatSum = 0;
        foreach ($this->_aDiscountedVats as $dVat) {
            $dVatSum += $oUtils->fRound($dVat, $this->_oCurrency);
        }

        $oNotDiscounted = $this->getNotDiscountProductsPrice();

        if ($this->isCalculationModeNetto()) {
            // netto view mode
            $this->setNettoSum($this->getProductsPrice()->getSum());
            $this->setBruttoSum($oNotDiscounted->getSum(false) + $dDiscountedSum + $dVatSum);
        } else {
            // brutto view mode
            $this->setNettoSum($oNotDiscounted->getSum() + $dDiscountedSum - $dVatSum);
            $this->setBruttoSum($this->getProductsPrice()->getSum(false));
        }
    }

    /**
     * Returns true if view mode is netto
     *
     * @return bool
     */
    public function isPriceViewModeNetto()
    {
        $blResult = (bool) Registry::getConfig()->getConfigParam('blShowNetPrice');
        $oUser = $this->getBasketUser();
        if ($oUser) {
            $blResult = $oUser->isPriceViewModeNetto();
        }

        return $blResult;
    }

    /**
     * Returns prepared price object depending on view mode
     *
     * @return Price
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPriceObject" in next major
     */
    protected function _getPriceObject() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oPrice = oxNew(Price::class);

        if ($this->isCalculationModeNetto()) {
            $oPrice->setNettoPriceMode();
        } else {
            $oPrice->setBruttoPriceMode();
        }

        return $oPrice;
    }

    /**
     * Loads basket discounts and calculates discount values.
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcBasketDiscount" in next major
     */
    protected function _calcBasketDiscount() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // resetting
        $this->_aDiscounts = [];

        // P using prices sum which has discount, not sum of skipped discounts
        $dOldPrice = $this->_oDiscountProductsPriceList->getSum($this->isCalculationModeNetto());

        // add basket discounts
        if ($this->_oTotalDiscount !== null && isset($this->_isForOrderRecalculation) && $this->_isForOrderRecalculation) {
            //if total discount was set on order recalculation
            $oTotalPrice = $this->getTotalDiscount();
            $oDiscount = oxNew(Discount::class);
            $oDiscount->oxdiscount__oxaddsum = new Field($oTotalPrice->getPrice());
            $oDiscount->oxdiscount__oxaddsumtype = new Field('abs');
            $aDiscounts[] = $oDiscount;
        } else {
            // discounts for basket
            $aDiscounts = Registry::get(DiscountList::class)->getBasketDiscounts($this, $this->getBasketUser());
        }

        if ($oPriceList = $this->getDiscountProductsPrice()) {
            $this->_aDiscountedVats = $oPriceList->getVatInfo($this->isCalculationModeNetto());
        }

        /** @var Discount $oDiscount */
        foreach ($aDiscounts as $oDiscount) {
            // storing applied discounts
            $oStdDiscount = $oDiscount->getSimpleDiscount();

            // skipping bundle discounts
            if ($oDiscount->oxdiscount__oxaddsumtype->value == 'itm') {
                continue;
            }

            // saving discount info
            $oStdDiscount->dDiscount = $oDiscount->getAbsValue($dOldPrice);

            $dVatPart = 100 - $oDiscount->getPercentage($dOldPrice);

            // if discount is more than basket sum
            if ($dOldPrice < $oStdDiscount->dDiscount) {
                $oStdDiscount->dDiscount = $dOldPrice;
                $dVatPart = 0;
            }

            // apply discount to vat
            foreach ($this->_aDiscountedVats as $sKey => $dVat) {
                $this->_aDiscountedVats[$sKey] = Price::percent($dVat, $dVatPart);
            }

            //storing discount
            if ($oStdDiscount->dDiscount != 0) {
                $this->_aDiscounts[$oDiscount->getId()] = $oStdDiscount;
                // subtracting product price after discount
                $dOldPrice = $dOldPrice - $oStdDiscount->dDiscount;
            }
        }
    }

    /**
     * Calculates total basket discount value.
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcBasketTotalDiscount" in next major
     */
    protected function _calcBasketTotalDiscount() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_oTotalDiscount === null || (!$this->isAdmin())) {
            $this->_oTotalDiscount = $this->_getPriceObject();

            if (is_array($this->_aDiscounts)) {
                foreach ($this->_aDiscounts as $oDiscount) {
                    // skipping bundle discounts
                    if ($oDiscount->sType == 'itm') {
                        continue;
                    }

                    // add discount value to total basket discount
                    $this->_oTotalDiscount->add($oDiscount->dDiscount);
                }
            }
        }
    }

    /**
     * Adds Gift price info to $this->oBasket (additional field for
     * basket item "oWrap""). Loads each basket item, checks for
     * wrapping data, updates if available and stores back into
     * $this->oBasket. Returns price object for wrapping.
     *
     * @return Price
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcBasketWrapping" in next major
     */
    protected function _calcBasketWrapping() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oWrappingPrices = oxNew(PriceList::class);

        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            if (($oWrapping = $oBasketItem->getWrapping())) {
                $oWrappingPrice = $oWrapping->getWrappingPrice($oBasketItem->getAmount());
                $oWrappingPrice->setVat($oBasketItem->getPrice()->getVat());

                $oWrappingPrices->addToPriceList($oWrappingPrice);
            }
        }


        return $oWrappingPrices->calculateToPrice();
    }

    /**
     * Adds Gift price info to $this->oBasket (additional field for
     * basket item "oWrap""). Loads each basket item, checks for
     * wrapping data, updates if available and stores back into
     * $this->oBasket. Returns oxprice object for wrapping.
     *
     * @return Price
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcBasketGiftCard" in next major
     */
    protected function _calcBasketGiftCard() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oGiftCardPrice = oxNew(Price::class);

        if (Registry::getConfig()->getConfigParam('blWrappingVatOnTop')) {
            $oGiftCardPrice->setNettoPriceMode();
        } else {
            $oGiftCardPrice->setBruttoPriceMode();
        }

        $dVATPercent = $this->getAdditionalServicesVatPercent();

        $oGiftCardPrice->setVat($dVATPercent);

        // gift card price calculation
        if (($oCard = $this->getCard())) {
            if ($dVATPercent !== null) {
                $oCard->setWrappingVat($dVATPercent);
            }
            $oGiftCardPrice->addPrice($oCard->getWrappingPrice());
        }

        return $oGiftCardPrice;
    }

    /**
     * Payment cost calculation, applying payment discount if available.
     *
     * @return Price
     * @deprecated underscore prefix violates PSR12, will be renamed to "calcPaymentCost" in next major
     */
    protected function _calcPaymentCost() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // resetting values
        $oPaymentPrice = oxNew(Price::class);

        // payment
        if (($this->_sPaymentId = $this->getPaymentId())) {
            $oPayment = oxNew(Payment::class);
            $oPayment->load($this->_sPaymentId);

            $oPayment->calculate($this);
            $oPaymentPrice = $oPayment->getPrice();
        }

        return $oPaymentPrice;
    }

    /**
     * Sets basket additional costs
     *
     * @param string $sCostName additional costs
     * @param object $oPrice    Price
     */
    public function setCost($sCostName, $oPrice = null)
    {
        $this->_aCosts[$sCostName] = $oPrice;
    }

    /**
     * Executes all needed functions to calculate basket price and other needed
     * info
     *
     * @param bool $blForceUpdate set this parameter to TRUE to force basket recalculation
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function calculateBasket($blForceUpdate = false)
    {
        /*
        //would be good to perform the reset of previous calculation
        //at least you can use it for the debug
        $this->_aDiscounts = array();
        $this->_aItemDiscounts = array();
        $this->_oTotalDiscount = null;
        $this->_dDiscountedProductNettoPrice = 0;
        $this->_aDiscountedVats = array();
        $this->_oPrice = null;
        $this->_oNotDiscountedProductsPriceList = null;
        $this->_oProductsPriceList = null;
        $this->_oDiscountProductsPriceList = null;*/

        if (!$this->isEnabled()) {
            return;
        }

        if ($blForceUpdate) {
            $this->onUpdate();
        }

        if (!($this->_blUpdateNeeded || $blForceUpdate)) {
            return;
        }

        $this->_aCosts = [];

        //  1. saving basket to the database
        $this->_save();

        //  2. remove all bundles
        $this->_clearBundles();

        //  3. generate bundle items
        $this->_addBundles();

        //  4. calculating item prices
        $this->_calcItemsPrice();

        //  5. calculating/applying discounts
        $this->_calcBasketDiscount();

        //  6. calculating basket total discount
        $this->_calcBasketTotalDiscount();

        //  7. check for vouchers
        $this->_calcVoucherDiscount();

        //  8. applies all discounts to price-list
        $this->_applyDiscounts();

        //  9. calculating additional costs:
        //  9.1: delivery
        $this->setCost('oxdelivery', $this->_calcDeliveryCost());

        //  9.2: adding wrapping and gift card costs
        $this->setCost('oxwrapping', $this->_calcBasketWrapping());

        $this->setCost('oxgiftcard', $this->_calcBasketGiftCard());

        //  9.3: adding payment cost
        $this->setCost('oxpayment', $this->_calcPaymentCost());

        //  10. calculate total price
        $this->_calcTotalPrice();

        //  11. formatting discounts
        $this->formatDiscount();

        //  12.setting to up-to-date status
        $this->afterUpdate();
    }

    /**
     * Notifies basket that recalculation is needed
     */
    public function onUpdate()
    {
        $this->_blUpdateNeeded = true;
    }

    /**
     * Marks basket as up-to-date
     */
    public function afterUpdate()
    {
        $this->_blUpdateNeeded = false;
    }

    /**
     * Function collects summary information about basket. Usually this info
     * is used while calculating discounts or so. Data is stored in static
     * class parameter Basket::$_aBasketSummary
     *
     * @return object
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws ObjectException
     */
    public function getBasketSummary()
    {
        if ($this->_blUpdateNeeded || $this->_aBasketSummary === null) {
            $this->_aBasketSummary = new stdclass();
            $this->_aBasketSummary->aArticles = [];
            $this->_aBasketSummary->aCategories = [];
            $this->_aBasketSummary->iArticleCount = 0;
            $this->_aBasketSummary->dArticlePrice = 0;
            $this->_aBasketSummary->dArticleDiscountablePrice = 0;
        }

        if (!$this->isEnabled()) {
            return $this->_aBasketSummary;
        }

        $myConfig = Registry::getConfig();
        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            if (!$oBasketItem->isBundle() && $oArticle = $oBasketItem->getArticle(false)) {
                $aCatIds = $oArticle->getCategoryIds();
                //#M530 if price is not loaded for articles
                $dPrice = 0;
                $dDiscountablePrice = 0;
                if (($oPrice = $oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $this))) {
                    $dPrice = $oPrice->getPrice();
                    if (!$oArticle->skipDiscounts()) {
                        $dDiscountablePrice = $dPrice;
                    }
                }

                foreach ($aCatIds as $sCatId) {
                    if (!isset($this->_aBasketSummary->aCategories[$sCatId])) {
                        $priceObject = new stdClass();
                        $priceObject->dPrice = 0;
                        $priceObject->dDiscountablePrice = 0;
                        $priceObject->dAmount = 0;
                        $priceObject->iCount = 0;
                        $this->_aBasketSummary->aCategories[$sCatId] = $priceObject;
                    }

                    $categorySummaryPrice = $this->_aBasketSummary->aCategories[$sCatId];
                    $categorySummaryPrice->dPrice += $dPrice * $oBasketItem->getAmount();
                    $categorySummaryPrice->dDiscountablePrice += $dDiscountablePrice * $oBasketItem->getAmount();
                    $categorySummaryPrice->dAmount += $oBasketItem->getAmount();
                    $categorySummaryPrice->iCount++;
                }

                // variant handling
                if (($sParentId = $oArticle->getParentId()) && $myConfig->getConfigParam('blVariantParentBuyable')) {
                    if (!isset($this->_aBasketSummary->aArticles[$sParentId])) {
                        $this->_aBasketSummary->aArticles[$sParentId] = 0;
                    }
                    $this->_aBasketSummary->aArticles[$sParentId] += $oBasketItem->getAmount();
                }

                if (!isset($this->_aBasketSummary->aArticles[$oBasketItem->getProductId()])) {
                    $this->_aBasketSummary->aArticles[$oBasketItem->getProductId()] = 0;
                }

                $this->_aBasketSummary->aArticles[$oBasketItem->getProductId()] += $oBasketItem->getAmount();
                $this->_aBasketSummary->iArticleCount += $oBasketItem->getAmount();
                $this->_aBasketSummary->dArticlePrice += $dPrice * $oBasketItem->getAmount();
                $this->_aBasketSummary->dArticleDiscountablePrice += $dDiscountablePrice * $oBasketItem->getAmount();
            }
        }

        return $this->_aBasketSummary;
    }

    /**
     * Checks and sets voucher information. Checks its availability according
     * to few conditions: oxvoucher::checkVoucherAvailability(),
     * oxvoucher::checkUserAvailability(). Errors are stored in
     * Basket::voucherErrors array. After all voucher is marked as reserved
     * (oxvoucher::MarkAsReserved())
     *
     * @param string $sVoucherId voucher ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addVoucher($sVoucherId)
    {
        // calculating price to check
        // P using prices sum which has discount, not sum of skipped discounts
        $dPrice = 0;
        if ($this->_oDiscountProductsPriceList) {
            $dPrice = $this->_oDiscountProductsPriceList->getSum($this->isCalculationModeNetto());
        }

        try { // trying to load voucher and apply it
            $oVoucher = oxNew(Voucher::class);

            if (!$this->_blSkipVouchersAvailabilityChecking) {
                $oDb = DatabaseProvider::getMaster();

                $oDb->startTransaction();

                try {
                    $oVoucher->getVoucherByNr($sVoucherId, $this->_aVouchers, true);
                    $oVoucher->checkVoucherAvailability($this->_aVouchers, $dPrice);
                    $oVoucher->checkUserAvailability($this->getBasketUser());
                    $oVoucher->markAsReserved();
                } catch (Exception $exception) {
                    $oDb->rollbackTransaction();

                    if ($exception instanceof VoucherException) {
                        throw $exception;
                    } else {
                        $oEx = oxNew(VoucherException::class);
                        $oEx->setMessage('Something went wrong, please try again');
                        $oEx->setVoucherNr($oVoucher->oxvouchers__oxvouchernr->value);
                        throw $oEx;
                    }
                }

                $oDb->commitTransaction();
            } else {
                $oVoucher->load($sVoucherId);
            }

            // saving voucher info
            $this->_aVouchers[$oVoucher->oxvouchers__oxid->value] = $oVoucher->getSimpleVoucher();
        } catch (VoucherException $oEx) {
            // problems adding voucher
            Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);
        }

        $this->onUpdate();
    }

    /**
     * Removes voucher from basket and unreserved it.
     *
     * @param string $sVoucherId removable voucher ID
     */
    public function removeVoucher($sVoucherId)
    {
        // removing if it exists
        if (isset($this->_aVouchers[$sVoucherId])) {
            $oVoucher = oxNew(Voucher::class);
            $oVoucher->load($sVoucherId);

            $oVoucher->unMarkAsReserved();

            // unset it if exists this voucher in DB or not
            unset($this->_aVouchers[$sVoucherId]);
            $this->onUpdate();
        }
    }

    /**
     * Resets user related information kept in basket object
     */
    public function resetUserInfo()
    {
        $this->setPayment(null);
        $this->setShipping(null);
    }

    /**
     * Formatting discounts
     */
    protected function formatDiscount()
    {
        // discount information
        // formatting discount value
        $this->aDiscounts = $this->getDiscounts();
        if (is_array($this->aDiscounts) && count($this->aDiscounts) > 0) {
            $oLang = Registry::getLang();
            foreach ($this->aDiscounts as $oDiscount) {
                $oDiscount->fDiscount = $oLang->formatCurrency($oDiscount->dDiscount, $this->getBasketCurrency());
            }
        }
    }

    /**
     * Checks whether basket can be saved
     *
     * @deprecated in v5.2.0 on 2013-04-28; use Basket::isSaveToDataBaseEnabled()
     *
     * @return bool
     */
    protected function _canSaveBasket() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isSaveToDataBaseEnabled();
    }

    /**
     * Populates current basket from the saved one.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function load()
    {
        $oUser = $this->getBasketUser();
        if (!$oUser) {
            return;
        }

        $oBasket = $oUser->getBasket('savedbasket');

        // restoring from saved history
        $aSavedItems = $oBasket->getItems();
        foreach ($aSavedItems as $oItem) {
            try {
                $oSelList = $oItem->getSelList();

                $this->addToBasket($oItem->oxuserbasketitems__oxartid->value, $oItem->oxuserbasketitems__oxamount->value, $oSelList, $oItem->getPersParams(), true);
            } catch (ArticleException $oEx) {
                // caught and ignored
            }
        }
    }

    /**
     * Saves existing basket to database
     * @deprecated underscore prefix violates PSR12, will be renamed to "save" in next major
     */
    protected function _save() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->isSaveToDataBaseEnabled()) {
            if ($oUser = $this->getBasketUser()) {
                //first delete all contents
                //#2039
                $oSavedBasket = $oUser->getBasket('savedbasket');
                $oSavedBasket->delete();

                //then save
                /** @var BasketItem $oBasketItem */
                foreach ($this->_aBasketContents as $oBasketItem) {
                    // discount or bundled products will be added automatically if available
                    if (!$oBasketItem->isBundle() && !$oBasketItem->isDiscountArticle()) {
                        $oSavedBasket->addItemToBasket($oBasketItem->getProductId(), $oBasketItem->getAmount(), $oBasketItem->getSelList(), true, $oBasketItem->getPersParams());
                    }
                }
            }
        }
    }

    /**
     * Cleans up saved basket data. This method usually is initiated by
     * Basket::deleteBasket() method which cleans up basket data when
     * user completes order.
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteSavedBasket" in next major
     */
    protected function _deleteSavedBasket() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // deleting basket if session user available
        if ($oUser = $this->getBasketUser()) {
            $oUser->getBasket('savedbasket')->delete();
        }

        // basket exclude
        if (Registry::getConfig()->getConfigParam('blBasketExcludeEnabled')) {
            $this->setBasketRootCatId(null);
        }
    }

    /**
     * Tries to fetch user delivery country ID
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "findDelivCountry" in next major
     */
    protected function _findDelivCountry() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $oUser = $this->getBasketUser();

        $sDeliveryCountry = null;

        if (!$oUser) {
            // don't calculate if not logged in unless specified otherwise
            $aHomeCountry = $myConfig->getConfigParam('aHomeCountry');
            if ($myConfig->getConfigParam('blCalculateDelCostIfNotLoggedIn') && is_array($aHomeCountry)) {
                $sDeliveryCountry = current($aHomeCountry);
            }
        } else {
            // ok, logged in
            if ($sCountryId = $myConfig->getGlobalParameter('delcountryid')) {
                $sDeliveryCountry = $sCountryId;
            } elseif ($sAddressId = Registry::getSession()->getVariable('deladrid')) {
                $oDeliveryAddress = oxNew(Address::class);
                if ($oDeliveryAddress->load($sAddressId)) {
                    $sDeliveryCountry = $oDeliveryAddress->oxaddress__oxcountryid->value;
                }
            }

            // still not found ?
            if (!$sDeliveryCountry) {
                $sDeliveryCountry = $oUser->oxuser__oxcountryid->value;
            }
        }

        return $sDeliveryCountry;
    }

    /**
     * Deletes user basket object from session
     */
    public function deleteBasket()
    {
        $this->_aBasketContents = [];
        Registry::getSession()->delBasket();

        if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
            Registry::getSession()->getBasketReservations()->discardReservations();
        }

        // merging basket history
        $this->_deleteSavedBasket();
    }

    /**
     * Set basket payment ID
     *
     * @param string $sPaymentId payment id
     */
    public function setPayment($sPaymentId = null)
    {
        $this->_sPaymentId = $sPaymentId;
    }

    /**
     * Get basket payment, if payment id is not set, try to get it from session
     *
     * @return string
     */
    public function getPaymentId()
    {
        if (!$this->_sPaymentId) {
            $this->_sPaymentId = Registry::getSession()->getVariable('paymentid');
        }

        return $this->_sPaymentId;
    }

    /**
     * Set basket shipping set ID
     *
     * @param string $sShippingSetId delivery set id
     */
    public function setShipping($sShippingSetId = null)
    {
        $this->_sShippingSetId = $sShippingSetId;
        Registry::getSession()->setVariable('sShipSet', $sShippingSetId);
    }

    /**
     * Set basket shipping price
     *
     * @param Price $oShippingPrice delivery costs
     */
    public function setDeliveryPrice($oShippingPrice = null)
    {
        $this->_oDeliveryPrice = $oShippingPrice;
    }

    /**
     * Get basket shipping set, if shipping set id is not set, try to get it from session
     *
     * @return string oxDeliverySet
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getShippingId()
    {
        if (!$this->_sShippingSetId) {
            $this->_sShippingSetId = Registry::getSession()->getVariable('sShipSet');
        }

        $sActPaymentId = $this->getPaymentId();
        // setting default if none is set
        if (!$this->_sShippingSetId && $sActPaymentId != 'oxempty') {
            $oUser = $this->getUser();

            // choosing first preferred delivery set
            list(, $sActShipSet) = Registry::get(DeliverySetList::class)->getDeliverySetData(null, $oUser, $this);
            // in case nothing was found and no user set - choosing default
            $this->_sShippingSetId = $sActShipSet ? $sActShipSet : ($oUser ? null : 'oxidstandard');
        } elseif (!$this->isAdmin() && $sActPaymentId == 'oxempty') {
            // in case 'oxempty' is payment id - delivery set must be reset
            $this->_sShippingSetId = null;
        }

        return $this->_sShippingSetId;
    }

    /**
     * Returns array of basket oxarticle objects
     *
     * @return array
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketArticles()
    {
        $aBasketArticles = [];
        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $sItemKey => $oBasketItem) {
            try {
                $oProduct = $oBasketItem->getArticle(true);

                if (Registry::getConfig()->getConfigParam('bl_perfLoadSelectLists')) {
                    // marking chosen select list
                    $aSelList = $oBasketItem->getSelList();
                    if (is_array($aSelList) && ($aSelectlist = $oProduct->getSelectLists($sItemKey))) {
                        reset($aSelList);
                        foreach ($aSelList as $key => $iSel) {
                            $aSelectlist[$key][$iSel]->selected = 1;
                        }
                        $oProduct->setSelectlist($aSelectlist);
                    }
                }
            } catch (NoArticleException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
                $this->removeItem($sItemKey);
                $this->calculateBasket(true);
                continue;
            } catch (ArticleInputException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
                $this->removeItem($sItemKey);
                $this->calculateBasket(true);
                continue;
            }

            $aBasketArticles[$sItemKey] = $oProduct;
        }

        return $aBasketArticles;
    }

    /**
     * Returns price list object of discounted products
     *
     * @return PriceList
     */
    public function getDiscountProductsPrice()
    {
        return $this->_oDiscountProductsPriceList;
    }

    /**
     * Returns basket products price list object
     *
     * @return PriceList
     */
    public function getProductsPrice()
    {
        if (is_null($this->_oProductsPriceList)) {
            $this->_oProductsPriceList = oxNew(PriceList::class);
        }

        return $this->_oProductsPriceList;
    }

    /**
     * Returns basket price object
     *
     * @return Price
     */
    public function getPrice()
    {
        if (is_null($this->_oPrice)) {
            /** @var Price $price */
            $price = oxNew(Price::class);
            $this->setPrice($price);
        }

        return $this->_oPrice;
    }

    /**
     * Set basket total sum price object
     *
     * @param Price $oPrice Price object
     */
    public function setPrice($oPrice)
    {
        $this->_oPrice = $oPrice;
    }


    /**
     * Returns unique order ID assigned to current basket.
     * This id is only available on last order step
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->_sOrderId;
    }

    /**
     * Basket order ID setter
     *
     * @param string $sId unique id for basket order
     */
    public function setOrderId($sId)
    {
        $this->_sOrderId = $sId;
    }

    /**
     * Returns array of basket costs. By passing cost identifier method will return
     * this cost if available
     *
     * @param string $sId cost id ( optional )
     *
     * @return array|Price|null
     */
    public function getCosts($sId = null)
    {
        // if user want some specific cost - return it
        if ($sId) {
            return isset($this->_aCosts[$sId]) ? $this->_aCosts[$sId] : null;
        }

        return $this->_aCosts;
    }

    /**
     * Returns array of vouchers applied to basket
     *
     * @return array
     */
    public function getVouchers()
    {
        return $this->_aVouchers;
    }

    /**
     * Returns number of different products stored in basket.
     *
     * @return int
     */
    public function getProductsCount()
    {
        return count($this->_aBasketContents);
    }

    /**
     * Returns count of items stored in basket.
     *
     * @return double
     */
    public function getItemsCount()
    {
        $itemsCount = 0;

        foreach ($this->_aBasketContents as $oBasketItem) {
            $itemsCount += $oBasketItem->getAmount();
        }

        return $itemsCount;
    }

    /**
     * Returns total basket weight.
     *
     * @return double
     */
    public function getWeight()
    {
        $weight = 0;

        foreach ($this->_aBasketContents as $oBasketItem) {
            $weight += $oBasketItem->getWeight();
        }

        return $weight;
    }

    /**
     * Returns basket items array
     *
     * @return array
     */
    public function getContents()
    {
        return $this->_aBasketContents;
    }

    /**
     * Returns array of plain of formatted VATs which were calculated for basket
     *
     * @param bool $blFormatCurrency enables currency formatting
     *
     * @return array
     */
    public function getProductVats($blFormatCurrency = true)
    {
        if (!$this->_oNotDiscountedProductsPriceList) {
            return [];
        }

        $aVats = $this->_oNotDiscountedProductsPriceList->getVatInfo($this->isCalculationModeNetto());

        $oUtils = Registry::getUtils();
        foreach ((array)$this->_aDiscountedVats as $sKey => $dVat) {
            if (!isset($aVats[$sKey])) {
                $aVats[$sKey] = 0;
            }
            // add prices of the same discounts
            $aVats[$sKey] += $oUtils->fRound($dVat, $this->_oCurrency);
        }

        if ($blFormatCurrency) {
            $oLang = Registry::getLang();
            foreach ($aVats as $sKey => $dVat) {
                $aVats[$sKey] = $oLang->formatCurrency($dVat, $this->getBasketCurrency());
            }
        }

        return $aVats;
    }

    /**
     * Gift card message setter
     *
     * @param string $sMessage gift card message
     */
    public function setCardMessage($sMessage)
    {
        $this->_sCardMessage = $sMessage;
    }

    /**
     * Returns gift card message text
     *
     * @return string
     */
    public function getCardMessage()
    {
        return $this->_sCardMessage;
    }

    /**
     * Gift card ID setter
     *
     * @param string $sCardId gift card id
     */
    public function setCardId($sCardId)
    {
        $this->_sCardId = $sCardId;
    }

    /**
     * Returns applied gift card ID
     *
     * @return string
     */
    public function getCardId()
    {
        return $this->_sCardId;
    }

    /**
     * Returns gift card object (if available)
     *
     * @return Wrapping
     */
    public function getCard()
    {
        $oCard = null;
        if ($sCardId = $this->getCardId()) {
            $oCard = oxNew(Wrapping::class);
            $oCard->load($sCardId);
            $oCard->setWrappingVat($this->getAdditionalServicesVatPercent());
        }

        return $oCard;
    }

    /**
     * Returns total basket discount Price object
     *
     * @return Price
     */
    public function getTotalDiscount()
    {
        return $this->_oTotalDiscount;
    }

    /**
     * Returns applied discount information array
     *
     * @return array
     */
    public function getDiscounts()
    {
        if ($this->getTotalDiscount() && $this->getTotalDiscount()->getBruttoPrice() == 0 && count($this->_aItemDiscounts) == 0) {
            return [];
        }

        return array_merge($this->_aItemDiscounts, $this->_aDiscounts);
    }

    /**
     * Returns basket voucher discount price object
     *
     * @return Price
     */
    public function getVoucherDiscount()
    {
        if (Registry::getConfig()->getConfigParam('bl_showVouchers')) {
            return $this->_oVoucherDiscount;
        }

        return null;
    }

    /**
     * Set basket currency
     *
     * @param stdClass $oCurrency currency object
     */
    public function setBasketCurrency($oCurrency)
    {
        $this->_oCurrency = $oCurrency;
    }

    /**
     * Basket currency getter
     *
     * @return stdClass
     */
    public function getBasketCurrency()
    {
        if ($this->_oCurrency === null) {
            $this->_oCurrency = Registry::getConfig()->getActShopCurrencyObject();
        }

        return $this->_oCurrency;
    }

    /**
     * Set skip or not vouchers availability checking
     *
     * @param bool $blSkipChecking skip or not vouchers checking
     */
    public function setSkipVouchersChecking($blSkipChecking = null)
    {
        $this->_blSkipVouchersAvailabilityChecking = $blSkipChecking;
    }

    /**
     * Returns true if discount must be skipped for one of the products
     *
     * @return bool
     */
    public function hasSkipedDiscount()
    {
        return $this->_blSkipDiscounts;
    }

    /**
     * Used to set "skip discounts" status for basket
     *
     * @param bool $blSkip set true to skip discounts
     */
    public function setSkipDiscounts($blSkip)
    {
        $this->_blSkipDiscounts = $blSkip;
    }

    /**
     * Formatted Products net price getter
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getProductsNetPrice()
    {
        return Registry::getLang()->formatCurrency($this->getNettoSum(), $this->getBasketCurrency());
    }

    /**
     * Formatted Products price getter
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getFProductsPrice()
    {
        return Registry::getLang()->formatCurrency($this->getBruttoSum(), $this->getBasketCurrency());
    }

    /**
     * Returns VAT of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return double
     */
    public function getDelCostVatPercent()
    {
        return $this->getCosts('oxdelivery')->getVat();
    }

    /**
     * Returns formatted VAT of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getDelCostVat()
    {
        $dDelVAT = $this->getCosts('oxdelivery')->getVatValue();

        // blShowVATForDelivery option will be used, only for displaying, but not calculation
        if ($dDelVAT > 0 && Registry::getConfig()->getConfigParam('blShowVATForDelivery')) {
            return Registry::getLang()->formatCurrency($dDelVAT, $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Returns formatted netto price of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getDelCostNet()
    {
        $oConfig = Registry::getConfig();

        // blShowVATForDelivery option will be used, only for displaying, but not calculation
        if ($oConfig->getConfigParam('blShowVATForDelivery') && ($this->getBasketUser() || $oConfig->getConfigParam('blCalculateDelCostIfNotLoggedIn'))) {
            $dNetPrice = $this->getCosts('oxdelivery')->getNettoPrice();
            if ($dNetPrice > 0) {
                return Registry::getLang()->formatCurrency($dNetPrice, $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns VAT of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return double
     */
    public function getPayCostVatPercent()
    {
        return $this->getCosts('oxpayment')->getVat();
    }

    /**
     * Returns formatted VAT of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getPayCostVat()
    {
        $dPayVAT = $this->getCosts('oxpayment')->getVatValue();

        // blShowVATForPayCharge option will be used, only for displaying, but not calculation
        if ($dPayVAT > 0 && Registry::getConfig()->getConfigParam('blShowVATForPayCharge')) {
            return Registry::getLang()->formatCurrency($dPayVAT, $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Returns formatted netto price of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getPayCostNet()
    {
        // blShowVATForPayCharge option will be used, only for displaying, but not calculation
        if (Registry::getConfig()->getConfigParam('blShowVATForPayCharge')) {
            $oPaymentCost = $this->getCosts('oxpayment');
            if ($oPaymentCost && $oPaymentCost->getNettoPrice()) {
                return Registry::getLang()->formatCurrency($this->getCosts('oxpayment')->getNettoPrice(), $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns payment costs brutto value
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return double|void
     */
    public function getPaymentCosts()
    {
        $oPaymentCost = $this->getCosts('oxpayment');
        if ($oPaymentCost && $oPaymentCost->getBruttoPrice()) {
            return $oPaymentCost->getBruttoPrice();
        }
    }

    /**
     * Returns payment costs
     *
     * @return Price
     */
    public function getPaymentCost()
    {
        return $this->getCosts('oxpayment');
    }

    /**
     * Returns if exists formatted payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getFPaymentCosts()
    {
        $oPaymentCost = $this->getCosts('oxpayment');
        if ($oPaymentCost && $oPaymentCost->getBruttoPrice()) {
            return Registry::getLang()->formatCurrency($oPaymentCost->getBruttoPrice(), $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Returns value of voucher discount
     *
     * @return double
     */
    public function getVoucherDiscValue()
    {
        if ($this->getVoucherDiscount()) {
            return $this->getVoucherDiscount()->getBruttoPrice();
        }

        return false;
    }

    /**
     * Returns formatted voucher discount
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getFVoucherDiscountValue()
    {
        if ($oVoucherDiscount = $this->getVoucherDiscount()) {
            if ($oVoucherDiscount->getBruttoPrice()) {
                return Registry::getLang()->formatCurrency($oVoucherDiscount->getBruttoPrice(), $this->getBasketCurrency());
            }
        }

        return false;
    }


    /**
     * Returns VAT of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return double
     */
    public function getWrappCostVatPercent()
    {
        return $this->getCosts('oxwrapping')->getVat();
    }


    /**
     * Returns VAT of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return double
     */
    public function getGiftCardCostVatPercent()
    {
        return $this->getCosts('oxgiftcard')->getVat();
    }

    /**
     * Returns formatted VAT of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getWrappCostVat()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::getConfig()->getConfigParam('blShowVATForWrapping')) {
            $oPrice = $this->getCosts('oxwrapping');

            if ($oPrice && $oPrice->getVatValue() > 0) {
                return Registry::getLang()->formatCurrency($oPrice->getVatValue(), $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns formatted netto price of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getWrappCostNet()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::getConfig()->getConfigParam('blShowVATForWrapping')) {
            $oPrice = $this->getCosts('oxwrapping');

            if ($oPrice && $oPrice->getNettoPrice() > 0) {
                return Registry::getLang()->formatCurrency($oPrice->getNettoPrice(), $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns if exists formatted wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getFWrappingCosts()
    {
        $oPrice = $this->getCosts('oxwrapping');

        if ($oPrice && $oPrice->getBruttoPrice()) {
            return Registry::getLang()->formatCurrency($oPrice->getBruttoPrice(), $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Returns array of wrapping costs
     *
     * @return array
     */
    public function getWrappingCost()
    {
        return $this->getCosts('oxwrapping');
    }

    /**
     * Returns formatted VAT of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getGiftCardCostVat()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::getConfig()->getConfigParam('blShowVATForWrapping')) {
            $oPrice = $this->getCosts('oxgiftcard');

            if ($oPrice && $oPrice->getVatValue() > 0) {
                return Registry::getLang()->formatCurrency($oPrice->getVatValue(), $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns formatted netto price of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getGiftCardCostNet()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::getConfig()->getConfigParam('blShowVATForWrapping')) {
            $oPrice = $this->getCosts('oxgiftcard');

            if ($oPrice && $oPrice->getNettoPrice() > 0) {
                return Registry::getLang()->formatCurrency($oPrice->getNettoPrice(), $this->getBasketCurrency());
            }
        }

        return false;
    }

    /**
     * Returns if exists formatted gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getFGiftCardCosts()
    {
        $oPrice = $this->getCosts('oxgiftcard');

        if ($oPrice && $oPrice->getBruttoPrice()) {
            return Registry::getLang()->formatCurrency($oPrice->getBruttoPrice(), $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Gets gift card cost.
     *
     * @return Price
     */
    public function getGiftCardCost()
    {
        return $this->getCosts('oxgiftcard');
    }

    /**
     * Returns formatted basket total price
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string
     */
    public function getFPrice()
    {
        return Registry::getLang()->formatCurrency($this->getPrice()->getBruttoPrice(), $this->getBasketCurrency());
    }

    /**
     * Returns if exists formatted delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getFDeliveryCosts()
    {
        $oPrice = $this->getCosts('oxdelivery');

        if ($oPrice && ($this->getBasketUser() || Registry::getConfig()->getConfigParam('blCalculateDelCostIfNotLoggedIn'))) {
            return Registry::getLang()->formatCurrency($oPrice->getBruttoPrice(), $this->getBasketCurrency());
        }

        return false;
    }

    /**
     * Returns if exists delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     *
     * @return string | bool
     */
    public function getDeliveryCosts()
    {
        if ($oDeliveryCost = $this->getCosts('oxdelivery')) {
            return $oDeliveryCost->getBruttoPrice();
        }

        return false;
    }

    /**
     * Returns delivery costs
     *
     * @return Price
     */
    public function getDeliveryCost()
    {
        return $this->getCosts('oxdelivery');
    }

    /**
     * Sets total discount value
     *
     * @param double $dDiscount new total discount value
     */
    public function setTotalDiscount($dDiscount)
    {
        $this->_oTotalDiscount = oxNew(Price::class);
        $this->_oTotalDiscount->setBruttoPriceMode();
        $this->_oTotalDiscount->add($dDiscount);
    }

    /**
     * Get basket price for payment cost calculation. Returned price
     * is with applied discounts, vouchers and added delivery cost
     *
     * @return double
     */
    public function getPriceForPayment()
    {
        $dPrice = $this->getDiscountedProductsBruttoPrice();
        //#1905 not discounted products should be included in payment amount calculation
        if ($oPriceList = $this->getNotDiscountProductsPrice()) {
            $dPrice += $oPriceList->getBruttoSum();
        }

        // adding delivery price to final price
        if ($oDeliveryPrice = $this->_aCosts['oxdelivery']) {
            $dPrice += $oDeliveryPrice->getBruttoPrice();
        }

        return $dPrice;
    }


    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     * @deprecated will be renamed to "getDiscountedProductsSum" in next major
     */
    public function _getDiscountedProductsSum()  // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($oProductsPrice = $this->getDiscountProductsPrice()) {
            $dPrice = $oProductsPrice->getSum($this->isCalculationModeNetto());
        }

        // subtracting total discount
        if ($oPrice = $this->getTotalDiscount()) {
            $dPrice -= $oPrice->getPrice();
        }

        if ($oVoucherPrice = $this->getVoucherDiscount()) {
            $dPrice -= $oVoucherPrice->getPrice();
        }

        return $dPrice;
    }

    /**
     * Gets total discount sum.
     *
     * @return float|int
     */
    public function getTotalDiscountSum()
    {
        $dPrice = 0;
        // subtracting total discount
        if ($oPrice = $this->getTotalDiscount()) {
            $dPrice += $oPrice->getPrice();
        }

        if ($oVoucherPrice = $this->getVoucherDiscount()) {
            $dPrice += $oVoucherPrice->getPrice();
        }

        return $dPrice;
    }

    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     */
    public function getDiscountedProductsBruttoPrice()
    {
        if ($oProductsPrice = $this->getDiscountProductsPrice()) {
            $dPrice = $oProductsPrice->getBruttoSum();
        }

        // subtracting total discount
        if ($oPrice = $this->getTotalDiscount()) {
            $dPrice -= $oPrice->getBruttoPrice();
        }

        if ($oVoucherPrice = $this->getVoucherDiscount()) {
            $dPrice -= $oVoucherPrice->getBruttoPrice();
        }

        return $dPrice;
    }

    /**
     * Returns TRUE if ( current basket products sum - total discount - voucher discount ) > 0
     *
     * @return bool
     */
    public function isBelowMinOrderPrice()
    {
        $blIsBelowMinOrderPrice = false;
        $sConfValue = Registry::getConfig()->getConfigParam('iMinOrderPrice');
        if (is_numeric($sConfValue) && $this->getProductsCount()) {
            $dMinOrderPrice = Price::getPriceInActCurrency((double) $sConfValue);
            $dNotDiscountedProductPrice = 0;
            if ($oPrice = $this->getNotDiscountProductsPrice()) {
                $dNotDiscountedProductPrice = $oPrice->getBruttoSum();
            }
            $blIsBelowMinOrderPrice = ($dMinOrderPrice > ($this->getDiscountedProductsBruttoPrice() + $dNotDiscountedProductPrice));
        }

        return $blIsBelowMinOrderPrice;
    }

    /**
     * Returns stock of article in basket, including bundle article
     *
     * @param string $sArtId        article id
     * @param string $sExpiredArtId item id of updated article
     *
     * @return double
     */
    public function getArtStockInBasket($sArtId, $sExpiredArtId = null)
    {
        $dArtStock = 0;
        foreach ($this->_aBasketContents as $sItemKey => $oOrderArticle) {
            if ($oOrderArticle && ($sExpiredArtId == null || $sExpiredArtId != $sItemKey)) {
                if ($oOrderArticle->getArticle(true)->getId() == $sArtId) {
                    $dArtStock += $oOrderArticle->getAmount();
                }
            }
        }

        return $dArtStock;
    }

    /**
     * Checks if product can be added to basket
     *
     * @param string $sProductId product id
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function canAddProductToBasket($sProductId)
    {
        $blCanAdd = null;

        // if basket category is not set...
        if ($this->_sBasketCategoryId === null) {
            $oCat = null;

            // request category
            if ($oView = Registry::getConfig()->getActiveView()) {
                if ($oCat = $oView->getActiveCategory()) {
                    if (!$this->_isProductInRootCategory($sProductId, $oCat->oxcategories__oxrootid->value)) {
                        $oCat = null;
                    } else {
                        $blCanAdd = true;
                    }
                }
            }

            // product main category
            if (!$oCat) {
                $oProduct = oxNew(Article::class);
                if ($oProduct->load($sProductId)) {
                    $oCat = $oProduct->getCategory();
                }
            }

            // root category id
            if ($oCat) {
                $this->setBasketRootCatId($oCat->oxcategories__oxrootid->value);
            }
        }

        // avoiding double check...
        if ($blCanAdd === null) {
            $blCanAdd = $this->_sBasketCategoryId ? $this->_isProductInRootCategory($sProductId, $this->getBasketRootCatId()) : true;
        }

        return $blCanAdd;
    }

    /**
     * Checks if product is in root category
     *
     * @param string $sProductId product id
     * @param string $sRootCatId root category id
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "isProductInRootCategory" in next major
     */
    protected function _isProductInRootCategory($sProductId, $sRootCatId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sO2CTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxobject2category');
        $sCatTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories');

        $oDb = DatabaseProvider::getDb();
        $sParentId = $oDb->getOne("select oxparentid from oxarticles where oxid = :oxid", [
            ':oxid' => $sProductId
        ]);
        $sProductId = $sParentId ? $sParentId : $sProductId;

        $sQ = "select 1 from {$sO2CTable}
                 left join {$sCatTable} on {$sCatTable}.oxid = {$sO2CTable}.oxcatnid
                 where {$sO2CTable}.oxobjectid = :oxobjectid and
                       {$sCatTable}.oxrootid = :oxrootid";

        return (bool) $oDb->getOne($sQ, [
            ':oxobjectid' => $sProductId,
            ':oxrootid' => $sRootCatId
        ]);
    }

    /**
     * Set active basket root category
     *
     * @param string $sRoot Root category id
     */
    public function setBasketRootCatId($sRoot)
    {
        $this->_sBasketCategoryId = $sRoot;
    }

    /**
     * Get active basket root category
     *
     * @return string
     */
    public function getBasketRootCatId()
    {
        return $this->_sBasketCategoryId;
    }

    /**
     * Sets category change warn state
     *
     * @param bool $blShow to show warning or not
     */
    public function setCatChangeWarningState($blShow)
    {
        $this->_blShowCatChangeWarning = $blShow;
    }

    /**
     * Tells to show category change warning
     *
     * @return bool
     */
    public function showCatChangeWarning()
    {
        return $this->_blShowCatChangeWarning;
    }

    /**
     * Returns price list object of not discounted products
     *
     * @return PriceList in v4.8/5.1 on 2013-10-14; for formatting use oxPrice smarty plugin
     */
    public function getNotDiscountProductsPrice()
    {
        return $this->_oNotDiscountedProductsPriceList;
    }

    /**
     * Is called when new basket item is successfully added.
     *
     * @param string $sProductID       id of product
     * @param double $dAmount          product amount
     * @param array  $aSel             product select lists (default null)
     * @param array  $aPersParam       product persistent parameters (default null)
     * @param bool   $blOverride       marker to accumulate passed amount or renew (default false)
     * @param bool   $blBundle         marker if product is bundle or not (default false)
     * @param string $sOldBasketItemId id if old basket item if to change it
     *
     * @deprecated since v.6.0.0 (2017-08-24); Use addedNewItem() instead.
     */
    protected function _addedNewItem($sProductID, $dAmount, $aSel, $aPersParam, $blOverride, $blBundle, $sOldBasketItemId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->addedNewItem($blOverride);
    }

    /**
     * Is called when new basket item is successfully added.
     *
     * @param bool $blOverride marker to accumulate passed amount or renew (default false).
     */
    protected function addedNewItem($blOverride)
    {
        if (!$blOverride) {
            $this->_blNewITemAdded = null;
            Registry::getSession()->setVariable("blAddedNewItem", true);
        }
    }

    /**
     * Resets new basket item addition state on deserialization
     */
    public function __wakeUp()
    {
        $this->_blNewITemAdded = null;
        $this->_isCalculationModeNetto = null;
    }

    /**
     * Returns true if new product was just added to basket
     *
     * @return bool
     */
    public function isNewItemAdded()
    {
        if ($this->_blNewITemAdded == null) {
            $this->_blNewITemAdded = (bool) Registry::getSession()->getVariable("blAddedNewItem");
            Registry::getSession()->deleteVariable("blAddedNewItem");
        }

        return $this->_blNewITemAdded;
    }

    /**
     * Returns true if at least one product is downloadable in basket
     *
     * @return bool
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function hasDownloadableProducts()
    {
        $this->_blDownloadableProducts = false;
        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            if ($oBasketItem->getArticle(false) && $oBasketItem->getArticle(false)->isDownloadable()) {
                $this->_blDownloadableProducts = true;
                break;
            }
        }

        return $this->_blDownloadableProducts;
    }

    /**
     * Returns whether there are any articles in basket with intangible products agreement enabled.
     *
     * @return bool
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function hasArticlesWithIntangibleAgreement()
    {
        $blHasArticlesWithIntangibleAgreement = false;

        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            if ($oBasketItem->getArticle(false) && $oBasketItem->getArticle(false)->hasIntangibleAgreement()) {
                $blHasArticlesWithIntangibleAgreement = true;
                break;
            }
        }

        return $blHasArticlesWithIntangibleAgreement;
    }

    /**
     * Returns whether there are any articles in basket with downloadable products agreement enabled.
     *
     * @return bool
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function hasArticlesWithDownloadableAgreement()
    {
        $blHasArticlesWithIntangibleAgreement = false;

        /** @var BasketItem $oBasketItem */
        foreach ($this->_aBasketContents as $oBasketItem) {
            if ($oBasketItem->getArticle(false) && $oBasketItem->getArticle(false)->hasDownloadableAgreement()) {
                $blHasArticlesWithIntangibleAgreement = true;
                break;
            }
        }

        return $blHasArticlesWithIntangibleAgreement;
    }

    /**
     * Returns min order price value
     *
     * @return float
     */
    public function getMinOrderPrice()
    {
        return Price::getPriceInActCurrency(Registry::getConfig()->getConfigParam('iMinOrderPrice'));
    }
}
