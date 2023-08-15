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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Object2Role;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use oxRegistry;
use oxDb;
use oxField;

/**
 * Class manages users assignment to groups
 */
class AdminRightsMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
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
         ['oxid', 'o3object2role', 0, 0, 1],
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
        $myConfig = Registry::getConfig();

        // looking for table/view
        $sUserTable = (oxNew(User::class))->getViewName();
        $oDb = DatabaseProvider::getDb();
        $sRoleId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchRoleId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sRoleId) {
            $sQAdd = " from $sUserTable where 1 ";
        } else {
            $sQAdd = " from $sUserTable, o3object2role where $sUserTable.oxid=o3object2role.objectid and ";
            $sQAdd .= " o3object2role.roleid = " . $oDb->quote($sRoleId);
        }

        if ($sSynchRoleId && $sSynchRoleId != $sRoleId) {
            $sQAdd .= " and $sUserTable.oxid not in ( select $sUserTable.oxid from $sUserTable, o3object2role where $sUserTable.oxid=o3object2role.objectid and ";
            $sQAdd .= " o3object2role.roleid = " . $oDb->quote($sSynchRoleId);
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
    public function removeuserfromrole()
    {
        $aRemoveRoles = $this->_getActionIds('o3object2role.oxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete o3object2role.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveRoles && is_array($aRemoveRoles)) {
            $sQ = "delete from o3object2role where o3object2role.oxid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aRemoveRoles)) . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds User to group
     */
    public function addusertorole()
    {
        $aAddUsers = $this->_getActionIds('oxuser.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sUserTable = (oxNew(User::class))->getViewName();
            $aAddUsers = $this->_getAll($this->_addFilter("select $sUserTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aAddUsers)) {
            foreach ($aAddUsers as $sAdduser) {
                $newAssignments = oxNew(Object2Role::class);
                $newAssignments->assign([
                    'objectid'  => $sAdduser,
                    'roleid'    => $soxId
                ]);
                $newAssignments->save();
            }
        }
    }
}
