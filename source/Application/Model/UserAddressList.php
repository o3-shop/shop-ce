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
 * Class oxUserAddressList
 */
class UserAddressList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * Call parent class constructor
     */
    public function __construct()
    {
        parent::__construct('oxaddress');
    }

    /**
     * Selects and loads all address for particular user.
     *
     * @param string $sUserId user id
     */
    public function load($sUserId)
    {
        $sViewName = getViewName('oxcountry');
        $oBaseObject = $this->getBaseObject();
        $sSelectFields = $oBaseObject->getSelectFields();

        $sSelect = "
                SELECT {$sSelectFields}, `oxcountry`.`oxtitle` AS oxcountry
                FROM oxaddress
                LEFT JOIN {$sViewName} AS oxcountry ON oxaddress.oxcountryid = oxcountry.oxid
                WHERE oxaddress.oxuserid = :oxuserid";
        $this->selectString($sSelect, [
            ':oxuserid' => $sUserId
        ]);
    }
}
