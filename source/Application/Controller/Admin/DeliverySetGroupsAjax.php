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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages deliveryset groups
 */
class DeliverySetGroupsAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [
            // field , table, visible, multilanguage, ident
            ['oxtitle', 'oxgroups', 1, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 1],
         ],
         'container2' => [
             ['oxtitle', 'oxgroups', 1, 0, 0],
             ['oxid', 'oxgroups', 0, 0, 0],
             ['oxid', 'oxobject2delivery', 0, 0, 1],
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
        return $this->getQuery();
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getQuery()
    {
        $oDb = DatabaseProvider::getDb();
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        $sgroupTable = $this->getViewName('oxgroups');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sgroupTable where 1 ";
        } else {
            $sQAdd = " from oxobject2delivery, {$sgroupTable} " .
                     'where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sId) .
                     " and oxobject2delivery.oxobjectid = {$sgroupTable}.oxid " .
                     "and oxobject2delivery.oxtype = 'oxdelsetg' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and {$sgroupTable}.oxid not in ( select {$sgroupTable}.oxid " .
                      "from oxobject2delivery, {$sgroupTable} " .
                      'where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sSynchId) .
                      " and oxobject2delivery.oxobjectid = $sgroupTable.oxid " .
                      "and oxobject2delivery.oxtype = 'oxdelsetg' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes user group from delivery sets config
     */
    public function removeGroupFromSet()
    {
        $aRemoveGroups = $this->getActionIds('oxobject2delivery.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter('delete oxobject2delivery.* ' . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sRemoveGroups = implode(', ', DatabaseProvider::getDb()->quoteArray($aRemoveGroups));
            $sQ = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . $sRemoveGroups . ') ';
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds user group to delivery sets config
     */
    public function addGroupToSet()
    {
        $aChosenCat = $this->getActionIds('oxgroups.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sGroupTable = $this->getViewName('oxgroups');
            $aChosenCat = $this->getAll($this->addFilter("select $sGroupTable.oxid " . $this->getQuery()));
        }
        if ($soxId && $soxId != '-1' && is_array($aChosenCat)) {
            foreach ($aChosenCat as $sChosenCat) {
                $oObject2Delivery = oxNew(BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new Field($sChosenCat);
                $oObject2Delivery->oxobject2delivery__oxtype = new Field('oxdelsetg');
                $oObject2Delivery->save();
            }
        }
    }
}
