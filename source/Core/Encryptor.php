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
 * Class oxEncryptor
 */
class Encryptor
{
    /**
     * Encrypts string with given key.
     *
     * @param string $string
     * @param string $key
     *
     * @return string
     */
    public function encrypt($string, $key)
    {
        $string = "ox{$string}id";

        $key = $this->_formKey($key, $string);

        $string = $string ^ $key;
        $string = base64_encode($string);
        $string = str_replace("=", "!", $string);

        return "ox_$string";
    }

    /**
     * Forms key for use in encoding.
     *
     * @param string $key
     * @param string $string
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "formKey" in next major
     */
    protected function _formKey($key, $string) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $key = '_' . $key;
        $keyLength = (strlen($string) / strlen($key)) + 5;

        return str_repeat($key, $keyLength);
    }
}
