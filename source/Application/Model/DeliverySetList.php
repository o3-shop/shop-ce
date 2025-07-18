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
 * DeliverySet list manager.
 *
 */
class DeliverySetList extends ListModel
{
    /**
     * Session user ID
     *
     * @var string
     */
    protected $_sUserId = null;

    /**
     * Country ID
     *
     * @var string
     */
    protected $_sCountryId = null;

    /**
     * User object
     *
     * @var User
     */
    protected $_oUser = null;

    /**
     * Home country info id
     *
     * @var array
     */
    protected $_sHomeCountry = null;

    /**
     * Calls parent constructor and sets home country
     */
    public function __construct()
    {
        $this->setHomeCountry(Registry::getConfig()->getConfigParam('aHomeCountry'));
        parent::__construct('oxdeliveryset');
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
     * Returns active delivery set list
     *
     * Loads all active delivery sets in list. Additionally,
     * checks if set has user customized parameters like
     * assigned users, countries or user groups. Performs
     * additional filtering according to these parameters
     *
     * @param null $oUser user object
     * @param null $sCountryId user country id
     *
     * @return DeliverySetList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getActiveDeliverySetList" in next major
     */
    protected function _getList($oUser = null, $sCountryId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser === null) {
            $oUser = $this->getUser();
        } else {
            //set user
            $this->setUser($oUser);
        }

        $sUserId = $oUser ? $oUser->getId() : '';

        if ($sUserId !== $this->_sUserId || $sCountryId !== $this->_sCountryId) {
            // choosing delivery country if it is not set yet
            if (!$sCountryId) {
                if ($oUser) {
                    $sCountryId = $oUser->getActiveCountry();
                } else {
                    $sCountryId = $this->_sHomeCountry;
                }
            }

            $this->selectString($this->_getFilterSelect($oUser, $sCountryId));
            $this->_sUserId = $sUserId;
            $this->_sCountryId = $sCountryId;
        }

        $this->rewind();

        return $this;
    }


