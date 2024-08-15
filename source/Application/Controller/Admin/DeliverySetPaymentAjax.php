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
use Exception;

/**
 * Class manages deliveryset payment
 */
class DeliverySetPaymentAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
            ['oxdesc', 'oxpayments', 1, 1, 0],
            ['oxaddsum', 'oxpayments', 1, 0, 0],
            ['oxaddsumtype', 'oxpayments', 0, 0, 0],
            ['oxid', 'oxpayments', 0, 0, 1],
         ],
         'container2' => [
             ['oxdesc', 'oxpayments', 1, 1, 0],
             ['oxaddsum', 'oxpayments', 1, 0, 0],
             ['oxaddsumtype', 'oxpayments', 0, 0, 0],
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
        $oDb = DatabaseProvider::getDb();
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        $sPayTable = $this->_getViewName('oxpayments');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sPayTable where 1 ";
        } else {
            $sQAdd = " from oxobject2payment, $sPayTable where oxobject2payment.oxobjectid = " . $oDb->quote($sId);
            $sQAdd .= " and oxobject2payment.oxpaymentid = $sPayTable.oxid and oxobject2payment.oxtype = 'oxdelset' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= "and $sPayTable.oxid not in ( select $sPayTable.oxid from oxobject2payment, $sPayTable where oxobject2payment.oxobjectid = " . $oDb->quote($sSynchId);
            $sQAdd .= "and oxobject2payment.oxpaymentid = $sPayTable.oxid and oxobject2payment.oxtype = 'oxdelset' ) ";
        }

        return $sQAdd;
    }

    /**
     * Remove these payments from this set
     */
    public function removePayFromSet()
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

    /**
     * Adds these payments to this set
     *
     * @throws Exception
     */
    public function addPayToSet()
    {
        $aChosenSets = $this->_getActionIds('oxpayments.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sPayTable = $this->_getViewName('oxpayments');
            $aChosenSets = $this->_getAll($this->_addFilter("select $sPayTable.oxid " . $this->_getQuery()));
        }
        if ($soxId && $soxId != "-1" && is_array($aChosenSets)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getMaster();
            foreach ($aChosenSets as $sChosenSet) {
                // check if we have this entry already in
                // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                $sID = $database->getOne("select oxid from oxobject2payment where oxpaymentid = :oxpaymentid and oxobjectid = :oxobjectid and oxtype = 'oxdelset'", [
                    ':oxpaymentid' => $sChosenSet,
                    ':oxobjectid' => $soxId
                ]);
                if (!isset($sID) || !$sID) {
                    $oObject = oxNew(BaseModel::class);
                    $oObject->init('oxobject2payment');
                    $oObject->oxobject2payment__oxpaymentid = new Field($sChosenSet);
                    $oObject->oxobject2payment__oxobjectid = new Field($soxId);
                    $oObject->oxobject2payment__oxtype = new Field("oxdelset");
                    $oObject->save();
                }
            }
        }
    }
}
