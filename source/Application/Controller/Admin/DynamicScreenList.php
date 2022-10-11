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
 * Admin dynscreen list manager.
 * Arranges controll tabs and sets title.
 *
 * @subpackage dyn
 *
 * @deprecated since v5.3 (2016-05-20); Dynpages will be removed.
 */
class DynamicScreenList extends \OxidEsales\Eshop\Application\Controller\Admin\DynamicScreenController
{
    /**
     * Executes marent method parent::render() and returns mane of template
     * file "dynscreen_list.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_aViewData['menu'] = basename(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("menu"));

        return "dynscreen_list.tpl";
    }
}
