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

use oxRegistry;
use oxPrice;

/**
 * Lightweight variant handler. Implemnets only absolutely needed oxArticle methods.
 *
 */
class SimpleVariant extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel implements \OxidEsales\Eshop\Core\Contract\IUrl
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
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_oPrice = null;

    /**
     * Parent article
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_oParent = null;

    /**
     * Stardard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_aStdUrls = [];

    /**
     * Stardard/dynamic article urls for languages
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
     * @var oxUser
     */
    protected $_oUser = null;

    /**
     * Initializes instance
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_sCacheKey = "simplevariants";
        $this->init('oxarticles');
    }

    /**
     * Implementing (fakeing) performance friendly method from oxArticle
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
     * @return oxUser
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
        if ($this->getConfig()->getConfigParam('blOverrideZeroABCPrices') && (double) $dPrice == 0) {
            $dPrice = $this->oxarticles__oxprice->value;
        }

        return $dPrice;
    }

    /**
     * Implementing (faking) performance friendly method from oxArticle
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function getPrice()
    {
        $myConfig = $this->getConfig();
        // 0002030 No need to return price if it disabled for better performance.
        if (!$myConfig->getConfigParam('bl_perfLoadPrice')) {
            return;
        }

        if ($this->_oPrice === null) {
            $this->_oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
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
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     * @param object                       $oCur   Currency object
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyCurrency" in next major
     */
    protected function _applyCurrency(\OxidEsales\Eshop\Core\Price $oPrice, $oCur = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$oCur) {
            $oCur = $this->getConfig()->getActShopCurrencyObject();
        }

        $oPrice->multiply($oCur->rate);
    }

    /**
     * Applies discounts which should be applied in general case (for 0 amount)
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
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
     * @param \OxidEsales\Eshop\Core\Price $oPrice price object
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyParentVat" in next major
     */
    protected function _applyParentVat($oPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (($oParent = $this->getParent()) && !$this->getConfig()->getConfigParam('bl_perfCalcVatOnlyForBasketOrder')) {
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
     * Returns formated product price.
     *
     * @return double
     */
    public function getFPrice()
    {
        $sPrice = null;
        if (($oPrice = $this->getPrice())) {
            $sPrice = \OxidEsales\Eshop\Core\Registry::getLang()->formatCurrency($oPrice->getBruttoPrice());
        }

        return $sPrice;
    }

    /**
     * Sets parent article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oParent Parent article
     */
    public function setParent($oParent)
    {
        $this->_oParent = $oParent;
    }

    /**
     * Parent article getter.
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
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
            $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
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
            $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
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
     */
    public function getBaseSeoLink($iLang)
    {
        // possible required linktype for articles doesn't exist
        $iLinkType = null;
        return \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Application\Model\SeoEncoderArticle::class)->getArticleUrl($this, $iLang, $iLinkType);
    }

    /**
     * Gets article link
     *
     * @param int $iLang required language id [optional]
     *
     * @return string
     */
    public function getLink($iLang = null)
    {
        if ($iLang === null) {
            $iLang = (int) $this->getLanguage();
        }

        if (!\OxidEsales\Eshop\Core\Registry::getUtils()->seoIsActive()) {
            return $this->getStdLink($iLang);
        }

        $iLinkType = $this->getLinkType();
        if (!isset($this->_aSeoUrls[$iLang][$iLinkType])) {
            $this->_aSeoUrls[$iLang][$iLinkType] = $this->getBaseSeoLink($iLang);
        }

        return $this->_aSeoUrls[$iLang][$iLinkType];
    }
}
