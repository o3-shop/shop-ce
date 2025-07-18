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
 * Class manages payment countries
 */
class PaymentCountryAjax extends ListComponentAjax
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
            ['oxid', 'oxobject2payment', 0, 0, 1],
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
        $sCountryTable = $this->_getViewName('oxcountry');
        $oDb = DatabaseProvider::getDb();
        $sCountryId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchCountryId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sCountryId) {
            // which fields to load ?
            $sQAdd = " from $sCountryTable where $sCountryTable.oxactive = '1' ";
        } else {
            $sQAdd = " from oxobject2payment left join $sCountryTable on $sCountryTable.oxid=oxobject2payment.oxobjectid ";
            $sQAdd .= "where $sCountryTable.oxactive = '1' and oxobject2payment.oxpaymentid = " . $oDb->quote($sCountryId) . " and oxobject2payment.oxtype = 'oxcountry' ";
        }

        if ($sSynchCountryId && $sSynchCountryId != $sCountryId) {
            $sQAdd .= "and $sCountryTable.oxid not in ( ";
            $sQAdd .= "select $sCountryTable.oxid from oxobject2payment left join $sCountryTable on $sCountryTable.oxid=oxobject2payment.oxobjectid ";
            $sQAdd .= "where oxobject2payment.oxpaymentid = " . $oDb->quote($sSynchCountryId) . " and oxobject2payment.oxtype = 'oxcountry' ) ";
        }

        return $sQAdd;
    }

    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function addPayCountry()
    {
        $aChosenCntr = $this->_getActionIds('oxcountry.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sCountryTable = $this->_getViewName('oxcountry');
            $aChosenCntr = $this->_getAll($this->_addFilter("select $sCountryTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Payment = oxNew(BaseModel::class);
                $oObject2Payment->init('oxobject2payment');
                $oObject2Payment->oxobject2payment__oxpaymentid = new Field($soxId);
                $oObject2Payment->oxobject2payment__oxobjectid = new Field($sChosenCntr);
                $oObject2Payment->oxobject2payment__oxtype = new Field("oxcountry");
                $oObject2Payment->save();
            }
        }
    }

    /**
     * Removes chosen user group (groups) from delivery list
     */
    public function removePayCountry()
    {
        $aChosenCntr = $this->_getActionIds('oxobject2payment.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete oxobject2payment.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCntr)) {
            $sQ = "delete from oxobject2payment where oxobject2payment.oxid in (" . implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenCntr)) . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }
}
