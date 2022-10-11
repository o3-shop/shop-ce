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

namespace OxidEsales\EshopCommunity\Core;

/**
 * SEPA (Single Euro Payments Area) BIC validation class
 *
 */
class SepaBICValidator
{
    /**
     * Business identifier code validation
     *
     * Structure
     *  - 4 letters: Institution Code or bank code.
     *  - 2 letters: ISO 3166-1 alpha-2 country code
     *  - 2 letters or digits: location code
     *  - 3 letters or digits: branch code, optional
     *
     * @param string $sBIC code to check
     *
     * @return bool
     */
    public function isValid($sBIC)
    {
        $sBIC = strtoupper(trim($sBIC));

        return (bool) getStr()->preg_match("(^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$)", $sBIC);
    }
}
