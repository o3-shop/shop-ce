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
 * Class manages delivery countries
 */
class DeliveryMainAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [
            // field , table, visible, multilanguage, ident
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
             ['oxid', 'oxobject2delivery', 0, 0, 1],
         ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Use getQuery() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _getQuery().
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sCountryTable = $this->getViewName('oxcountry');
        $oDb = DatabaseProvider::getDb();
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from {$sCountryTable} where {$sCountryTable}.oxactive = '1' ";
        } else {
            $sQAdd = " from oxobject2delivery left join {$sCountryTable} " .
                     "on {$sCountryTable}.oxid=oxobject2delivery.oxobjectid " .
                     ' where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sId) .
                     " and oxobject2delivery.oxtype = 'oxcountry' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and {$sCountryTable}.oxid not in ( select {$sCountryTable}.oxid " .
                      "from oxobject2delivery left join {$sCountryTable} " .
                      "on {$sCountryTable}.oxid=oxobject2delivery.oxobjectid " .
                      ' where oxobject2delivery.oxdeliveryid = ' . $oDb->quote($sSynchId) .
                      " and oxobject2delivery.oxtype = 'oxcountry' ) ";
        }

        return $sQAdd;
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal If your override does not fully replace the behavior, call parent::getQuery()
     *           (not the deprecated _getQuery()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getQuery()
    {
        return $this->_getQuery();
    }

    /**
     * Removes chosen countries from delivery list
     */
    public function removeCountryFromDel()
    {
        $aChosenCntr = $this->getActionIds('oxobject2delivery.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter('delete oxobject2delivery.* ' . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCntr)) {
            $sQ = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . implode(', ', DatabaseProvider::getDb()->quoteArray($aChosenCntr)) . ') ';
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds chosen countries to delivery list
     */
    public function addCountryToDel()
    {
        $aChosenCntr = $this->getActionIds('oxcountry.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sCountryTable = $this->getViewName('oxcountry');
            $aChosenCntr = $this->getAll($this->addFilter("select $sCountryTable.oxid " . $this->getQuery()));
        }

        if ($soxId && $soxId != '-1' && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Delivery = oxNew(BaseModel::class);
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new Field($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new Field($sChosenCntr);
                $oObject2Delivery->oxobject2delivery__oxtype = new Field('oxcountry');
                $oObject2Delivery->save();
            }
        }
    }
}
