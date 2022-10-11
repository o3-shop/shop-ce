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

namespace OxidEsales\EshopCommunity\Core\GenericImport\ImportObject;

use Exception;

/**
 * Import object for Users.
 */
class User extends \OxidEsales\Eshop\Core\GenericImport\ImportObject\ImportObject
{
    /** @var string Database table name. */
    protected $tableName = 'oxuser';

    /** @var string Shop object name. */
    protected $shopObjectName = 'oxuser';

    /**
     * Imports user. Returns import status.
     *
     * @param array $data db row array
     *
     * @throws Exception If user exists with provided OXID, throw an exception.
     *
     * @return string $oxid on success, bool FALSE on failure
     */
    public function import($data)
    {
        if (isset($data['OXUSERNAME'])) {
            $id = $data['OXID'];
            $userName = $data['OXUSERNAME'];

            $user = oxNew(\OxidEsales\Eshop\Application\Model\User::class, "core");
            $user->oxuser__oxusername = new \OxidEsales\Eshop\Core\Field($userName, \OxidEsales\Eshop\Core\Field::T_RAW);

            if ($user->exists($id) && $id != $user->getId()) {
                throw new Exception("USER $userName already exists!");
            }
        }

        return parent::import($data);
    }
}
