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

use OxidEsales\Eshop\Core\Contract\IUrl;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;

/**
 * Lightweight variant handler. Implements only absolutely needed oxArticle methods.
 *
 */
class SimpleVariant extends MultiLanguageModel implements IUrl
{
    /**
     * Use lazy loading for this item
     *
     * @var bool
     */
    protected $_blUseLazyLoading = true;

    /**
     * Variant price
     *
     * @var Price
     */
    protected $_oPrice = null;

    /**
     * Parent article
     *
     * @var Article
     */
    protected $_oParent = null;

    /**
     * Standard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_aStdUrls = [];

    /**
     * Standard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_aBaseStdUrls = [];

    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_aSeoUrls = [];

    /**
     * user object
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Initializes instance
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_sCacheKey = 'simplevariants';
        $this->init('oxarticles');
    }

    /**
     * Implementing (faking) performance friendly method from oxArticle
     *oxbase
     *
     * @return null
     */
    public function getSelectLists()
    {
        return null;
    }

    /**
     * Returns article user
     *
     * @return User
     */
    public function getArticleUser()
    {
        if ($this->_oUser === null) {
            $this->_oUser = $this->getUser();
        }

        return $this->_oUser;
    }

    /**
     * get user Group A, B or C price, returns db price if user is not in groups
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "getGroupPrice" in next major
     */
    protected function _getGroupPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $dPrice = $this->oxarticles__oxprice->value;
        if ($oUser = $this->getArticleUser()) {
            if ($oUser->inGroup('oxidpricea')) {
                $dPrice = $this->oxarticles__oxpricea->value;
            } elseif ($oUser->inGroup('oxidpriceb')) {
                $dPrice = $this->oxarticles__oxpriceb->value;
            } elseif ($oUser->inGroup('oxidpricec')) {
                $dPrice = $this->oxarticles__oxpricec->value;
            }
        }

        // #1437/1436C - added config option, and check for zero A,B,C price values
        if (Registry::getConfig()->getConfigParam('blOverrideZeroABCPrices') && (float) $dPrice == 0) {
            $dPrice = $this->oxarticles__oxprice->value;
        }

