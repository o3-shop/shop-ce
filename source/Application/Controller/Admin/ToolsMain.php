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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;

/**
 * CVS export manager.
 * Performs export function according to user chosen categories.
 * Admin Menu: Maine Menu -> In-/Export -> Export.
 */
class ToolsMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), passes data to Smarty engine
     * and returns name of template file "imex_export.tpl".
     *
     * @return string
     */
    public function render()
    {
        if (Registry::getConfig()->isDemoShop()) {
            Registry::getUtils()->showMessageAndExit("Access denied !");
        }

        parent::render();

        $oAuthUser = oxNew(User::class);
        $oAuthUser->loadAdminUser();
        $this->_aViewData["blIsMallAdmin"] = $oAuthUser->oxuser__oxrights->value == "malladmin";

        $blShowUpdateViews = Registry::getConfig()->getConfigParam('blShowUpdateViews');
        $this->_aViewData['showViewUpdate'] = (isset($blShowUpdateViews) && !$blShowUpdateViews) ? false : true;

        return "tools_main.tpl";
    }
}
