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

namespace OxidEsales\EshopCommunity\Core\Module;

use OxidEsales\Eshop\Core\Exception\ModuleValidationException   as EshopModuleValidationException;
use OxidEsales\Eshop\Core\Module\ModuleSmartyPluginDirectories  as EshopModuleSmartyPluginDirectories;

/**
 * Class ModuleSmartyPluginDirectoryValidator
 *
 * @deprecated since v6.4.0 (2019-05-24); Validation was moved to Internal\Framework\Module package and will be executed during the module activation.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 *
 */
class ModuleSmartyPluginDirectoryValidator
{
    /**
     * @param EshopModuleSmartyPluginDirectories $moduleSmartyPluginDirectories
     *
     * @throws EshopModuleValidationException
     */
    public function validate(EshopModuleSmartyPluginDirectories $moduleSmartyPluginDirectories)
    {
        $directories = $moduleSmartyPluginDirectories->getWithFullPath();

        foreach ($directories as $directory) {
            if (!$this->doesDirectoryExist($directory)) {
                throw new EshopModuleValidationException('Smarty plugin directory does not exist ' . $directory);
            }
        }
    }

    /**
     * @param string $directory
     * @return bool
     */
    private function doesDirectoryExist($directory)
    {
        return is_dir($directory);
    }
}
