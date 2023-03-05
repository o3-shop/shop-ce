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

use oxRegistry;

/**
 * Admin article categories order manager.
 * There is possibility to change category sorting.
 * Admin Menu: Manage Products -> Categories -> Order.
 */
class CategoryOrder extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Loads article category ordering info, passes it to Smarty
     * engine and returns name of template file "category_order.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['edit'] = $oCategory = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);

        // resetting
        \OxidEsales\Eshop\Core\Registry::getSession()->setVariable('neworder_sess', null);

        $soxId = $this->getEditObjectId();

        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oCategory->load($soxId);

            //Disable editing for derived items
            if ($oCategory->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }
        }
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("aoc")) {
            $oCategoryOrderAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\CategoryOrderAjax::class);
            $this->_aViewData['oxajax'] = $oCategoryOrderAjax->getColumns();

            return "popups/category_order.tpl";
        }

        return "category_order.tpl";
    }
}
