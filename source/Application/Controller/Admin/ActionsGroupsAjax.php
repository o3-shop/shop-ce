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
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages promotion groups
 */
class ActionsGroupsAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        // field , table,  visible, multilanguage, ident
        'container1' => [
            ['oxtitle', 'oxgroups', 1, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 1],
        ],
         'container2' => [
             ['oxtitle', 'oxgroups', 1, 0, 0],
             ['oxid', 'oxgroups', 0, 0, 0],
             ['oxid', 'oxobject2action', 0, 0, 1],
         ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated Transitional during #107. Modules SHOULD override _getQuery()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getQuery() to the canonical override
      *             target and retires _getQuery(); until then, _getQuery() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // active AJAX component
        $sGroupTable = $this->getViewName('oxgroups');
        $oDb = DatabaseProvider::getDb();

        $sId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from {$sGroupTable} where 1 ";
        } else {
            $sQAdd = " from oxobject2action, {$sGroupTable} where {$sGroupTable}.oxid=oxobject2action.oxobjectid " .
                      ' and oxobject2action.oxactionid = ' . $oDb->quote($sId) .
                      " and oxobject2action.oxclass = 'oxgroups' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and {$sGroupTable}.oxid not in ( select {$sGroupTable}.oxid " .
                      "from oxobject2action, {$sGroupTable} where $sGroupTable.oxid=oxobject2action.oxobjectid " .
                      ' and oxobject2action.oxactionid = ' . $oDb->quote($sSynchId) .
                      " and oxobject2action.oxclass = 'oxgroups' ) ";
        }

        return $sQAdd;
    }

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @throws DatabaseConnectionException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getQuery(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getQuery() the canonical override target.
     */
    protected function getQuery()
    {
        return $this->_getQuery();
    }

    /**
     * Removes user group from promotion
     */
    public function removePromotionGroup()
    {
        $aRemoveGroups = $this->_getActionIds('oxobject2action.oxid');
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter('delete oxobject2action.* ' . $this->getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif ($aRemoveGroups && is_array($aRemoveGroups)) {
            $sRemoveGroups = implode(', ', DatabaseProvider::getDb()->quoteArray($aRemoveGroups));
            $sQ = 'delete from oxobject2action where oxobject2action.oxid in (' . $sRemoveGroups . ') ';
            DatabaseProvider::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds user group to promotion
     *
     * @return bool Whether at least one promotion was added.
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function addPromotionGroup()
    {
        $aChosenGroup = $this->_getActionIds('oxgroups.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sGroupTable = $this->getViewName('oxgroups');
            $aChosenGroup = $this->_getAll($this->_addFilter("select $sGroupTable.oxid " . $this->getQuery()));
        }

        $promotionAdded = false;
        if ($soxId && $soxId != '-1' && is_array($aChosenGroup)) {
            foreach ($aChosenGroup as $sChosenGroup) {
                $oObject2Promotion = oxNew(BaseModel::class);
                $oObject2Promotion->init('oxobject2action');
                $oObject2Promotion->oxobject2action__oxactionid = new Field($soxId);
                $oObject2Promotion->oxobject2action__oxobjectid = new Field($sChosenGroup);
                $oObject2Promotion->oxobject2action__oxclass = new Field('oxgroups');
                $oObject2Promotion->save();
            }

            $promotionAdded = true;
        }

        return $promotionAdded;
    }
}
