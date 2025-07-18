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

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * UserBasketItem class, responsible for storing most important fields
 *
 */
class BasketItem extends Base
{
    /**
     * Product ID
     *
     * @var string
     */
    protected $_sProductId = null;

    /**
     * Basket product title
     *
     * @var string
     */
    protected $_sTitle = null;

    /**
     * Variant var select
     *
     * @var string
     */
    protected $_sVarSelect = null;

    /**
     * Product icon name
     *
     * @var string
     */
    protected $_sIcon = null;

    /**
     * Product details link
     *
     * @var string
     */
    protected $_sLink = null;

    /**
     * Item price
     *
     * @var Price
     */
    protected $_oPrice = null;

    /**
     * Item unit price
     *
     * @var Price
     */
    protected $_oUnitPrice = null;

    /**
     * Basket item total amount
     *
     * @var double
     */
    protected $_dAmount = 0.0;

    /**
     * Total basket item weight
     *
     * @var double
     */
    protected $_dWeight = 0;

    /**
     * Basket item select lists
     *
     * @var array
     */
    protected $_aSelList = [];

    /**
     * Shop id where product was put into basket
     *
     * @var string
     */
    protected $_sShopId = null;

    /**
     * Native product shop ID
     *
     * @var string
     */
    protected $_sNativeShopId = null;

    /**
     * Skip discounts marker
     *
     * @var boolean
     */
    protected $_blSkipDiscounts = false;

    /**
     * Persistent basket item parameters
     *
     * @var array
     */
    protected $_aPersistentParameters = [];

    /**
     * Bundle marker - marks if item is bundle or not
     *
     * @var boolean
     */
    protected $_blBundle = false;

    /**
     * Discount bundle marker - marks if item is discount bundle or not
     *
     * @var boolean
     */
    protected $_blIsDiscountArticle = false;

    /**
     * This item article
     *
     * @var Article
     */
    protected $_oArticle = null;

    /**
     * Image NON SSL url
     *
     * @var string
     */
    protected $_sDimageDirNoSsl = null;

    /**
     * Image SSL url
     *
     * @var string
     */
    protected $_sDimageDirSsl = null;

    /**
     * User chosen selectlists
     *
     * @var array
     */
    protected $_aChosenSelectlist = [];

    /**
     * Used wrapping paper ID
     *
     * @var string
     */
    protected $_sWrappingId = null;

    /**
     * Wishlist user Id
     *
     * @var string
     */
    protected $_sWishId = null;

    /**
     * Wish article ID
     *
     * @var string
     */
    protected $_sWishArticleId = null;

    /**
     * Article stock check (live db check) status
     *
     * @var bool
     */
    protected $_blCheckArticleStock = true;


    /**
     * Basket Item language Id
     *
     * @var bool
     */
    protected $_iLanguageId = null;

    /**
     * Ssl mode
     *
     * @var bool
     */
    protected $_blSsl = null;

    /**
     * Icon url
     *
     * @var string
     */
    protected $_sIconUrl = null;


    /**
     * Regular Item unit price - price without basket item discounts
     *
     * @var Price
     */
    protected $_oRegularUnitPrice = null;

    /**
     * Basket item's individual key.
     *
     * @var string
     */
    protected $basketItemKey = null;

    /**
     * Getter for basketItemKey.
     *
     * @return string | null
     */
    public function getBasketItemKey()
    {
        return $this->basketItemKey;
    }

    /**
     * Setter for basketItemKey.
     *
     * @param string $itemKey
     */
    public function setBasketItemKey($itemKey)
    {
        $this->basketItemKey = $itemKey;
    }

    /**
     * Return regular unit price
     *
     * @return Price
     */
    public function getRegularUnitPrice()
    {
        return $this->_oRegularUnitPrice;
    }

    /**
     * Set regular unit price
     *
     * @param Price $oRegularUnitPrice regular price
     */
    public function setRegularUnitPrice($oRegularUnitPrice)
    {
        $this->_oRegularUnitPrice = $oRegularUnitPrice;
    }


