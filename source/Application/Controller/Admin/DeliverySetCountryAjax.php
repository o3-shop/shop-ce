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
 * Class manages deliveryset countries
 */
class DeliverySetCountryAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxcountry', 1, 1, 0],
        ['oxisoalpha2', 'oxcountry', 1, 0, 0],
        ['oxisoalpha3', 'oxcountry', 0, 0, 0],
        ['oxunnum3', 'oxcountry', 0, 0, 0],
        ['oxid', 'oxcountry', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxtitle', 'oxcountry', 1, 1, 0],
                                     ['oxisoalpha2', 'oxcountry', 1, 0, 0],
                                     ['oxisoalpha3', 'oxcountry', 0, 0, 0],
                                     ['oxunnum3', 'oxcountry', 0, 0, 0],
                                     ['oxid', 'oxobject2delivery', 0, 0, 1]
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
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sId = $this->getConfig()->getRequestParameter('oxid');
        $sSynchId = $this->getConfig()->getRequestParameter('synchoxid');

        $sCountryTable = $this->_getViewName('oxcountry');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from {$sCountryTable} where {$sCountryTable}.oxactive = '1' ";
        } else {
            $sQAdd = " from oxobject2delivery, {$sCountryTable} " .
                     "where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sId) .
                     " and oxobject2delivery.oxobjectid = {$sCountryTable}.oxid " .
                     "and oxobject2delivery.oxtype = 'oxdelset' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= "and {$sCountryTable}.oxid not in ( select {$sCountryTable}.oxid " .
                      "from oxobject2delivery, {$sCountryTable} " .
                      "where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sSynchId) .
                      "and oxobject2delivery.oxobjectid = {$sCountryTable}.oxid " .
                      "and oxobject2delivery.oxtype = 'oxdelset' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes chosen countries from delivery list
     */
    public function removeCountryFromSet()
    {
        $aChosenCntr = $this->_getActionIds('oxobject2delivery.oxid');
        // removing all
        if ($this->getConfig()->getRequestParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2delivery.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCntr)) {
            $sChosenCountries = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenCntr));
            $sQ = "delete from oxobject2delivery where oxobject2delivery.oxid in (" . $sChosenCountries . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds chosen countries to delivery list
     */
    public function addCountryToSet()
    {
        $aChosenCntr = $this->_getActionIds('oxcountry.oxid');
        $soxId = $this->getConfig()->getRequestParameter('synchoxid');

        // adding
        if ($this->getConfig()->getRequestParameter('all')) {
            $sCountryTable = $this->_getViewName('oxcountry');
            $aChosenCntr = $this->_getAll($this->_addFilter("select $sCountryTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Delivery = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new \OxidEsales\Eshop\Core\Field($sChosenCntr);
                $oObject2Delivery->oxobject2delivery__oxtype = new \OxidEsales\Eshop\Core\Field("oxdelset");
                $oObject2Delivery->save();
            }
        }
    }
}
