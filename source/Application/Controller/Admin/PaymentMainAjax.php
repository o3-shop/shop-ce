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

use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Application\Model\Object2Group;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages payment user groups
 */
class PaymentMainAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        // field , table,  visible, multilanguage, id
        'container1' => [
            ['oxtitle', 'oxgroups', 1, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 1],
        ],
        'container2' => [
            ['oxtitle', 'oxgroups', 1, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 0],
            ['oxid', 'oxobject2group', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // looking for table/view
        $sGroupTable = $this->_getViewName('oxgroups');
        $sGroupId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchGroupId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $oDb = DatabaseProvider::getDb();

        // category selected or not ?
        if (!$sGroupId) {
            $sQAdd = " from {$sGroupTable} ";
        } else {
            $sQAdd = " from {$sGroupTable}, oxobject2group where ";
            $sQAdd .= " oxobject2group.oxobjectid = " . $oDb->quote($sGroupId) .
                      " and oxobject2group.oxgroupsid = {$sGroupTable}.oxid ";
        }

        if (!$sSynchGroupId) {
            $sSynchGroupId = Registry::getRequest()->getRequestEscapedParameter('oxajax_synchfid');
        }
        if ($sSynchGroupId && $sSynchGroupId != $sGroupId) {
            if (!$sGroupId) {
                $sQAdd .= 'where ';
            } else {
                $sQAdd .= 'and ';
            }
            $sQAdd .= " {$sGroupTable}.oxid not in ( select {$sGroupTable}.oxid from {$sGroupTable}, oxobject2group " .
                      "where  oxobject2group.oxobjectid = " . $oDb->quote($sSynchGroupId) .
                      " and oxobject2group.oxgroupsid = $sGroupTable.oxid ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes group of users that may pay using selected method(s).
     */
    public function removePayGroup()
    {
        $aRemoveGroups = $this->_getActionIds('oxobject2group.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2group.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sRemoveGroups = implode(", ", DatabaseProvider::getDb()->quoteArray($aRemoveGroups));
            $sQ = "delete from oxobject2group where oxobject2group.oxid in (" . $sRemoveGroups . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds group of users that may pay using selected method(s).
     */
    public function addPayGroup()
    {
        $aAddGroups = $this->_getActionIds('oxgroups.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sGroupTable = $this->_getViewName('oxgroups');
            $aAddGroups = $this->_getAll($this->_addFilter("select $sGroupTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aAddGroups)) {
            foreach ($aAddGroups as $sAddgroup) {
                $oNewGroup = oxNew(Object2Group::class);
                $oNewGroup->oxobject2group__oxobjectid = new Field($soxId);
                $oNewGroup->oxobject2group__oxgroupsid = new Field($sAddgroup);
                $oNewGroup->save();
            }
        }
    }
}