    /**
     * Assigns basic params to basket item
     *  - BasketItem::_setArticle();
     *  - BasketItem::setAmount();
     *  - BasketItem::_setSelectList();
     *  - BasketItem::setPersParams();
     *  - BasketItem::setBundle().
     *
     * @param string $sProductID product id
     * @param double $dAmount amount
     * @param array|null $aSel selection
     * @param array|null $aPersParam persistent params
     * @param bool|null $blBundle bundle
     *
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function init($sProductID, $dAmount, $aSel = null, $aPersParam = null, $blBundle = null)
    {
        $this->_setArticle($sProductID);
        $this->setAmount($dAmount);
        $this->_setSelectList($aSel);
        $this->setPersParams($aPersParam);
        $this->setBundle($blBundle);
        $this->setLanguageId(Registry::getLang()->getBaseLanguage());
    }

    /**
     * Initializes basket item from OrderArticle-object
     *  - basketItem::_setFromOrderArticle() - assigns $oOrderArticle parameter
     *  to BasketItem::_oArticle. Thus, oxOrderArticle is used as oxArticle (calls
     *  standard methods implemented by oxIArticle interface);
     *  - basketItem::setAmount();
     *  - basketItem::_setSelectList();
     *  - basketItem::setPersParams().
     *
     * @param OrderArticle $oOrderArticle order article to load info from
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function initFromOrderArticle($oOrderArticle)
    {
        $this->_setFromOrderArticle($oOrderArticle);
        $this->setAmount($oOrderArticle->oxorderarticles__oxamount->value);
        $this->_setSelectList($oOrderArticle->getOrderArticleSelectList());
        $this->setPersParams($oOrderArticle->getPersParams());
        $this->setBundle($oOrderArticle->isBundle());
    }

    /**
     * Marks if item is discount bundle ( BasketItem::_blIsDiscountArticle )
     *
     * @param bool $blIsDiscountArticle if item is discount bundle
     */
    public function setAsDiscountArticle($blIsDiscountArticle)
    {
        $this->_blIsDiscountArticle = $blIsDiscountArticle;
    }

    /**
     * Sets stock control mode
     *
     * @param bool $blStatus stock control mode
     */
    public function setStockCheckStatus($blStatus)
    {
        $this->_blCheckArticleStock = $blStatus;
    }

    /**
     * Returns stock control mode
     *
     * @return bool
     */
    public function getStockCheckStatus()
    {
        return $this->_blCheckArticleStock;
    }

