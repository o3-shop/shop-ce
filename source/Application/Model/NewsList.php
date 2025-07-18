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
 * News list manager.
 * Creates news objects, fetches its data.
 * @deprecated 6.5.6 "News" feature will be removed completely
 */
class NewsList extends ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxnews';

    /**
     * Ref. to user object
     */
    protected $_oUser = null;

    /**
     * Loads news stored in DB, filtered by user groups, returns array, filled with
     * objects, that keeps news data.
     *
     * @param integer $iFrom  number from which start selecting
     * @param integer $iLimit Limit of records to fetch from DB(default 0)
     */
    public function loadNews($iFrom = 0, $iLimit = 10)
    {
        if ($iLimit) {
            $this->setSqlLimit($iFrom, $iLimit);
        }

        $sNewsViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxnews');
        $oBaseObject = $this->getBaseObject();
        $sSelectFields = $oBaseObject->getSelectFields();
        $params = [];

        if ($oUser = $this->getUser()) {
            // performance - only join if user is logged in
            $sSelect = "select $sSelectFields from $sNewsViewName ";
            $sSelect .= "left join oxobject2group on oxobject2group.oxobjectid=$sNewsViewName.oxid where ";
            $sSelect .= "oxobject2group.oxgroupsid in ( select oxgroupsid from oxobject2group where oxobjectid = :oxobjectid ) or ";
            $sSelect .= "( oxobject2group.oxgroupsid is null ) ";

            $params[':oxobjectid'] = $oUser->getId();
        } else {
            $sSelect = "select $sSelectFields, oxobject2group.oxgroupsid from $sNewsViewName ";
            $sSelect .= "left join oxobject2group on oxobject2group.oxobjectid=$sNewsViewName.oxid where oxobject2group.oxgroupsid is null ";
        }

        $sSelect .= " and " . $oBaseObject->getSqlActiveSnippet();
        $sSelect .= " and $sNewsViewName.oxshortdesc <> '' ";
        $sSelect .= " group by $sNewsViewName.oxid order by $sNewsViewName.oxdate desc ";

        $this->selectString($sSelect, $params);
    }

    /**
     * Returns count of all entries.
     *
     * @return integer $iRecCnt
     * @throws DatabaseConnectionException
     */
    public function getCount()
    {
        $oDb = DatabaseProvider::getDb();

        $sNewsViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxnews');
        $oBaseObject = $this->getBaseObject();
        $params = [];

        $sSelect = "select COUNT($sNewsViewName.`oxid`) from $sNewsViewName ";
        if ($oUser = $this->getUser()) {
            // performance - only join if user is logged in
            $sSelect .= "left join oxobject2group on oxobject2group.oxobjectid=$sNewsViewName.oxid where ";
            $sSelect .= "oxobject2group.oxgroupsid in ( select oxgroupsid from oxobject2group where oxobjectid = :oxobjectid ) or ";
            $sSelect .= "( oxobject2group.oxgroupsid is null ) ";

            $params[':oxobjectid'] = $oUser->getId();
        } else {
            $sSelect .= "left join oxobject2group on oxobject2group.oxobjectid=$sNewsViewName.oxid where oxobject2group.oxgroupsid is null ";
        }

        $sSelect .= " and " . $oBaseObject->getSqlActiveSnippet();

        // loading only if there is some data
        $iRecCnt = (int) $oDb->getOne($sSelect, $params);

        return $iRecCnt;
    }

    /**
     * News list user setter
     *
     * @param User $oUser user object
     */
    public function setUser($oUser)
    {
        $this->_oUser = $oUser;
    }

    /**
     * News list user getter
     *
     * @return User
     */
    public function getUser()
    {
        if ($this->_oUser == null) {
            $this->_oUser = parent::getUser();
        }

        return $this->_oUser;
    }
}
