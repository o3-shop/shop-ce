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
 * Delivery list manager.
 *
 */
class DeliveryList extends ListModel
{
    /**
     * Session user ID
     *
     * @var string
     */
    protected $_sUserId = null;

    /**
     * Performance - load or not delivery list
     *
     * @var bool
     */
    protected $_blPerfLoadDelivery = null;

    /**
     * Deliveries list
     *
     * @var array
     */
    protected $_aDeliveries = [];

    /**
     * User object
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Home country info array
     *
     * @var array
     */
    protected $_sHomeCountry = null;

    /**
     * Collect fitting deliveries sets instead of fitting deliveries
     * Default is false
     *
     * @var bool
     */
    protected $_blCollectFittingDeliveriesSets = false;


    /**
     * Calls parent constructor and sets home country
     */
    public function __construct()
    {
        parent::__construct('oxdelivery');

        // load or not delivery list
        $this->setHomeCountry(Registry::getConfig()->getConfigParam('aHomeCountry'));
    }

    /**
     * Home country setter
     *
     * @param string $sHomeCountry home country id
     */
    public function setHomeCountry($sHomeCountry)
    {
        if (is_array($sHomeCountry)) {
            $this->_sHomeCountry = current($sHomeCountry);
        } else {
            $this->_sHomeCountry = $sHomeCountry;
        }
    }

    /**
     * Returns active delivery list
     *
     * Loads all active delivery in list. Additionally,
     * checks if set has user customized parameters like
     * assigned users, countries or user groups. Performs
     * additional filtering according to these parameters
     *
     * @param null $oUser session user object
     * @param null $sCountryId user country id
     * @param null $sDelSet user chosen delivery set
     *
     * @return DeliveryList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getActiveDeliveryList" in next major
     */
    protected function _getList($oUser = null, $sCountryId = null, $sDelSet = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser === null) {
            $oUser = $this->getUser();
        } else {
            //set user
            $this->setUser($oUser);
        }

        $sUserId = $oUser ? $oUser->getId() : '';

        // choosing delivery country if it is not set yet
        if (!$sCountryId) {
            if ($oUser) {
                $sCountryId = $oUser->getActiveCountry();
            } else {
                $sCountryId = $this->_sHomeCountry;
            }
        }

        if (($sUserId . $sCountryId . $sDelSet) !== $this->_sUserId) {
            $this->selectString($this->_getFilterSelect($oUser, $sCountryId, $sDelSet));
            $this->_sUserId = $sUserId . $sCountryId . $sDelSet;
        }

        $this->rewind();