    /**
     * Sets item amount and weight which depends on amount
     * ( BasketItem::dAmount, BasketItem::dWeight )
     *
     * @param double $dAmount amount
     * @param bool $blOverride Whether to override current amount.
     * @param null $sItemKey item key
     *
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws NoArticleException
     * @throws OutOfStockException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setAmount($dAmount, $blOverride = true, $sItemKey = null)
    {
        try {
            //validating amount
            $dAmount = Registry::getInputValidator()->validateBasketAmount($dAmount);
        } catch (ArticleInputException $oEx) {
            $oEx->setArticleNr($this->getProductId());
            $oEx->setProductId($this->getProductId());
            // setting additional information for exception and then rethrowing
            throw $oEx;
        }

        $oArticle = $this->getArticle(true);
        $dAmount = $this->applyPackageOnAmount($oArticle, $dAmount);

        // setting default
        $iOnStock = true;

        if ($blOverride) {
            $this->_dAmount = $dAmount;
        } else {
            $this->_dAmount += $dAmount;
        }

        // checking for stock
        if ($this->getStockCheckStatus()) {
            $dArtStockAmount = Registry::getSession()->getBasket()->getArtStockInBasket($oArticle->getId(), $sItemKey);
            $selectForUpdate = false;
            if (Registry::getConfig()->getConfigParam('blPsBasketReservationEnabled')) {
                $selectForUpdate = true;
            }
            $iOnStock = $oArticle->checkForStock($this->_dAmount, $dArtStockAmount, $selectForUpdate);
            if ($iOnStock !== true) {
                if ($iOnStock === false) {
                    // no stock !
                    $this->_dAmount = 0;
                } else {
                    // limited stock
                    $this->_dAmount = $iOnStock;
                }
            }
        }

        // calculating general weight
        $this->_dWeight = $oArticle->oxarticles__oxweight->value * $this->_dAmount;

        if ($iOnStock !== true) {
            /** @var OutOfStockException $oEx */
            $oEx = oxNew(OutOfStockException::class);
            $oEx->setMessage('ERROR_MESSAGE_OUTOFSTOCK_OUTOFSTOCK');
            $oEx->setArticleNr($oArticle->oxarticles__oxartnum->value);
            $oEx->setProductId($oArticle->getProductId());
            $oEx->setRemainingAmount($this->_dAmount);
            $oEx->setBasketIndex($sItemKey);
            throw $oEx;
        }
    }

    /**
     * Apply checks for package on amount
     *
     * @param Article $article
     * @param double                                      $amount
     *
     * @return double
     */
    protected function applyPackageOnAmount($article, $amount)
    {
        return $amount;
    }

    /**
     * Sets $this->_oPrice
     *
     * @param object $oPrice price
     */
    public function setPrice($oPrice)
    {
        $this->_oUnitPrice = clone $oPrice;

        $this->_oPrice = clone $oPrice;
        $this->_oPrice->multiply($this->getAmount());
    }

    /**
     * Returns article icon picture url
     *
     * @return string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function getIconUrl()
    {
        // icon url must be (re)loaded in case icon is not set or shop was switched between ssl/non-ssl
        if ($this->_sIconUrl === null || $this->_blSsl != Registry::getConfig()->isSsl()) {
            $this->_sIconUrl = $this->getArticle()->getIconUrl();
        }

        return $this->_sIconUrl;
    }

    /**
     * Retrieves the article .Throws an exception if article does not exist,
     * is not buyable or visible.
     *
     * @param bool $blCheckProduct checks if product is buyable and visible
     * @param null $sProductId product id
     * @param bool $blDisableLazyLoading disable lazy loading
     *
     * @return Article|OrderArticle
     * @throws ArticleException exception in case of no current object product id is set
     * @throws ArticleInputException exception if product is not buyable (stock and so on)
     * @throws DatabaseConnectionException
     * @throws NoArticleException exception in case if product not exists or not visible
     */
    public function getArticle($blCheckProduct = false, $sProductId = null, $blDisableLazyLoading = false)
    {
        if ($this->_oArticle === null || (!$this->_oArticle->isOrderArticle() && $blDisableLazyLoading)) {
            $sProductId = $sProductId ? $sProductId : $this->_sProductId;
            if (!$sProductId) {
                //this exception may not be caught, anyhow this is a critical exception
                /** @var ArticleException $oEx */
                $oEx = oxNew(ArticleException::class);
                $oEx->setMessage('EXCEPTION_ARTICLE_NOPRODUCTID');
                throw $oEx;
            }

            $this->_oArticle = oxNew(Article::class);
            // #M773 Do not use article lazy loading on order save
            if ($blDisableLazyLoading) {
                $this->_oArticle->modifyCacheKey('_allviews');
                $this->_oArticle->disableLazyLoading();
            }

            // performance:
            // - skipping variants loading
            // - skipping 'ab' price info
            // - load parent field
            $this->_oArticle->setNoVariantLoading(true);
            $this->_oArticle->setLoadParentData(true);
            if (!$this->_oArticle->load($sProductId)) {
                /** @var NoArticleException $oEx */
                $oEx = oxNew(NoArticleException::class);
                $oLang = Registry::getLang();
                $oEx->setMessage(sprintf($oLang->translateString('ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST', $oLang->getBaseLanguage()), $sProductId));
                $oEx->setArticleNr($sProductId);
                $oEx->setProductId($sProductId);
                throw $oEx;
            }

            // cant put not visible product to basket (M:1286)
            if ($blCheckProduct && !$this->_oArticle->isVisible()) {
                /** @var NoArticleException $oEx */
                $oEx = oxNew(NoArticleException::class);
                $oLang = Registry::getLang();
                $oEx->setMessage(sprintf($oLang->translateString('ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST', $oLang->getBaseLanguage()), $this->_oArticle->oxarticles__oxartnum->value));
                $oEx->setArticleNr($sProductId);
                $oEx->setProductId($sProductId);
                throw $oEx;
            }

            // cant put not buyable product to basket
            if ($blCheckProduct && !$this->_oArticle->isBuyable()) {
                /** @var ArticleInputException $oEx */
                $oEx = oxNew(ArticleInputException::class);
                $oEx->setMessage('ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE');
                $oEx->setArticleNr($sProductId);
                $oEx->setProductId($sProductId);
                throw $oEx;
            }
        }

        return $this->_oArticle;
    }

    /**
     * Returns bundle amount
     *
     * @return double
     */
    public function getdBundledAmount()
    {
        return $this->isBundle() ? $this->_dAmount : 0;
    }

    /**
     * Returns the price.
     *
     * @return Price
     */
    public function getPrice()
    {
        return $this->_oPrice;
    }

    /**
     * Returns the price.
     *
     * @return Price
     */
    public function getUnitPrice()
    {
        return $this->_oUnitPrice;
    }

    /**
     * Returns the amount of item.
     *
     * @return double
     */
    public function getAmount()
    {
        return $this->_dAmount;
    }

    /**
     * returns the total weight.
     *
     * @return double
     */
    public function getWeight()
    {
        return $this->_dWeight;
    }

    /**
     * Returns product title
     *
     * @return string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function getTitle()
    {
        if ($this->_sTitle === null || $this->getLanguageId() != Registry::getLang()->getBaseLanguage()) {
            $oArticle = $this->getArticle();
            $this->_sTitle = $oArticle->oxarticles__oxtitle->value;

            if ($oArticle->oxarticles__oxvarselect->value) {
                $this->_sTitle = $this->_sTitle . ', ' . $this->getVarSelect();
            }
        }

        return $this->_sTitle;
    }

    /**
     * Returns product details URL
     *
     * @return string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     * @throws DatabaseErrorException
     */
    public function getLink()
    {
        if ($this->_sLink === null || $this->getLanguageId() != Registry::getLang()->getBaseLanguage()) {
            $this->_sLink = Registry::getUtilsUrl()->cleanUrl($this->getArticle()->getLink(), ['force_sid']);
        }

        return Registry::getSession()->processUrl($this->_sLink);
    }

    /**
     * Returns ID of shop from which this product was added into basket
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->_sShopId;
    }

    /**
     * Returns user passed select list information
     *
     * @return array
     */
    public function getSelList()
    {
        return $this->_aSelList;
    }

    /**
     * Returns user chosen select list information
     *
     * @return array
     */
    public function getChosenSelList()
    {
        return $this->_aChosenSelectlist;
    }

    /**
     * Returns true if product is bundle
     *
     * @return bool
     */
    public function isBundle()
    {
        return $this->_blBundle;
    }

    /**
     * Returns true if product is given as discount
     *
     * @return bool
     */
    public function isDiscountArticle()
    {
        return $this->_blIsDiscountArticle;
    }

    /**
     * Returns true if discount must be skipped for current product
     *
     * @return bool
     */
    public function isSkipDiscount()
    {
        return $this->_blSkipDiscounts;
    }

    /**
     * Special getter function for backwards compatibility.
     * Executes methods by rule "get".$sVariableName and returns
     * result processed by executed function.
     *
     * @param string $sName parameter name
     *
     * @return Article|void
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function __get($sName)
    {
        if ($sName == 'oProduct') {
            return $this->getArticle();
        }
    }

    /**
     * Does not return _oArticle var on serialisation
     *
     * @return array
     */
    public function __sleep()
    {
        $aRet = [];
        foreach (get_object_vars($this) as $sKey => $sVar) {
            if ($sKey != '_oArticle') {
                $aRet[] = $sKey;
            }
        }

        return $aRet;
    }

    /**
     * Assigns general product parameters to BasketItem object :
     *  - sProduct    - oxarticle object ID;
     *  - title       - products title;
     *  - icon        - icon name;
     *  - link        - details URLs;
     *  - sShopId     - current shop ID;
     *  - sNativeShopId  - article shop ID;
     *  - _sDimageDirNoSsl - NON SSL mode image path;
     *  - _sDimageDirSsl   - SSL mode image path;
     *
     * @param string $sProductId product id
     *
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException exception
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setArticle" in next major
     */
    protected function _setArticle($sProductId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oConfig = Registry::getConfig();
        $oArticle = $this->getArticle(true, $sProductId);

        // product ID
        $this->_sProductId = $sProductId;

        $this->_sTitle = null;
        $this->_sVarSelect = null;
        $this->getTitle();

        // icon and details URLs
        $this->_sIcon = $oArticle->oxarticles__oxicon->value;
        $this->_sIconUrl = $oArticle->getIconUrl();
        $this->_blSsl = $oConfig->isSsl();

        // removing force_sid from the link (in case it'll change)
        $this->_sLink = Registry::getUtilsUrl()->cleanUrl($oArticle->getLink(), ['force_sid']);

        // shop Ids
        $this->_sShopId = $oConfig->getShopId();
        $this->_sNativeShopId = $oArticle->oxarticles__oxshopid->value;

        // SSL/NON SSL image paths
        $this->_sDimageDirNoSsl = $oArticle->nossl_dimagedir;
        $this->_sDimageDirSsl = $oArticle->ssl_dimagedir;
    }

    /**
     * Assigns general product parameters to BasketItem object:
     *  - sProduct    - oxarticle object ID;
     *  - title       - products title;
     *  - sShopId     - current shop ID;
     *  - sNativeShopId  - article shop ID;
     *
     * @param OrderArticle $oOrderArticle order article
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFromOrderArticle" in next major
     */
    protected function _setFromOrderArticle($oOrderArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // overriding whole article
        $this->_oArticle = $oOrderArticle;

        // product ID
        $this->_sProductId = $oOrderArticle->getProductId();

        // products title
        $this->_sTitle = $oOrderArticle->oxarticles__oxtitle->value;

        // shop Ids
        $this->_sShopId = Registry::getConfig()->getShopId();
        $this->_sNativeShopId = $oOrderArticle->oxarticles__oxshopid->value;
    }

    /**
     * Stores item select lists ( BasketItem::aSelList )
     *
     * @param array $aSelList item select lists
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     * @deprecated underscore prefix violates PSR12, will be renamed to "setSelectList" in next major
     */
    protected function _setSelectList($aSelList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // checking for default select list
        $aSelectLists = $this->getArticle()->getSelectLists();
        if (!$aSelList || is_array($aSelList) && count($aSelList) == 0) {
            if ($iSelCnt = count($aSelectLists)) {
                $aSelList = array_fill(0, $iSelCnt, '0');
            }
        }

        $this->_aSelList = $aSelList;

        //
        if (is_array($this->_aSelList) && count($this->_aSelList)) {
            foreach ($this->_aSelList as $key => $iSel) {
                $this->_aChosenSelectlist[$key] = new stdClass();
                $this->_aChosenSelectlist[$key]->name = $aSelectLists[$key]['name'];
                $this->_aChosenSelectlist[$key]->value = $aSelectLists[$key][$iSel]->name;
            }
        }
    }

    /**
     * Get persistent parameters ( BasketItem::_aPersistentParameters )
     *
     * @return array
     */
    public function getPersParams()
    {
        return $this->_aPersistentParameters;
    }

    /**
     * Stores items persistent parameters ( BasketItem::_aPersistentParameters )
     *
     * @param array $aPersParam items persistent parameters
     */
    public function setPersParams($aPersParam)
    {
        $this->_aPersistentParameters = $aPersParam;
    }

    /**
     * Marks if item is bundle ( BasketItem::blBundle )
     *
     * @param bool $blBundle if item is bundle
     */
    public function setBundle($blBundle)
    {
        $this->_blBundle = $blBundle;
    }

    /**
     * Used to set "skip discounts" status for basket item
     *
     * @param bool $blSkip set true to skip discounts
     */
    public function setSkipDiscounts($blSkip)
    {
        $this->_blSkipDiscounts = $blSkip;
    }

    /**
     * Returns product Id
     *
     * @return string product id
     */
    public function getProductId()
    {
        return $this->_sProductId;
    }

    /**
     * Product wrapping paper id setter
     *
     * @param string $sWrapId wrapping paper id
     */
    public function setWrapping($sWrapId)
    {
        $this->_sWrappingId = $sWrapId;
    }

    /**
     * Returns wrapping paper ID (if such was applied)
     *
     * @return string
     */
    public function getWrappingId()
    {
        return $this->_sWrappingId;
    }

    /**
     * Returns basket item wrapping object
     *
     * @return Wrapping
     */
    public function getWrapping()
    {
        $oWrap = null;
        if ($sWrapId = $this->getWrappingId()) {
            $oWrap = oxNew(Wrapping::class);
            $oWrap->load($sWrapId);
        }

        return $oWrap;
    }

    /**
     * Returns wishlist user Id
     *
     * @return string
     */
    public function getWishId()
    {
        return $this->_sWishId;
    }

    /**
     * Wish user id setter
     *
     * @param string $sWishId user id
     */
    public function setWishId($sWishId)
    {
        $this->_sWishId = $sWishId;
    }

    /**
     * Wish article ID setter
     *
     * @param string $sArticleId wish article id
     */
    public function setWishArticleId($sArticleId)
    {
        $this->_sWishArticleId = $sArticleId;
    }

    /**
     * Returns wish article ID
     *
     * @return string
     */
    public function getWishArticleId()
    {
        return $this->_sWishArticleId;
    }

    /**
     * Returns formatted regular unit price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice smarty formatter
     *
     * @return string
     */
    public function getFRegularUnitPrice()
    {
        return Registry::getLang()->formatCurrency($this->getRegularUnitPrice()->getPrice());
    }

    /**
     * Returns formatted unit price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice smarty formatter
     *
     * @return string
     */
    public function getFUnitPrice()
    {
        return Registry::getLang()->formatCurrency($this->getUnitPrice()->getPrice());
    }

    /**
     * Returns formatted total price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice smarty formatter
     *
     * @return string
     */
    public function getFTotalPrice()
    {
        return Registry::getLang()->formatCurrency($this->getPrice()->getPrice());
    }

    /**
     * Returns formatted total price
     *
     * @return string
     */
    public function getVatPercent()
    {
        return Registry::getLang()->formatVat($this->getPrice()->getVat());
    }

    /**
     * Returns varselect value
     *
     * @return string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws NoArticleException
     */
    public function getVarSelect()
    {
        if ($this->_sVarSelect === null || $this->getLanguageId() != Registry::getLang()->getBaseLanguage()) {
            $oArticle = $this->getArticle();
            $sVarSelectValue = $oArticle->oxarticles__oxvarselect->value;
            $this->_sVarSelect = (!empty($sVarSelectValue) || $sVarSelectValue === '0') ? $sVarSelectValue : '';
        }

        return $this->_sVarSelect;
    }

    /**
     * Get language id
     *
     * @return bool|null
     */
    public function getLanguageId()
    {
        return $this->_iLanguageId;
    }

    /**
     * Set language ID, reload basket content on language change.
     *
     * @param integer $iLanguageId language id
     * @throws ArticleException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function setLanguageId($iLanguageId)
    {
        $iOldLang = $this->_iLanguageId;
        $this->_iLanguageId = $iLanguageId;

        // #0003777: reload content on language change
        if ($iOldLang !== null && $iOldLang != $iLanguageId) {
            try {
                $this->_setArticle($this->getProductId());
            } catch (NoArticleException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            } catch (ArticleInputException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            }
        }
    }
}