    /**
     * Creates delivery set list filter SQL to load current state delivery set list
     *
     * @param User $oUser user object
     * @param string $sCountryId user country id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getFilterSelect" in next major
     */
    protected function _getFilterSelect($oUser, $sCountryId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxdeliveryset');
        $sQ = "select $sTable.* from $sTable ";
        $sQ .= "where " . $this->getBaseObject()->getSqlActiveSnippet() . ' ';

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

        $oDb = DatabaseProvider::getDb();

        $sCountrySql = $sCountryId ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelset' and oxobject2delivery.OXOBJECTID=" . $oDb->quote($sCountryId) . ")" : '0';
        $sUserSql = $sUserId ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelsetu' and oxobject2delivery.OXOBJECTID=" . $oDb->quote($sUserId) . ")" : '0';
        $sGroupSql = count($aIds) ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelsetg' and oxobject2delivery.OXOBJECTID in (" . implode(', ', DatabaseProvider::getDb()->quoteArray($aIds)) . ") )" : '0';

        $sQ .= "and (
                if(EXISTS(select 1 from oxobject2delivery, $sCountryTable where $sCountryTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelset' LIMIT 1),
                    $sCountrySql,
                    1) &&
                if(EXISTS(select 1 from oxobject2delivery, $sUserTable where $sUserTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelsetu' LIMIT 1),
                    $sUserSql,
                    1) &&
                if(EXISTS(select 1 from oxobject2delivery, $sGroupTable where $sGroupTable.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid=$sTable.OXID and oxobject2delivery.oxtype='oxdelsetg' LIMIT 1),
                    $sGroupSql,
                    1)
            )";

        //order by
        $sQ .= " order by $sTable.oxpos";

        return $sQ;
    }

    /**
     * Creates current state delivery set list
     *
     * @param User $oUser user object
     * @param string $sCountryId user country id
     * @param null $sDelSet preferred delivery set ID (optional)
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getDeliverySetList($oUser, $sCountryId, $sDelSet = null)
    {
        $this->_getList($oUser, $sCountryId);

        // if there is already chosen delivery set we must start checking from it
        $aList = $this->_aArray;
        if ($sDelSet && isset($aList[$sDelSet])) {
            //set it as first element
            $oDelSet = $aList[$sDelSet];
            unset($aList[$sDelSet]);

            $aList = array_merge([$sDelSet => $oDelSet], $aList);
        }

        return $aList;
    }

    /**
     * Loads delivery set data, checks if it has payments assigned. If active delivery set id
     * is passed - checks if it can be used, if not - takes first ship set id from list which
     * fits. For active ship set collects payment list info. Returns array containing:
     *   1. all ship sets that has payment (array)
     *   2. active ship set id (string)
     *   3. payment list for active ship set (array)
     *
     * @param string $sShipSet current ship set id (can be null if not set yet)
     * @param User $oUser active user
     * @param Basket $oBasket basket object
     *
     * @return array|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getDeliverySetData($sShipSet, $oUser, $oBasket)
    {
        $sActShipSet = null;
        $aActSets = [];
        $aActPaymentList = [];

        if (!$oUser) {
            return;
        }

        $this->_getList($oUser, $oUser->getActiveCountry());

        // if there are no shipping sets we don't need to load payments
        if ($this->count()) {
            // one selected ?
            if ($sShipSet && !isset($this->_aArray[$sShipSet])) {
                $sShipSet = null;
            }

            $oPayList = Registry::get(PaymentList::class);
            $oDelList = Registry::get(DeliveryList::class);

            $oCur = Registry::getConfig()->getActShopCurrencyObject();
            $dBasketPrice = $oBasket->getPriceForPayment() / $oCur->rate;

            // checking if these ship sets available (number of possible payment methods > 0)
            foreach ($this as $sShipSetId => $oShipSet) {
                $aPaymentList = $oPayList->getPaymentList($sShipSetId, $dBasketPrice, $oUser);
                if (count($aPaymentList)) {
                    // now checking for deliveries
                    if ($oDelList->hasDeliveries($oBasket, $oUser, $oUser->getActiveCountry(), $sShipSetId)) {
                        $aActSets[$sShipSetId] = $oShipSet;

                        if (!$sShipSet || ($sShipSetId == $sShipSet)) {
                            $sActShipSet = $sShipSet = $sShipSetId;
                            $aActPaymentList = $aPaymentList;
                            $oShipSet->blSelected = true;
                        }
                    }
                }
            }
        }

        return [$aActSets, $sActShipSet, $aActPaymentList];
    }

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
     * @param User $oUser user object
     */
    public function setUser($oUser)
    {
        $this->_oUser = $oUser;
    }

    /**
     * Loads an object including all delivery sets which are not mapped to a
     * predefined GoodRelations delivery method.
     */
    public function loadNonRDFaDeliverySetList()
    {
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxdeliveryset');
        $sSubSql = "SELECT * FROM oxobject2delivery WHERE oxobject2delivery.OXDELIVERYID = $sTable.OXID AND oxobject2delivery.OXTYPE = 'rdfadeliveryset'";
        $this->selectString("SELECT $sTable.* FROM $sTable WHERE NOT EXISTS($sSubSql) AND $sTable.OXACTIVE = 1");
    }

    /**
     * Loads delivery set mapped to a
     * predefined GoodRelations delivery method.
     *
     * @param null $sDelId delivery set id
     */
    public function loadRDFaDeliverySetList($sDelId = null)
    {
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxdeliveryset');
        if ($sDelId) {
            $sSubSql = "( select $sTable.* from $sTable left join oxdel2delset on oxdel2delset.oxdelsetid=$sTable.oxid where " . $this->getBaseObject()->getSqlActiveSnippet() . " and oxdel2delset.oxdelid = :oxdelid ) as $sTable";
        } else {
            $sSubSql = $sTable;
        }
        $sQ = "select $sTable.*, oxobject2delivery.oxobjectid from $sSubSql left join (select oxobject2delivery.* from oxobject2delivery where oxobject2delivery.oxtype = 'rdfadeliveryset' ) as oxobject2delivery on oxobject2delivery.oxdeliveryid=$sTable.oxid where " . $this->getBaseObject()->getSqlActiveSnippet() . " ";
        $this->selectString($sQ, [
            ':oxdelid' => $sDelId
        ]);
    }
}
