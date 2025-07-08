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
 * Class manages delivery categories
 */
class DeliveryCategoriesAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [ 
            // field , table, visible, multilanguage, ident
            ['oxtitle', 'oxcategories', 1, 1, 0],
            ['oxdesc', 'oxcategories', 1, 1, 0],
            ['oxid', 'oxcategories', 0, 0, 0],
            ['oxid', 'oxcategories', 0, 0, 1],
        ],
        'container2' => [
            ['oxtitle', 'oxcategories', 1, 1, 0],
            ['oxdesc', 'oxcategories', 1, 1, 0],
            ['oxid', 'oxcategories', 0, 0, 0],
            ['oxid', 'oxobject2delivery', 0, 0, 1],
            ['oxid', 'oxcategories', 0, 0, 1],
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
        // looking for table/view
        $sCatTable = $this->getViewName('oxcategories');
        $oDb = DatabaseProvider::getDb();
        $sDelId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchDelId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sDelId) {
            $sQAdd = " from {$sCatTable} ";
        } else {
            $sQAdd = " from oxobject2delivery left join {$sCatTable} " .
                     "on {$sCatTable}.oxid=oxobject2delivery.oxobjectid " .
                     " where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sDelId) .
                     " and oxobject2delivery.oxtype = 'oxcategories' ";
        }

        if ($sSynchDelId && $sSynchDelId != $sDelId) {
            // performance
            $sSubSelect = " select {$sCatTable}.oxid from oxobject2delivery left join {$sCatTable} " .
                          "on {$sCatTable}.oxid=oxobject2delivery.oxobjectid " .
                          " where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sSynchDelId) .
                          " and oxobject2delivery.oxtype = 'oxcategories' ";
            if (stristr($sQAdd, 'where') === false) {
                $sQAdd .= ' where ';
            } else {
                $sQAdd .= ' and ';
            }
            $sQAdd .= " {$sCatTable}.oxid not in ( $sSubSelect ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes category from delivery configuration
     */
    public function removeCatFromDel()
    {
        $aChosenCat = $this->getActionIds('oxobject2delivery.oxid');

        // removing all
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter("delete oxobject2delivery.* " . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCat)) {
            $sChosenCategories = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenCat));
            $sQ = "delete from oxobject2delivery where oxobject2delivery.oxid in (" . $sChosenCategories . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds category to delivery configuration
     */
    public function addCatToDel()
    {
        $aChosenCat = $this->getActionIds('oxcategories.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sCatTable = $this->getViewName('oxcategories');
            $aChosenCat = $this->getAll($this->addFilter("select $sCatTable.oxid " . $this->getQuery()));
        }

        if (isset($soxId) && $soxId != "-1" && isset($aChosenCat) && $aChosenCat) {
            foreach ($aChosenCat as $sChosenCat) {
                $oObject2Delivery = oxNew(BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new Field($sChosenCat);
                $oObject2Delivery->oxobject2delivery__oxtype = new Field("oxcategories");
                $oObject2Delivery->save();
            }
        }
    }
}
