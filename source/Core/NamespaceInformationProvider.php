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
 * Class NamespaceInformationProvider
 *
 * @package OxidEsales\EshopCommunity\Core
 *
 * @internal Do not make a module extension for this class.
 */
class NamespaceInformationProvider
{
    /**
     * Array contains names of the official O3-Shop edition namespaces.
     *
     * @var array
     */
    protected static $shopEditionNamespaces = [
        'CE' => 'OxidEsales\\EshopCommunity\\',
    ];

    /**
     * Array contains names of the official O3-Shop edition namespaces for tests.
     *
     * @var array
     */
    protected static $shopEditionTestNamespaces = [
        'CE' => 'OxidEsales\\EshopCommunity\\Tests\\',
    ];

    /**
     * O3-Shop unified namespace.
     *
     * @var string
     */
    protected static $unifiedNamespace = 'OxidEsales\\Eshop\\';

    /**
     * Getter for array with official O3-Shop Edition namespaces.
     *
     * @return array
     */
    public static function getShopEditionNamespaces()
    {
        return static::$shopEditionNamespaces;
    }

    /**
     * Getter for official O3-Shop Unified Namespace.
     *
     * @return string
     */
    public static function getUnifiedNamespace()
    {
        return static::$unifiedNamespace;
    }


    /**
     * @param string $className
     *
     * @return bool
     */
    public static function isNamespacedClass($className)
    {
        return strpos($className, '\\') !== false;
    }

    /**
     * Check if given class belongs to a shop edition namespace.
     *
     * @param string $className
     *
     * @return bool
     */
    public static function classBelongsToShopEditionNamespace($className)
    {
        return static::classBelongsToNamespace($className, static::getShopEditionNamespaces());
    }

    /**
     * Check if given class belongs to a shop edition namespace.
     *
     * @param string $className
     *
     * @return bool
     */
    public static function classBelongsToShopUnifiedNamespace($className)
    {
        $lcClassName = strtolower(ltrim($className, '\\'));
        $unifiedNamespace = static::getUnifiedNamespace();
        $belongsToUnifiedNamespace = (false !== strpos($lcClassName, strtolower($unifiedNamespace)));

        return $belongsToUnifiedNamespace;
    }

    /**
     * Check if given class belongs to one of the supplied namespaces.
     *
     * @param string $className
     * @param array  $namespaces
     *
     * @return bool
     */
    private static function classBelongsToNamespace($className, $namespaces)
    {
        $belongsToNamespace = false;
        $check = array_values($namespaces);
        $lcClassName = strtolower(ltrim($className, '\\'));

        foreach ($check as $namespace) {
            if (false !== strpos($lcClassName, strtolower($namespace))) {
                $belongsToNamespace = true;
                continue;
            }
        }
        return $belongsToNamespace;
    }
}
