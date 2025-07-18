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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Discount list manager.
 * Organizes list of discount objects.
 *
 */
class DiscountList extends ListModel
{
    /**
     * Discount user id
     *
     * @var string User ID
     */
    protected $_sUserId = null;

    /**
     * Forced list reload marker
     *
     * @var bool
     */
    protected $_blReload = true;


    /**
     * If any shops category has "skip discounts" status this parameter value will be true
     *
     * @var bool
     */
    protected $_hasSkipDiscountCategories = null;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct('oxdiscount');
    }

    /**
     * Initializes current state discount list
     * For iterating through the list, use getArray() on the list,
     * as iterating on object itself can cause concurrency problems.
     *
     * @param User|null $oUser user object (optional)
     *
     * @return DiscountList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDiscountList" in next major
     */
    protected function _getList($oUser = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sUserId = $oUser ? $oUser->getId() : '';

        if ($this->_blReload || $sUserId !== $this->_sUserId) {
            // loading list
            $this->selectString($this->_getFilterSelect($oUser));

            // setting list properties
            $this->_blReload = false; // reload marker
            $this->_sUserId = $sUserId; // discount list user id
        }

        // resetting array pointer
        $this->rewind();

        return $this;
    }

    /**
     * Returns user country-ID for discount selection
     *
     * @param User $oUser oxuser object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCountryId($oUser)
    {
        $sCountryId = null;
        if ($oUser) {
            $sCountryId = $oUser->getActiveCountry();
        }

        return $sCountryId;
    }

    /**
     * Used to force discount list reload
     */
    public function forceReload()
    {
        $this->_blReload = true;
    }

    /**
     * Creates discount list filter SQL to load current state discount list
     *
     * @param User $oUser user object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterSelect" in next major
     */
    protected function _getFilterSelect($oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oBaseObject = $this->getBaseObject();

        $sTable = $oBaseObject->getViewName();
        $sQ = "select " . $oBaseObject->getSelectFields() . " from $sTable ";
        $sQ .= "where " . $oBaseObject->getSqlActiveSnippet() . ' ';


        // defining initial filter parameters
        $sUserId = null;
        $sGroupIds = null;
        $sCountryId = $this->getCountryId($oUser);
        $oDb = DatabaseProvider::getDb();

        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser) {
            // user ID
            $sUserId = $oUser->getId();

            // user group ids
            foreach ($oUser->getUserGroups() as $oGroup) {
                if ($sGroupIds) {
                    $sGroupIds .= ', ';
                }
                $sGroupIds .= $oDb->quote($oGroup->getId());
            }
        }

        $sUserTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxuser');
        $sGroupTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxgroups');
        $sCountryTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxcountry');

        $sCountrySql = $sCountryId ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxcountry' and oxobject2discount.OXOBJECTID=" . $oDb->quote($sCountryId) . ")" : '0';
        $sUserSql = $sUserId ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxuser' and oxobject2discount.OXOBJECTID=" . $oDb->quote($sUserId) . ")" : '0';
        $sGroupSql = $sGroupIds ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxgroups' and oxobject2discount.OXOBJECTID in ($sGroupIds) )" : '0';

        $sQ .= "and (
                if(EXISTS(select 1 from oxobject2discount, $sCountryTable where $sCountryTable.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxcountry' LIMIT 1),
                        $sCountrySql,
                        1) &&
                if(EXISTS(select 1 from oxobject2discount, $sUserTable where $sUserTable.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxuser' LIMIT 1),
                        $sUserSql,
                        1) &&
                if(EXISTS(select 1 from oxobject2discount, $sGroupTable where $sGroupTable.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID=$sTable.OXID and oxobject2discount.oxtype='oxgroups' LIMIT 1),
                        $sGroupSql,
                        1)
            )";

        $sQ .= " order by $sTable.oxsort ";

        return $sQ;
    }

    /**
     * Returns array of discounts that can be globally (transparently) applied
     *
     * @param Article $oArticle article object
     * @param null $oUser oxuser object (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getArticleDiscounts($oArticle, $oUser = null)
    {
        $aList = [];
        $aDiscList = $this->_getList($oUser)->getArray();
        foreach ($aDiscList as $oDiscount) {
            if ($oDiscount->isForArticle($oArticle)) {
                $aList[$oDiscount->getId()] = $oDiscount;
            }
        }

        return $aList;
    }

    /**
     * Returns array of discounts that can be applied for individual basket item
     *
     * @param mixed $oArticle article object or article id (according to needs)
     * @param Basket $oBasket array of basket items containing article id, amount and price
     * @param null $oUser user object (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketItemDiscounts($oArticle, $oBasket, $oUser = null)
    {
        $aList = [];
        $aDiscList = $this->_getList($oUser)->getArray();
        /** @var Discount $oDiscount */
        foreach ($aDiscList as $oDiscount) {
            if ($oDiscount->isForBasketItem($oArticle) && $oDiscount->isForBasketAmount($oBasket)) {
                $aList[$oDiscount->getId()] = $oDiscount;
            }
        }

        return $aList;
    }

    /**
     * Returns array of discounts that can be applied for whole basket
     *
     * @param Basket $oBasket basket
     * @param null $oUser user object (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketDiscounts($oBasket, $oUser = null)
    {
        $aList = [];
        $aDiscList = $this->_getList($oUser)->getArray();
        /** @var Discount $oDiscount */
        foreach ($aDiscList as $oDiscount) {
            if ($oDiscount->isForBasket($oBasket)) {
                $aList[$oDiscount->getId()] = $oDiscount;
            }
        }

        return $aList;
    }

    /**
     * Returns array of bundle discounts that can be applied for whole basket
     *
     * @param Article $oArticle article object
     * @param Basket $oBasket basket
     * @param null $oUser user object (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketItemBundleDiscounts($oArticle, $oBasket, $oUser = null)
    {
        $aList = [];
        $aDiscList = $this->_getList($oUser)->getArray();
        /** @var Discount $oDiscount */
        foreach ($aDiscList as $oDiscount) {
            if ($oDiscount->isForBundleItem($oArticle) && $oDiscount->isForBasketAmount($oBasket)) {
                $aList[$oDiscount->getId()] = $oDiscount;
            }
        }

        return $aList;
    }

    /**
     * Returns array of basket bundle discounts
     *
     * @param Basket $oBasket Basket object
     * @param null $oUser oxuser object (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getBasketBundleDiscounts($oBasket, $oUser = null)
    {
        $aList = [];
        $aDiscList = $this->_getList($oUser)->getArray();
        /** @var Discount $oDiscount */
        foreach ($aDiscList as $oDiscount) {
            if ($oDiscount->isForBundleBasket($oBasket)) {
                $aList[$oDiscount->getId()] = $oDiscount;
            }
        }

        return $aList;
    }

    /**
     * Checks if any category has "skip discounts" status
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function hasSkipDiscountCategories()
    {
        if ($this->_hasSkipDiscountCategories === null || $this->_blReload) {
            $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxcategories');
            $sQ = "select 1 from {$sViewName} where {$sViewName}.oxactive = 1 and {$sViewName}.oxskipdiscounts = '1' ";

            $this->_hasSkipDiscountCategories = (bool) DatabaseProvider::getDb()->getOne($sQ);
        }

        return $this->_hasSkipDiscountCategories;
    }
}