        return $this;
    }

    /**
     * Creates delivery list filter SQL to load current state delivery list
     *
     * @param User $oUser session user object
     * @param string $sCountryId user country id
     * @param string $sDelSet user chosen delivery set
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterSelect" in next major
     */
    protected function _getFilterSelect($oUser, $sCountryId, $sDelSet) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();

        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxdelivery');
        $sQ = "select $sTable.* from ( select distinct $sTable.* from $sTable left join oxdel2delset on oxdel2delset.oxdelid=$sTable.oxid ";
        $sQ .= "where " . $this->getBaseObject()->getSqlActiveSnippet() . " and oxdel2delset.oxdelsetid = " . $oDb->quote($sDelSet) . " ";

        // defining initial filter parameters
        $sUserId = null;
        $aGroupIds = [];

        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser) {
            // user ID
            $sUserId = $oUser->getId();

            // user groups ( maybe would be better to fetch by function User::getUserGroups() ? )
            $aGroupIds = $oUser->getUserGroups();
        }

        $aIds = [];
        if (count($aGroupIds)) {
            foreach ($aGroupIds as $oGroup) {
                $aIds[] = $oGroup->getId();
            }
        }

        $sUserTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxuser');
        $sGroupTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxgroups');
        $sCountryTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxcountry');

        $sCountrySql = $sCountryId ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxcountry' and oxobject2delivery.OXOBJECTID=" . $oDb->quote($sCountryId) . ")" : '0';
        $sUserSql = $sUserId ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxuser' and oxobject2delivery.OXOBJECTID=" . $oDb->quote($sUserId) . ")" : '0';
        $sGroupSql = count($aIds) ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxgroups' and oxobject2delivery.OXOBJECTID in (" . implode(', ', DatabaseProvider::getDb()->quoteArray($aIds)) . ") )" : '0';

        $sQ .= " order by $sTable.oxsort asc ) as $sTable where (
                if(EXISTS(select 1 from oxobject2delivery, $sCountryTable where $sCountryTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxcountry' LIMIT 1),
                    $sCountrySql,
                    1) &&
                if(EXISTS(select 1 from oxobject2delivery, $sUserTable where $sUserTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxuser' LIMIT 1),
                    $sUserSql,
                    1) &&
                if(EXISTS(select 1 from oxobject2delivery, $sGroupTable where $sGroupTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxgroups' LIMIT 1),
                    $sGroupSql,
                    1)
            )";

        $sQ .= " order by $sTable.oxsort asc ";

        return $sQ;
    }

    /**
     * Loads and returns list of deliveries.
     *
     * Process:
     *
     *  - first checks if delivery loading is enabled in config -
     *    $myConfig->bl_perfLoadDelivery is TRUE;
     *  - loads delivery set list by calling this::GetDeliverySetList(...);
     *  - checks if there is any active (e.g. chosen delivery set in order
     *    process etc.) delivery set defined and if its set - rearranges
     *    delivery set list by storing active set at the beginning in the
     *    list.
     *  - goes through delivery sets and loads its deliveries, checks if any
     *    delivery fits. By checking calculates and stores conditional
     *    amounts:
     *
     *       oDelivery->iItemCnt - items in basket that fits this delivery
     *       oDelivery->iProdCnt - products in basket that fits this delivery
     *       oDelivery->dPrice   - price of products that fits this delivery
     *
     *  - returns a list of deliveries.
     *    NOTICE: for performance reasons deliveries is cached in
     *    $myConfig->aDeliveryList.
     *
     * @param object $oBasket basket object
     * @param User|null $oUser session user
     * @param string|null $sDelCountry user country id
     * @param string|null $sDelSet delivery set id
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getDeliveryList($oBasket, $oUser = null, $sDelCountry = null, $sDelSet = null)
    {
        // ids of deliveries that does not fit for us to skip double check
        $aSkipDeliveries = [];
        $aFittingDelSets = [];
        $this->_aDeliveries = [];

        $aDelSetList = Registry::get(DeliverySetList::class)->getDeliverySetList($oUser, $sDelCountry, $sDelSet);

        // must choose right delivery set to use its delivery list
        foreach ($aDelSetList as $sDeliverySetId => $oDeliverySet) {
            // loading delivery list to check if some of them fits
            $aDeliveries = $this->_getList($oUser, $sDelCountry, $sDeliverySetId);
            $blDelFound = false;

            foreach ($aDeliveries as $sDeliveryId => $oDelivery) {
                // skipping that was checked and didn't fit before
                if (in_array($sDeliveryId, $aSkipDeliveries)) {
                    continue;
                }

                $aSkipDeliveries[] = $sDeliveryId;

                if ($oDelivery->isForBasket($oBasket)) {
                    // delivery fits conditions
                    $this->_aDeliveries[$sDeliveryId] = $aDeliveries[$sDeliveryId];
                    $blDelFound = true;

                    // removing from unfitting list
                    array_pop($aSkipDeliveries);

                    // maybe checked "Stop processing after first match" ?
                    if ($oDelivery->oxdelivery__oxfinalize->value) {
                        break;
                    }
                }
            }

            // found delivery set and deliveries that fits
            if ($blDelFound) {
                if ($this->_blCollectFittingDeliveriesSets) {
                    // collect only deliveries sets that fits deliveries
                    $aFittingDelSets[$sDeliverySetId] = $oDeliverySet;
                } else {
                    // return collected fitting deliveries
                    Registry::getSession()->setVariable('sShipSet', $sDeliverySetId);

                    return $this->_aDeliveries;
                }
            }
        }

        //return deliveries sets if found
        if ($this->_blCollectFittingDeliveriesSets && count($aFittingDelSets)) {
            //resetting getting delivery sets list instead of deliveries before return
            $this->_blCollectFittingDeliveriesSets = false;

            //reset cache and list
            $this->setUser(null);
            $this->clear();

            return $aFittingDelSets;
        }

        // nothing what fits was found
        return [];
    }

    /**
     * Checks if deliveries in list fits for current basket and delivery set
     *
     * @param Basket $oBasket shop basket
     * @param User $oUser session user
     * @param string $sDelCountry delivery country
     * @param string $sDeliverySetId delivery set id to check its relation to delivery list
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function hasDeliveries($oBasket, $oUser, $sDelCountry, $sDeliverySetId)
    {
        $blHas = false;

        // loading delivery list to check if some of them fits
        $this->_getList($oUser, $sDelCountry, $sDeliverySetId);
        foreach ($this as $oDelivery) {
            if ($oDelivery->isForBasket($oBasket)) {
                $blHas = true;
                break;
            }
        }

        return $blHas;
    }

    /**/

    /**
     * Get current user object. If user is not set, try to get current user.
     *
     * @return User
     */
    public function getUser()
    {
        if (!$this->_oUser) {
            $this->_oUser = parent::getUser();
        }

        return $this->_oUser;
    }

    /**
     * Set current user object
     *
     * @param User|null $oUser user object
     */
    public function setUser($oUser)
    {
        $this->_oUser = $oUser;
    }

    /**
     * Force or not to collect deliveries sets instead of deliveries when
     * getting deliveries list in getDeliveryList()
     *
     * @param bool $blCollectFittingDeliveriesSets collect deliveries sets or not
     */
    public function setCollectFittingDeliveriesSets($blCollectFittingDeliveriesSets = false)
    {
        $this->_blCollectFittingDeliveriesSets = $blCollectFittingDeliveriesSets;
    }

    /**
     * Load oxDeliveryList for product
     *
     * @param object $oProduct oxArticle object
     */
    public function loadDeliveryListForProduct($oProduct)
    {
        $dPrice = $oProduct->getPrice()->getBruttoPrice();
        $dSize = $oProduct->getSize();
        $dWeight = $oProduct->getWeight();

        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxdelivery');
        $params = [];

        $sQ = "select $sTable.* from $sTable";
        $sQ .= " where " . $this->getBaseObject()->getSqlActiveSnippet();
        $sQ .= " and ($sTable.oxdeltype != 'a' || ( $sTable.oxparam <= 1 && $sTable.oxparamend >= 1))";
        if ($dPrice) {
            $sQ .= " and ($sTable.oxdeltype != 'p' || ( $sTable.oxparam <= :dprice && $sTable.oxparamend >= :dprice))";
            $params[':dprice'] = $dPrice;
        }
        if ($dSize) {
            $sQ .= " and ($sTable.oxdeltype != 's' || ( $sTable.oxparam <= :dsize && $sTable.oxparamend >= :dsize))";
            $params[':dsize'] = $dSize;
        }
        if ($dWeight) {
            $sQ .= " and ($sTable.oxdeltype != 'w' || ( $sTable.oxparam <= :dweight && $sTable.oxparamend >= :dweight))";
            $params[':dweight'] = $dWeight;
        }
        $this->selectString($sQ, $params);
    }
}
