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
 * Factory class responsible for redirecting string handling functions to specific
 * string handling class. String handler basically is intended for dealing with multibyte string
 * and is NOT supposed to replace all string handling functions.
 * We use the handler for shop data and user input, but prefer not to use it for ascii strings
 * (eg. field or file names).
 */
class Str
{
    /**
     * Specific string handler
     *
     * @var object
     */
    protected static $_oHandler;

    /**
     * Class constructor. The constructor is defined in order to be possible to call parent::__construct() in modules.
     *
     * @return null;
     */
    public function __construct()
    {
    }

    /**
     * Static method initializing new string handler or returning the existing one.
     *
     * @return StrRegular|StrMb
     */
    public static function getStr()
    {
        if (!isset(self::$_oHandler)) {
            //let's init now non-static instance of oxStr to get the instance of str handler
            self::$_oHandler = oxNew(\OxidEsales\Eshop\Core\Str::class)->_getStrHandler();
        }

        return self::$_oHandler;
    }

    /**
     * Non static getter returning str handler. The sense of getStr() and _getStrHandler() is
     * to be possible to call this method statically ( \OxidEsales\Eshop\Core\Str::getStr() ), yet leaving the
     * possibility to extend it in modules by overriding _getStrHandler() method.
     *
     * @return oxStrRegular|oxStrMb
     * @deprecated underscore prefix violates PSR12, will be renamed to "getStrHandler" in next major
     */
    protected function _getStrHandler() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (function_exists('mb_strlen')) {
            return oxNew(\OxidEsales\Eshop\Core\StrMb::class);
        }

        return oxNew(\OxidEsales\Eshop\Core\StrRegular::class);
    }
}
