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
 * Forms real class name for edition based classes.
 *
 * @internal Do not make a module extension for this class.
 */
class BackwardsCompatibleClassNameProvider
{
    /** @var array */
    private $classMap;

    /**
     * @param array $classMap
     */
    public function __construct($classMap)
    {
        $this->classMap = $classMap;
    }

    /**
     * Returns real class name from given alias. If class alias is not found,
     * given class alias is thought to be a real class and is returned.
     *
     * @param string $classAlias
     *
     * @return mixed
     */
    public function getClassName($classAlias)
    {
        $className = $classAlias;
        if (array_key_exists($classAlias, $this->classMap)) {
            $className = $this->classMap[$classAlias];
        }

        return $className;
    }

    /**
     * Method returns class alias by given class name.
     *
     * @param string $className with namespace.
     *
     * @return string|null
     */
    public function getClassAliasName($className)
    {
        /*
         * Sanitize input: class names in namespaces should not, but may include a leading backslash
         */
        $className = ltrim($className, '\\');
        $classAlias = array_search($className, $this->classMap);

        if ($classAlias === false) {
            $classAlias = null;
        }

        return $classAlias;
    }
}
