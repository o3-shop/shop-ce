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
 * Class manages voucher assignment to user groups
 */
class VoucherSerieGroupsAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,  visible, multilanguage, ident
        ['oxtitle', 'oxgroups', 1, 0, 0],
        ['oxid', 'oxgroups', 0, 0, 0],
        ['oxid', 'oxgroups', 0, 0, 1],
    ],
                                 'container2' => [
                                     ['oxtitle', 'oxgroups', 1, 0, 0],
                                     ['oxid', 'oxgroups', 0, 0, 0],
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
        // looking for table/view
        $sGroupTable = $this->_getViewName('oxgroups');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $sVoucherId = $oConfig->getRequestParameter('oxid');
        $sSynchVoucherId = $oConfig->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sVoucherId) {
            $sQAdd = " from $sGroupTable where 1 ";
        } else {
            $sQAdd = " from $sGroupTable, oxobject2group where ";
            $sQAdd .= " oxobject2group.oxobjectid = " . $oDb->quote($sVoucherId) . " and $sGroupTable.oxid = oxobject2group.oxgroupsid ";
        }

        if ($sSynchVoucherId && $sSynchVoucherId != $sVoucherId) {
            $sQAdd .= " and $sGroupTable.oxid not in ( select $sGroupTable.oxid from $sGroupTable, oxobject2group where ";
            $sQAdd .= " oxobject2group.oxobjectid = " . $oDb->quote($sSynchVoucherId) . " and $sGroupTable.oxid = oxobject2group.oxgroupsid ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes selected user group(s) from Voucher serie list.
     */
    public function removeGroupFromVoucher()
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
     * Adds selected user group(s) to Voucher serie list.
     */
    public function addGroupToVoucher()
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $aChosenCat = $this->_getActionIds('oxgroups.oxid');
        $soxId = $oConfig->getRequestParameter('synchoxid');

        if ($oConfig->getRequestParameter('all')) {
            $sGroupTable = $this->_getViewName('oxgroups');
            $aChosenCat = $this->_getAll($this->_addFilter("select $sGroupTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenCat)) {
            foreach ($aChosenCat as $sChosenCat) {
                $oNewGroup = oxNew(\OxidEsales\Eshop\Application\Model\Object2Group::class);
                $oNewGroup->oxobject2group__oxobjectid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oNewGroup->oxobject2group__oxgroupsid = new \OxidEsales\Eshop\Core\Field($sChosenCat);
                $oNewGroup->save();
            }
        }
    }
}
