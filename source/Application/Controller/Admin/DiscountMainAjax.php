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
 * Class manages discount countries
 */
class DiscountMainAjax extends ListComponentAjax
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
            ['oxid', 'oxcountry', 0, 0, 1],
        ],
        'container2' => [
            ['oxtitle', 'oxcountry', 1, 1, 0],
            ['oxisoalpha2', 'oxcountry', 1, 0, 0],
            ['oxisoalpha3', 'oxcountry', 0, 0, 0],
            ['oxunnum3', 'oxcountry', 0, 0, 0],
            ['oxid', 'oxobject2discount', 0, 0, 1],
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
        $oRequest = Registry::getRequest();
        $sCountryTable = $this->getViewName('oxcountry');
        $oDb = DatabaseProvider::getDb();
        $sId = $oRequest->getRequestEscapedParameter('oxid');
        $sSynchId = $oRequest->getRequestEscapedParameter('synchoxid');

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
        $aChosenCntr = $this->getActionIds('oxobject2discount.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter("delete oxobject2discount.* " . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCntr)) {
            $sQ = "delete from oxobject2discount where oxobject2discount.oxid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenCntr)) . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function addDiscCountry()
    {
        $oRequest = Registry::getRequest();
        $aChosenCntr = $this->getActionIds('oxcountry.oxid');
        $soxId = $oRequest->getRequestEscapedParameter('synchoxid');

        if ($oRequest->getRequestEscapedParameter('all')) {
            $sCountryTable = $this->getViewName('oxcountry');
            $aChosenCntr = $this->getAll($this->addFilter("select $sCountryTable.oxid " . $this->getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Discount = oxNew(BaseModel::class);
                $oObject2Discount->init('oxobject2discount');
                $oObject2Discount->oxobject2discount__oxdiscountid = new Field($soxId);
                $oObject2Discount->oxobject2discount__oxobjectid = new Field($sChosenCntr);
                $oObject2Discount->oxobject2discount__oxtype = new Field("oxcountry");
                $oObject2Discount->save();
            }
        }
    }
}
