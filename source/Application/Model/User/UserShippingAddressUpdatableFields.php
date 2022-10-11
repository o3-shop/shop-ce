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

namespace OxidEsales\EshopCommunity\Application\Model\User;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Core\Contract\AbstractUpdatableFields;

/**
 * @inheritdoc
 */
class UserShippingAddressUpdatableFields extends AbstractUpdatableFields
{
    /**
     * UserShippingAddressUpdatableFields constructor.
     */
    public function __construct()
    {
        $address = oxNew(Address::class);
        $this->tableName = $address->getCoreTableName();
    }

    /**
     * Return list of fields which could be updated by shop customer.
     *
     * @return array
     */
    public function getUpdatableFields()
    {
        return [
            'OXCOMPANY',
            'OXFNAME',
            'OXLNAME',
            'OXSTREET',
            'OXSTREETNR',
            'OXADDINFO',
            'OXCITY',
            'OXCOUNTRY',
            'OXCOUNTRYID',
            'OXSTATEID',
            'OXZIP',
            'OXFON',
            'OXFAX',
            'OXSAL',
            'OXTIMESTAMP'
        ];
    }
}