        return $dPrice;
    }

    /**
     * Implementing (faking) performance friendly method from oxArticle
     *
     * @return Price|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getPrice()
    {
        $myConfig = Registry::getConfig();
        // 0002030 No need to return price if it disabled for better performance.
        if (!$myConfig->getConfigParam('bl_perfLoadPrice')) {
            return;
        }

        if ($this->_oPrice === null) {
            $this->_oPrice = oxNew(Price::class);
            if (($dPrice = $this->_getGroupPrice())) {
                $dPrice = $this->modifyGroupPrice($dPrice);
                $this->_oPrice->setPrice($dPrice, $this->_dVat);

                $this->_applyParentVat($this->_oPrice);
                $this->_applyCurrency($this->_oPrice);
                // apply discounts
                $this->_applyParentDiscounts($this->_oPrice);
            } elseif (($oParent = $this->getParent())) {
                $this->_oPrice = $oParent->getPrice();
            }
        }

        return $this->_oPrice;
    }

    /**
     * Make changes to price on getting price.
     *
     * @param float $price
     * @return float
     */
    public function modifyGroupPrice($price)
    {
        return $price;
    }

    /**
     * Applies currency factor
     *
     * @param Price $oPrice Price object
     * @param object                       $oCur   Currency object
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyCurrency" in next major
     */
    protected function _applyCurrency(Price $oPrice, $oCur = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$oCur) {
            $oCur = Registry::getConfig()->getActShopCurrencyObject();
        }

        $oPrice->multiply($oCur->rate);
    }

    /**
     * Applies discounts which should be applied in general case (for 0 amount)
     *
     * @param Price $oPrice Price object
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyParentDiscounts" in next major
     */
    protected function _applyParentDiscounts($oPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($oParent = $this->getParent())) {
            $oParent->applyDiscountsForVariant($oPrice);
        }
    }

    /**
     * apply parent article VAT to given price
     *
     * @param Price $oPrice price object
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyParentVat" in next major
     */
    protected function _applyParentVat($oPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($oParent = $this->getParent()) && !Registry::getConfig()->getConfigParam('bl_perfCalcVatOnlyForBasketOrder')) {
            $oParent->applyVats($oPrice);
        }
    }

    /**
     * Price setter
     *
     * @param object $oPrice price object
     */
    public function setPrice($oPrice)
    {
        $this->_oPrice = $oPrice;
    }

    /**
     * Returns formatted product price.
     *
     * @return string|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function getFPrice()
    {
        $sPrice = null;
        if (($oPrice = $this->getPrice())) {
            $sPrice = Registry::getLang()->formatCurrency($oPrice->getBruttoPrice());
        }

        return $sPrice;
    }

    /**
     * Sets parent article
     *
     * @param Article $oParent Parent article
     */
    public function setParent($oParent)
    {
        $this->_oParent = $oParent;
    }

    /**
     * Parent article getter.
     *
     * @return Article
     */
    public function getParent()
    {
        return $this->_oParent;
    }

    /**
     * Get link type
     *
     * @return int
     */
    public function getLinkType()
    {
        $iLinkType = 0;
        if (($oParent = $this->getParent())) {
            $iLinkType = $oParent->getLinkType();
        }

        return $iLinkType;
    }

    /**
     * Checks if article is assigned to category
     *
     * @param string $sCatNid category ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function inCategory($sCatNid)
    {
        $blIn = false;
        if (($oParent = $this->getParent())) {
            $blIn = $oParent->inCategory($sCatNid);
        }

        return $blIn;
    }

    /**
     * Checks if article is assigned to price category $sCatNID
     *
     * @param string $sCatNid Price category ID
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function inPriceCategory($sCatNid)
    {
        $blIn = false;
        if (($oParent = $this->getParent())) {
            $blIn = $oParent->inPriceCategory($sCatNid);
        }

        return $blIn;
    }

    /**
     * Returns base dynamic url: shopurl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function getBaseStdLink($iLang, $blAddId = true, $blFull = true)
    {
        // possible required linktype for articles doesn't exist
        $iLinkType = null;
        if (!isset($this->_aBaseStdUrls[$iLang][$iLinkType])) {
            $oArticle = oxNew(Article::class);
            $oArticle->setId($this->getId());
            $oArticle->setLinkType($iLinkType);
            $this->_aBaseStdUrls[$iLang][$iLinkType] = $oArticle->getBaseStdLink($iLang, $blAddId, $blFull);
        }

        return $this->_aBaseStdUrls[$iLang][$iLinkType];
    }

    /**
     * Gets article link
     *
     * @param int   $iLang   required language [optional]
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function getStdLink($iLang = null, $aParams = [])
    {
        if ($iLang === null) {
            $iLang = (int) $this->getLanguage();
        }

        $iLinkType = $this->getLinkType();
        if (!isset($this->_aStdUrls[$iLang][$iLinkType])) {
            $oArticle = oxNew(Article::class);
            $oArticle->setId($this->getId());
            $oArticle->setLinkType($iLinkType);
            $this->_aStdUrls[$iLang][$iLinkType] = $oArticle->getStdLink($iLang, $aParams);
        }

        return $this->_aStdUrls[$iLang][$iLinkType];
    }

    /**
     * Returns raw recommlist seo url
     *
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBaseSeoLink($iLang)
    {
        // possible required linktype for articles doesn't exist
        $iLinkType = null;
        return Registry::get(SeoEncoderArticle::class)->getArticleUrl($this, $iLang, $iLinkType);
    }

    /**
     * Gets article link
     *
     * @param null $iLang required language id [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getLink($iLang = null)
    {
        if ($iLang === null) {
            $iLang = (int) $this->getLanguage();
        }

        if (!Registry::getUtils()->seoIsActive()) {
            return $this->getStdLink($iLang);
        }

        $iLinkType = $this->getLinkType();
        if (!isset($this->_aSeoUrls[$iLang][$iLinkType])) {
            $this->_aSeoUrls[$iLang][$iLinkType] = $this->getBaseSeoLink($iLang);
        }

        return $this->_aSeoUrls[$iLang][$iLinkType];
    }
}
