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
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class controls article assignment to attributes
 */
class ShopDefaultCategoryAjax extends ListComponentAjax
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
            ['oxid', 'oxcategories', 0, 0, 1],
        ],
    ];

    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oCat = oxNew(Category::class);
        $oCat->setLanguage(Registry::getRequest()->getRequestEscapedParameter('editlanguage'));

        $sCategoriesTable = $oCat->getViewName();

        return " from $sCategoriesTable where " . $oCat->getSqlActiveSnippet();
    }

    /**
     * Removing article from cross-selling list
     */
    public function unassignCat()
    {
        $sShopId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $oShop = oxNew(Shop::class);
        if ($oShop->load($sShopId)) {
            $oShop->oxshops__oxdefcat = new Field('');
            $oShop->save();
        }
    }

    /**
     * Adding article to cross-selling list
     */
    public function assignCat()
    {
        $sChosenCat = Registry::getRequest()->getRequestEscapedParameter('oxcatid');
        $sShopId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $oShop = oxNew(Shop::class);
        if ($oShop->load($sShopId)) {
            $oShop->oxshops__oxdefcat = new Field($sChosenCat);
            $oShop->save();
        }
    }
}
