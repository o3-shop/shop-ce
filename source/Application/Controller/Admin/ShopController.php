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

use OxidEsales\Eshop\Core\ShopIdCalculator;

/**
 * Admin shop manager.
 * Returns template, that arranges two other templates ("shop_list.tpl"
 * and "shop_main.tpl") to frame.
 * Admin Menu: Main Menu -> Core Settings.
 */
class ShopController extends \OxidEsales\Eshop\Application\Controller\Admin\AdminController
{
    const CURRENT_TEMPLATE = 'shop.tpl';

    /** @deprecated since 6.0 (2016-07-25); Instead use ShopIdCalculator::BASE_SHOP_ID */
    const SHOP_ID = ShopIdCalculator::BASE_SHOP_ID;

    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_aViewData['currentadminshop'] = static::SHOP_ID;

        return static::CURRENT_TEMPLATE;
    }
}
