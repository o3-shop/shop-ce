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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages deliveryset and delivery configuration
 */
class DeliverySetMainAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        'container1' => [
            // field, table, visible, multilanguage, ident
            ['oxtitle', 'oxdelivery', 1, 1, 0],
            ['oxaddsum', 'oxdelivery', 1, 0, 0],
            ['oxaddsumtype', 'oxdelivery', 1, 0, 0],
            ['oxid', 'oxdelivery', 0, 0, 1],
         ],
         'container2' => [
             ['oxtitle', 'oxdelivery', 1, 1, 0],
             ['oxaddsum', 'oxdelivery', 1, 0, 0],
             ['oxaddsumtype', 'oxdelivery', 1, 0, 0],
             ['oxid', 'oxdel2delset', 0, 0, 1],
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
        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');
        $oDb = DatabaseProvider::getDb();

        $sDeliveryViewName = $this->getViewName('oxdelivery');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from $sDeliveryViewName where 1 ";
        } else {
            $sQAdd = " from $sDeliveryViewName left join oxdel2delset on oxdel2delset.oxdelid=$sDeliveryViewName.oxid ";
            $sQAdd .= 'where oxdel2delset.oxdelsetid = ' . $oDb->quote($sId);
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= "and $sDeliveryViewName.oxid not in ( select $sDeliveryViewName.oxid from $sDeliveryViewName left join oxdel2delset on oxdel2delset.oxdelid=$sDeliveryViewName.oxid ";
            $sQAdd .= 'where oxdel2delset.oxdelsetid = ' . $oDb->quote($sSynchId) . ' ) ';
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
     * Remove this delivery cost from these sets
     */
    public function removeFromSet()
    {
        $aRemoveGroups = $this->getActionIds('oxdel2delset.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->addFilter('delete oxdel2delset.* ' . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sQ = 'delete from oxdel2delset where oxdel2delset.oxid in (' . implode(', ', DatabaseProvider::getDb()->quoteArray($aRemoveGroups)) . ') ';
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds this delivery cost to these sets
     *
     * @throws Exception
     */
    public function addToSet()
    {
        $aChosenSets = $this->getActionIds('oxdelivery.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sDeliveryViewName = $this->getViewName('oxdelivery');
            $aChosenSets = $this->getAll($this->addFilter("select $sDeliveryViewName.oxid " . $this->getQuery()));
        }
        if ($soxId && $soxId != '-1' && is_array($aChosenSets)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getMaster();
            foreach ($aChosenSets as $sChosenSet) {
                // check if we have this entry already in
                // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                $sID = $database->getOne('select oxid from oxdel2delset where oxdelid = :oxdelid and oxdelsetid = :oxdelsetid', [
                    ':oxdelid' => $sChosenSet,
                    ':oxdelsetid' => $soxId,
                ]);
                if (!isset($sID) || !$sID) {
                    $oDel2delset = oxNew(BaseModel::class);
                    $oDel2delset->init('oxdel2delset');
                    $oDel2delset->oxdel2delset__oxdelid = new Field($sChosenSet);
                    $oDel2delset->oxdel2delset__oxdelsetid = new Field($soxId);
                    $oDel2delset->save();
                }
            }
        }
    }
}
