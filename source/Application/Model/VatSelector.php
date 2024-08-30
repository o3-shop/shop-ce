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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class, responsible for retrieving correct vat for users and articles
 *
 */
class VatSelector extends Base
{
    /**
     * State is VAT calculation for category is set
     *
     * @var bool
     */
    protected $_blCatVatSet = null;

    /**
     * keeps loaded user Vats for later usage
     *
     * @var array
     */
    protected static $_aUserVatCache = [];

    /**
     * get VAT for user, can NOT be null
     *
     * @param User $oUser given  user object
     * @param bool $blCacheReset reset cache
     *
     * @return double | false
     */
    public function getUserVat(User $oUser, $blCacheReset = false)
    {
        $cacheId = $oUser->getId() . '_' . $oUser->oxuser__oxcountryid->value;

        if (!$blCacheReset) {
            if (
                array_key_exists($cacheId, self::$_aUserVatCache) &&
                self::$_aUserVatCache[$cacheId] !== null
            ) {
                return self::$_aUserVatCache[$cacheId];
            }
        }

        $ret = false;

        $sCountryId = $this->_getVatCountry($oUser);

        if ($sCountryId) {
            $oCountry = oxNew(Country::class);
            if (!$oCountry->load($sCountryId)) {
                throw oxNew(ObjectException::class);
            }
            if ($oCountry->isForeignCountry()) {
                $ret = $this->_getForeignCountryUserVat($oUser, $oCountry);
            }
        }

        self::$_aUserVatCache[$cacheId] = $ret;

        return $ret;
    }

    /**
     * get vat for user of a foreign country
     *
     * @param User    $oUser    given user object
     * @param Country $oCountry given country object
     *
     * @return false|int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getForeignCountryUserVat" in next major
     */
    protected function _getForeignCountryUserVat(User $oUser, Country $oCountry) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($oCountry->isInEU()) {
            if ($oUser->oxuser__oxustid->value) {
                return 0;
            }

            return false;
        }

        return 0;
    }

    /**
     * return Vat value for category type assignment only
     *
     * @param Article $oArticle given article
     *
     * @return false|string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVatForArticleCategory" in next major
     */
    protected function _getVatForArticleCategory(Article $oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sCatT = getViewName('oxcategories');

        if ($this->_blCatVatSet === null) {
            $sSelect = "SELECT oxid FROM $sCatT WHERE oxvat IS NOT NULL LIMIT 1";

            //no category specific vats in shop?
            //then for performance reasons we just return false
            $this->_blCatVatSet = (bool) $oDb->getOne($sSelect);
        }

        if (!$this->_blCatVatSet) {
            return false;
        }

        $sO2C = getViewName('oxobject2category');
        $sSql = "SELECT c.oxvat
                 FROM $sCatT AS c, $sO2C AS o2c
                 WHERE c.oxid=o2c.oxcatnid AND
                       o2c.oxobjectid = :oxobjectid AND
                       c.oxvat IS NOT NULL
                 ORDER BY o2c.oxtime ";

        $fVat = $oDb->getOne($sSql, [
            ':oxobjectid' => $oArticle->getId()
        ]);
        if ($fVat !== false && $fVat !== null) {
            return $fVat;
        }

        return false;
    }

    /**
     * get VAT for given article, can NOT be null
     *
     * @param Article $oArticle given article
     *
     * @return double
     * @throws DatabaseConnectionException
     */
    public function getArticleVat(Article $oArticle)
    {
        startProfile("_assignPriceInternal");
        // article has its own VAT ?

        if (($dArticleVat = $oArticle->getCustomVAT()) !== null) {
            stopProfile("_assignPriceInternal");

            return $dArticleVat;
        }
        if (($dArticleVat = $this->_getVatForArticleCategory($oArticle)) !== false) {
            stopProfile("_assignPriceInternal");

            return $dArticleVat;
        }

        stopProfile("_assignPriceInternal");

        return Registry::getConfig()->getConfigParam('dDefaultVAT');
    }

    /**
     * Currently returns vats percent that can be applied for basket
     * item ( executes VatSelector::getArticleVat()). Can be used to override
     * basket price calculation behaviour (Article::getBasketPrice())
     *
     * @param Article $oArticle Article object
     * @param Basket $oBasket Basket object
     *
     * @return double
     * @throws DatabaseConnectionException
     */
    public function getBasketItemVat(Article $oArticle, $oBasket)
    {
        return $this->getArticleVat($oArticle);
    }

    /**
     * get article user vat
     *
     * @param Article $oArticle article object
     *
     * @return double | false
     * @throws ObjectException
     */
    public function getArticleUserVat(Article $oArticle)
    {
        if (($oUser = $oArticle->getArticleUser())) {
            return $this->getUserVat($oUser);
        }

        return false;
    }


    /**
     * Returns country id which VAT should be applied to.
     * Depending on configuration option either user billing country or shipping country (if available) is returned.
     *
     * @param User $oUser user object
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVatCountry" in next major
     */
    protected function _getVatCountry(User $oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blUseShippingCountry = Registry::getConfig()->getConfigParam("blShippingCountryVat");

        if ($blUseShippingCountry) {
            $aAddresses = $oUser->getUserAddresses($oUser->getId());
            $sSelectedAddress = $oUser->getSelectedAddressId();

            if (isset($aAddresses[$sSelectedAddress])) {
                return $aAddresses[$sSelectedAddress]->oxaddress__oxcountryid->value;
            }
        }

        return $oUser->oxuser__oxcountryid->value;
    }
}
