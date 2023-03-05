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

use oxAdminList;

/**
 * user list "view" class.
 */
class ListUser extends \OxidEsales\Eshop\Application\Controller\Admin\UserList
{
    /**
     * Viewable list size getter
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getViewListSize" in next version
     */
    protected function _getViewListSize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getUserDefListSize();
    }

    /**
     * Sets SQL query parameters (such as sorting),
     * executes parent method parent::Init().
     */
    public function init()
    {
        oxAdminList::init();
    }

    /**
     * Executes parent method parent::render(), passes data to Smarty engine
     * and returns name of template file "list_review.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_aViewData["menustructure"] = $this->getNavigation()->getDomXml()->documentElement->childNodes;

        return "list_user.tpl";
    }
}
