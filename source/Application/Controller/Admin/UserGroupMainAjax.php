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

use oxRegistry;
use oxDb;
use oxField;

/**
 * Class manages users assignment to groups
 */
class UserGroupMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
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
                                     ['oxid', 'oxobject2group', 0, 0, 1],
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

        // looking for table/view
        $sUserTable = $this->_getViewName('oxuser');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sRoleId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('oxid');
        $sSynchRoleId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sRoleId) {
            $sQAdd = " from $sUserTable where 1 ";
        } else {
            $sQAdd = " from $sUserTable, oxobject2group where $sUserTable.oxid=oxobject2group.oxobjectid and ";
            $sQAdd .= " oxobject2group.oxgroupsid = " . $oDb->quote($sRoleId);
        }

        if ($sSynchRoleId && $sSynchRoleId != $sRoleId) {
            $sQAdd .= " and $sUserTable.oxid not in ( select $sUserTable.oxid from $sUserTable, oxobject2group where $sUserTable.oxid=oxobject2group.oxobjectid and ";
            $sQAdd .= " oxobject2group.oxgroupsid = " . $oDb->quote($sSynchRoleId);
            if (!$myConfig->getConfigParam('blMallUsers')) {
                $sQAdd .= " and $sUserTable.oxshopid = '" . $myConfig->getShopId() . "' ";
            }
            $sQAdd .= " ) ";
        }

        if (!$myConfig->getConfigParam('blMallUsers')) {
            $sQAdd .= " and $sUserTable.oxshopid = '" . $myConfig->getShopId() . "' ";
        }

        return $sQAdd;
    }

    /**
     * Removes User from group
     */
    public function removeUserFromUGroup()
    {
        $aRemoveGroups = $this->_getActionIds('oxobject2group.oxid');

        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2group.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sQ = "delete from oxobject2group where oxobject2group.oxid in (" . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aRemoveGroups)) . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds User to group
     */
    public function addUserToUGroup()
    {
        $aAddUsers = $this->_getActionIds('oxuser.oxid');
        $soxId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('synchoxid');

        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('all')) {
            $sUserTable = $this->_getViewName('oxuser');
            $aAddUsers = $this->_getAll($this->_addFilter("select $sUserTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aAddUsers)) {
            foreach ($aAddUsers as $sAdduser) {
                $oNewGroup = oxNew(\OxidEsales\Eshop\Application\Model\Object2Group::class);
                $oNewGroup->oxobject2group__oxobjectid = new \OxidEsales\Eshop\Core\Field($sAdduser);
                $oNewGroup->oxobject2group__oxgroupsid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oNewGroup->save();
            }
        }
    }
}
