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
 * Class manages discount countries
 */
class DiscountMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
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
                                     ['oxid', 'oxobject2discount', 0, 0, 1]
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
        $oConfig = $this->getConfig();
        $sCountryTable = $this->_getViewName('oxcountry');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sId = $oConfig->getRequestParameter('oxid');
        $sSynchId = $oConfig->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sCountryTable where $sCountryTable.oxactive = '1' ";
        } else {
            $sQAdd = " from oxobject2discount, $sCountryTable where $sCountryTable.oxid=oxobject2discount.oxobjectid ";
            $sQAdd .= "and oxobject2discount.oxdiscountid = " . $oDb->quote($sId) . " and oxobject2discount.oxtype = 'oxcountry' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= "and $sCountryTable.oxid not in ( select $sCountryTable.oxid from oxobject2discount, $sCountryTable where $sCountryTable.oxid=oxobject2discount.oxobjectid ";
            $sQAdd .= "and oxobject2discount.oxdiscountid = " . $oDb->quote($sSynchId) . " and oxobject2discount.oxtype = 'oxcountry' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes chosen user group (groups) from delivery list
     */
    public function removeDiscCountry()
    {
        $oConfig = $this->getConfig();
        $aChosenCntr = $this->_getActionIds('oxobject2discount.oxid');
        if ($oConfig->getRequestParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2discount.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCntr)) {
            $sQ = "delete from oxobject2discount where oxobject2discount.oxid in (" . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenCntr)) . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function addDiscCountry()
    {
        $oConfig = $this->getConfig();
        $aChosenCntr = $this->_getActionIds('oxcountry.oxid');
        $soxId = $oConfig->getRequestParameter('synchoxid');

        if ($oConfig->getRequestParameter('all')) {
            $sCountryTable = $this->_getViewName('oxcountry');
            $aChosenCntr = $this->_getAll($this->_addFilter("select $sCountryTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Discount = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $oObject2Discount->init('oxobject2discount');
                $oObject2Discount->oxobject2discount__oxdiscountid = new \OxidEsales\Eshop\Core\Field($soxId);
                $oObject2Discount->oxobject2discount__oxobjectid = new \OxidEsales\Eshop\Core\Field($sChosenCntr);
                $oObject2Discount->oxobject2discount__oxtype = new \OxidEsales\Eshop\Core\Field("oxcountry");
                $oObject2Discount->save();
            }
        }
    }
}
