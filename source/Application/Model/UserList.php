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

namespace OxidEsales\EshopCommunity\Application\Model;

use oxDb;

/**
 * User list manager.
 *
 */
class UserList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxuser');
    }


    /**
     * Load searched user list with wishlist
     *
     * @param string $sSearchStr Search string
     *
     * @return null;
     */
    public function loadWishlistUsers($sSearchStr)
    {
        $sSearchStr = trim($sSearchStr);

        if (!$sSearchStr) {
            return;
        }

        $sSelect = "select oxuser.oxid, oxuser.oxfname, oxuser.oxlname from oxuser ";
        $sSelect .= "left join oxuserbaskets on oxuserbaskets.oxuserid = oxuser.oxid ";
        $sSelect .= "where oxuserbaskets.oxid is not null and oxuserbaskets.oxtitle = 'wishlist' ";
        $sSelect .= "and oxuserbaskets.oxpublic = 1 ";
        $sSelect .= "and ( oxuser.oxusername = :search or oxuser.oxlname = :search)";
        $sSelect .= "and ( select 1 from oxuserbasketitems where oxuserbasketitems.oxbasketid = oxuserbaskets.oxid limit 1)";

        $this->selectString($sSelect, [
            ':search' => "$sSearchStr"
        ]);
    }
}
