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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use oxDb;
use oxField;

/**
 * Class manages delivery users
 */
class DeliveryUsersAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,  visible, multilanguage, ident
        ['oxusername', 'oxuser', 1, 0, 0],
        ['oxlname', 'oxuser', 0, 0, 0],
        ['oxfname', 'oxuser', 0, 0, 0],
        ['oxstreet', 'oxuser', 0, 0, 0],
        ['oxstreetnr', 'oxuser', 0, 0, 0],
        ['oxcity', 'oxuser', 0, 0, 0],
        ['oxzip', 'oxuser', 0, 0, 0],
        ['oxfon', 'oxuser', 0, 0, 0],
        ['oxbirthdate', 'oxuser', 0, 0, 0],
        ['oxid', 'oxuser', 0, 0, 1],
    ],
                                 'container2' => [
                                     ['oxusername', 'oxuser', 1, 0, 0],
                                     ['oxlname', 'oxuser', 0, 0, 0],
                                     ['oxfname', 'oxuser', 0, 0, 0],
                                     ['oxstreet', 'oxuser', 0, 0, 0],
                                     ['oxstreetnr', 'oxuser', 0, 0, 0],
                                     ['oxcity', 'oxuser', 0, 0, 0],
                                     ['oxzip', 'oxuser', 0, 0, 0],
                                     ['oxfon', 'oxuser', 0, 0, 0],
                                     ['oxbirthdate', 'oxuser', 0, 0, 0],
                                     ['oxid', 'oxobject2delivery', 0, 0, 1],
                                 ]
    ];

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = $this->getConfig();

        $sUserTable = $this->_getViewName('oxuser');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sId = $myConfig->getRequestParameter('oxid');
        $sSynchId = $myConfig->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sUserTable where 1 ";
            if (!$myConfig->getConfigParam('blMallUsers')) {
                $sQAdd .= " and $sUserTable.oxshopid = '" . $myConfig->getShopId() . "' ";
            }
        } elseif ($sSynchId && $sSynchId != $sId) {
            // selected group ?
            $sQAdd = " from oxobject2group left join $sUserTable on $sUserTable.oxid = oxobject2group.oxobjectid ";
            $sQAdd .= " where oxobject2group.oxgroupsid = " . $oDb->quote($sId);
            if (!$myConfig->getConfigParam('blMallUsers')) {
                $sQAdd .= " and $sUserTable.oxshopid = '" . $myConfig->getShopId() . "' ";
            }
        } else {
            $sQAdd = " from oxobject2delivery left join $sUserTable on $sUserTable.oxid=oxobject2delivery.oxobjectid ";
            $sQAdd .= " where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sId) . " and oxobject2delivery.oxtype = 'oxuser' and $sUserTable.oxid IS NOT NULL ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and $sUserTable.oxid not in ( select $sUserTable.oxid from oxobject2delivery left join $sUserTable on $sUserTable.oxid=oxobject2delivery.oxobjectid ";
            $sQAdd .= " where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sSynchId) . " and oxobject2delivery.oxtype = 'oxuser' and $sUserTable.oxid IS NOT NULL ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes user from delivery configuration
     */
    public function removeUserFromDel()
    {
        $aRemoveGroups = $this->_getActionIds('oxobject2delivery.oxid');
        if ($this->getConfig()->getRequestParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2delivery.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sQ = "delete from oxobject2delivery where oxobject2delivery.oxid in (" . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aRemoveGroups)) . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds user from delivery configuration
     */
    public function addUserToDel()
    {
        $aChosenUsr = $this->_getActionIds('oxuser.oxid');
        $soxId = $this->getConfig()->getRequestParameter('synchoxid');

        // adding
        if ($this->getConfig()->getRequestParameter('all')) {
            $sUserTable = $this->_getViewName('oxuser');
            $aChosenUsr = $this->_getAll($this->_addFilter("select $sUserTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aChosenUsr)) {
            foreach ($aChosenUsr as $sChosenUsr) {
                $oObject2Delivery = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new \OxidEsales\Eshop\Core\Field($sChosenUsr);
                $oObject2Delivery->oxobject2delivery__oxtype = new \OxidEsales\Eshop\Core\Field('oxuser');
                $oObject2Delivery->save();
            }
        }
    }
}
