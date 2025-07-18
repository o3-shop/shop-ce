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
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Promotion List manager.
 *
 */
class ActionList extends ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxactions';

    /**
     * Loads x last finished promotions
     *
     * @param int $iCount count to load
     * @throws DatabaseConnectionException
     */
    public function loadFinishedByCount($iCount)
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());

        $oDb = DatabaseProvider::getDb();
        $sQ = "select * from {$sViewName} where oxtype=2 and oxactive=1 and oxshopid='" . Registry::getConfig()->getShopId() . "' and oxactiveto>0 and oxactiveto < " . $oDb->quote($sDate) . "
               " . $this->_getUserGroupFilter() . "
               order by oxactiveto desc, oxactivefrom desc limit " . (int) $iCount;
        $this->selectString($sQ);
        $this->_aArray = array_reverse($this->_aArray, true);
    }

    /**
     * Loads last finished promotions after given timespan
     *
     * @param int $iTimespan timespan to load
     * @throws DatabaseConnectionException
     */
    public function loadFinishedByTimespan($iTimespan)
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDateTo = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $sDateFrom = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime() - $iTimespan);
        $oDb = DatabaseProvider::getDb();
        $sQ = "select * from {$sViewName} where oxtype=2 and oxactive=1 and oxshopid='" . Registry::getConfig()->getShopId() . "' and oxactiveto < " . $oDb->quote($sDateTo) . " and oxactiveto > " . $oDb->quote($sDateFrom) . "
               " . $this->_getUserGroupFilter() . "
               order by oxactiveto, oxactivefrom";
        $this->selectString($sQ);
    }

    /**
     * Loads current promotions
     */
    public function loadCurrent()
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $oDb = DatabaseProvider::getDb();
        $sQ = "select * from {$sViewName} where oxtype=2 and oxactive=1 and oxshopid='" . Registry::getConfig()->getShopId() . "' and (oxactiveto > " . $oDb->quote($sDate) . " or oxactiveto=0) and oxactivefrom != 0 and oxactivefrom < " . $oDb->quote($sDate) . "
               " . $this->_getUserGroupFilter() . "
               order by oxactiveto, oxactivefrom";
        $this->selectString($sQ);
    }

    /**
     * Loads next not yet started promotions by count
     *
     * @param int $iCount count to load
     * @throws DatabaseConnectionException
     */
    public function loadFutureByCount($iCount)
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $oDb = DatabaseProvider::getDb();
        $sQ = "select * from {$sViewName} where oxtype=2 and oxactive=1 and oxshopid='" . Registry::getConfig()->getShopId() . "' and (oxactiveto > " . $oDb->quote($sDate) . " or oxactiveto=0) and oxactivefrom > " . $oDb->quote($sDate) . "
               " . $this->_getUserGroupFilter() . "
               order by oxactiveto, oxactivefrom limit " . (int) $iCount;
        $this->selectString($sQ);
    }

    /**
     * Loads next not yet started promotions before the given timespan
     *
     * @param int $iTimespan timespan to load
     * @throws DatabaseConnectionException
     */
    public function loadFutureByTimespan($iTimespan)
    {
        $sViewName = $this->getBaseObject()->getViewName();
        $sDate = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime());
        $sDateTo = date('Y-m-d H:i:s', Registry::getUtilsDate()->getTime() + $iTimespan);
        $oDb = DatabaseProvider::getDb();
        $sQ = "select * from {$sViewName} where oxtype=2 and oxactive=1 and oxshopid='" . Registry::getConfig()->getShopId() . "' and (oxactiveto > " . $oDb->quote($sDate) . " or oxactiveto=0) and oxactivefrom > " . $oDb->quote($sDate) . " and oxactivefrom < " . $oDb->quote($sDateTo) . "
               " . $this->_getUserGroupFilter() . "
               order by oxactiveto, oxactivefrom";
        $this->selectString($sQ);
    }

    /**
     * Returns part of user group filter query
     *
     * @param null $oUser user object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getUserGroupFilter" in next major
     */
    protected function _getUserGroupFilter($oUser = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oUser = ($oUser == null) ? $this->getUser() : $oUser;
        $sTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxactions');
        $sGroupTable = Registry::get(TableViewNameGenerator::class)->getViewName('oxgroups');

        $aIds = [];
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($oUser && count($aGroupIds = $oUser->getUserGroups())) {
            foreach ($aGroupIds as $oGroup) {
                $aIds[] = $oGroup->getId();
            }
        }

        $sGroupSql = count($aIds) ? "EXISTS(select oxobject2action.oxid from oxobject2action where oxobject2action.oxactionid=$sTable.OXID and oxobject2action.oxclass='oxgroups' and oxobject2action.OXOBJECTID in (" . implode(', ', DatabaseProvider::getDb()->quoteArray($aIds)) . ") )" : '0';
        return " and (
                if(EXISTS(select 1 from oxobject2action, $sGroupTable where $sGroupTable.oxid=oxobject2action.oxobjectid and oxobject2action.oxactionid=$sTable.OXID and oxobject2action.oxclass='oxgroups' LIMIT 1),
                    $sGroupSql,
                    1)
            ) ";
    }

    /**
     * return true if there are any active promotions
     *
     * @return boolean
     * @throws DatabaseConnectionException
     */
    public function areAnyActivePromotions()
    {
        return (bool) $this->fetchExistsActivePromotion();
    }


    /**
     * Fetch the information, if there is an active promotion.
     *
     * @return string One, if there is an active promotion.
     * @throws DatabaseConnectionException
     */
    protected function fetchExistsActivePromotion()
    {
        $query = "select 1 from " . Registry::get(TableViewNameGenerator::class)->getViewName('oxactions') . " 
            where oxtype = :oxtype and oxactive = :oxactive and oxshopid = :oxshopid 
            limit 1";

        return DatabaseProvider::getDb()->getOne($query, [
            ':oxtype' => 2,
            ':oxactive' => 1,
            ':oxshopid' => Registry::getConfig()->getShopId()
        ]);
    }

    /**
     * load active shop banner list
     */
    public function loadBanners()
    {
        $oBaseObject = $this->getBaseObject();
        $oViewName = $oBaseObject->getViewName();
        $sQ = "select * from {$oViewName} where oxtype=3 and " . $oBaseObject->getSqlActiveSnippet()
              . " and oxshopid='" . Registry::getConfig()->getShopId() . "' " . $this->_getUserGroupFilter()
              . " order by oxsort";
        $this->selectString($sQ);
    }
}
