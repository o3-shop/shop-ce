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
use OxidEsales\Eshop\Application\Model\Attribute;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use oxDb;
use oxField;
use Exception;

/**
 * Class manages category attributes
 */
class AttributeCategoryAjax extends ListComponentAjax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,         visible, multilanguage, ident
            ['oxtitle', 'oxcategories', 1, 1, 0],
            ['oxdesc', 'oxcategories', 1, 1, 0],
            ['oxid', 'oxcategories', 0, 0, 0],
            ['oxid', 'oxcategories', 0, 0, 1]
        ],
         'container2' => [
             ['oxtitle', 'oxcategories', 1, 1, 0],
             ['oxdesc', 'oxcategories', 1, 1, 0],
             ['oxid', 'oxcategories', 0, 0, 0],
             ['oxid', 'oxcategory2attribute', 0, 0, 1],
             ['oxid', 'oxcategories', 0, 0, 1]
         ],
         'container3' => [
             ['oxtitle', 'oxattribute', 1, 1, 0],
             ['oxsort', 'oxcategory2attribute', 1, 0, 0],
             ['oxid', 'oxcategory2attribute', 0, 0, 1]
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
        $myConfig = Registry::getConfig();
        $oDb = DatabaseProvider::getDb();

        $sCatTable = $this->_getViewName('oxcategories');
        $sDiscountId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchDiscountId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // category selected or not ?
        if (!$sDiscountId) {
            $sQAdd = " from {$sCatTable} where {$sCatTable}.oxshopid = '" . $myConfig->getShopId() . "' ";
            $sQAdd .= " and {$sCatTable}.oxactive = '1' ";
        } else {
            $sQAdd = " from {$sCatTable} left join oxcategory2attribute " .
                     "on {$sCatTable}.oxid=oxcategory2attribute.oxobjectid " .
                     " where oxcategory2attribute.oxattrid = " . $oDb->quote($sDiscountId) .
                     " and {$sCatTable}.oxshopid = '" . $myConfig->getShopId() . "' " .
                     " and {$sCatTable}.oxactive = '1' ";
        }

        if ($sSynchDiscountId && $sSynchDiscountId != $sDiscountId) {
            $sQAdd .= " and {$sCatTable}.oxid not in ( select {$sCatTable}.oxid " .
                      "from {$sCatTable} left join oxcategory2attribute " .
                      "on {$sCatTable}.oxid=oxcategory2attribute.oxobjectid " .
                      " where oxcategory2attribute.oxattrid = " . $oDb->quote($sSynchDiscountId) .
                      " and {$sCatTable}.oxshopid = '" . $myConfig->getShopId() . "' " .
                      " and {$sCatTable}.oxactive = '1' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes category from Attributes list
     */
    public function removeCatFromAttr()
    {
        $aChosenCat = $this->_getActionIds('oxcategory2attribute.oxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sQ = $this->_addFilter("delete oxcategory2attribute.* " . $this->_getQuery());
            DatabaseProvider::getDb()->Execute($sQ);
        } elseif (is_array($aChosenCat)) {
            $sChosenCategories = implode(", ", DatabaseProvider::getDb()->quoteArray($aChosenCat));
            $sQ = "delete from oxcategory2attribute where oxcategory2attribute.oxid in (" . $sChosenCategories . ") ";
            DatabaseProvider::getDb()->Execute($sQ);
        }

        $this->resetContentCache();
    }

    /**
     * Adds category to Attributes list
     *
     * @throws Exception
     */
    public function addCatToAttr()
    {
        $aAddCategory = $this->_getActionIds('oxcategories.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        $oAttribute = oxNew(Attribute::class);
        // adding
        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sCatTable = $this->_getViewName('oxcategories');
            $aAddCategory = $this->_getAll($this->_addFilter("select $sCatTable.oxid " . $this->_getQuery()));
        }

        if ($oAttribute->load($soxId) && is_array($aAddCategory)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = DatabaseProvider::getMaster();
            foreach ($aAddCategory as $sAdd) {
                $oNewGroup = oxNew(BaseModel::class);
                $oNewGroup->init("oxcategory2attribute");
                $sOxSortField = 'oxcategory2attribute__oxsort';
                $sObjectIdField = 'oxcategory2attribute__oxobjectid';
                $sAttributeIdField = 'oxcategory2attribute__oxattrid';
                $sOxIdField = 'oxattribute__oxid';
                $oNewGroup->$sObjectIdField = new Field($sAdd);
                $oNewGroup->$sAttributeIdField = new Field($oAttribute->$sOxIdField->value);

                $sSql = "select max(oxsort) + 1 from oxcategory2attribute where oxobjectid = :oxobjectid";

                $oNewGroup->$sOxSortField = new Field((int) $database->getOne($sSql, [
                    ':oxobjectid' => $sAdd
                ]));
                $oNewGroup->save();
            }
        }

        $this->resetContentCache();
    }
}
