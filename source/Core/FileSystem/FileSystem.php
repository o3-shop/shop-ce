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

namespace OxidEsales\EshopCommunity\Core\FileSystem;

/**
 * Wrapper for actions related to file system.
 *
 * @internal Do not make a module extension for this class.
 */
class FileSystem
{
    /**
     * Connect all parameters with backslash to single path.
     * Ensure that no double backslash appears if parameter already ends with backslash.
     *
     * @return string
     */
    public function combinePaths()
    {
        $pathElements = func_get_args();
        foreach ($pathElements as $key => $pathElement) {
            $pathElements[$key] = rtrim($pathElement, DIRECTORY_SEPARATOR);
        }

        return implode(DIRECTORY_SEPARATOR, $pathElements);
    }

    /**
     * Check if file exists and is readable
     *
     * @param string $filePath
     *
     * @return bool
     */
    public function isReadable($filePath)
    {
        return (is_file($filePath) && is_readable($filePath));
    }
}
