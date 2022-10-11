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
 * Class manages discount groups
 */
class DiscountGroupsAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /** If this discount id comes from request, it means that new discount should be created. */
    const NEW_DISCOUNT_ID = "-1";

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        // field , table,  visible, multilanguage, id
        'container1' => [
            ['oxtitle', 'oxgroups', 1, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 0],
            ['oxid', 'oxgroups', 0, 0, 1],
        ],
         'container2' => [
             ['oxtitle', 'oxgroups', 1, 0, 0],
             ['oxid', 'oxgroups', 0, 0, 0],
             ['oxid', 'oxobject2discount', 0, 0, 1],
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
        // active AJAX component
        $sGroupTable = $this->_getViewName('oxgroups');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sId = $oConfig->getRequestParameter('oxid');
        $sSynchId = $oConfig->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from {$sGroupTable} where 1 ";
        } else {
            $sQAdd = " from oxobject2discount, {$sGroupTable} where {$sGroupTable}.oxid=oxobject2discount.oxobjectid ";
            $sQAdd .= " and oxobject2discount.oxdiscountid = " . $oDb->quote($sId) .
                      " and oxobject2discount.oxtype = 'oxgroups' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= " and {$sGroupTable}.oxid not in ( select {$sGroupTable}.oxid " .
                      "from oxobject2discount, {$sGroupTable} where {$sGroupTable}.oxid=oxobject2discount.oxobjectid " .
                      " and oxobject2discount.oxdiscountid = " . $oDb->quote($sSynchId) .
                      " and oxobject2discount.oxtype = 'oxgroups' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes user group from discount config
     */
    public function removeDiscGroup()
    {
        $config = $this->getConfig();

        $groupIds = $this->_getActionIds('oxobject2discount.oxid');
        if ($config->getRequestParameter('all')) {
            $query = $this->_addFilter("delete oxobject2discount.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($query);
        } elseif ($groupIds && is_array($groupIds)) {
            $groupIdsQuoted = implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($groupIds));
            $query = "delete from oxobject2discount where oxobject2discount.oxid in (" . $groupIdsQuoted . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($query);
        }
    }

    /**
     * Adds user group to discount config
     */
    public function addDiscGroup()
    {
        $config = $this->getConfig();
        $groupIds = $this->_getActionIds('oxgroups.oxid');
        $discountId = $config->getRequestParameter('synchoxid');

        if ($config->getRequestParameter('all')) {
            $groupTable = $this->_getViewName('oxgroups');
            $groupIds = $this->_getAll($this->_addFilter("select $groupTable.oxid " . $this->_getQuery()));
        }
        if ($discountId && $discountId != self::NEW_DISCOUNT_ID && is_array($groupIds)) {
            foreach ($groupIds as $groupId) {
                $object2Discount = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
                $object2Discount->init('oxobject2discount');
                $object2Discount->oxobject2discount__oxdiscountid = new \OxidEsales\Eshop\Core\Field($discountId);
                $object2Discount->oxobject2discount__oxobjectid = new \OxidEsales\Eshop\Core\Field($groupId);
                $object2Discount->oxobject2discount__oxtype = new \OxidEsales\Eshop\Core\Field("oxgroups");
                $object2Discount->save();
            }
        }
    }
}
