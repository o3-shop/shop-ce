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

use OxidEsales\Eshop\Core\Module\Module as EshopModule;

/**
 * Class ModuleSmartyPluginDirectories
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 *
 */
class ModuleSmartyPluginDirectories
{

    /**
     * @var EshopModule
     *
     * Needed to get the absolute path to a module directory
     */
    private $module = null;

    /**
     * @var array
     */
    private $moduleSmartyPluginDirectories = [];

    /**
     * SmartyPluginDirectoryBridge constructor.
     *
     * @param EshopModule $module
     */
    public function __construct(EshopModule $module)
    {
        $this->module = $module;
    }

    /**
     * @deprecated since v6.4.0 (2019-05-24); Module smarty plugins directory are stored in project configuration file now.
     *             Use appropriate Dao to add them.
     *
     * @param array  $moduleSmartyPluginDirectories
     * @param string $moduleId
     */
    public function add($moduleSmartyPluginDirectories, $moduleId)
    {
        $this->moduleSmartyPluginDirectories[$moduleId] = $moduleSmartyPluginDirectories;
    }

    /**
     *
     * @deprecated since v6.4.0 (2019-05-24); Module smarty plugins directory are stored in project configuration file now.
     *             Use appropriate Dao to set them.
     *
     * @param array $moduleSmartyPluginDirectories
     */
    public function set($moduleSmartyPluginDirectories)
    {
        $this->moduleSmartyPluginDirectories = $moduleSmartyPluginDirectories;
    }

    /**
     * Delete the smarty plugin directories for the module, given by its ID
     *
     * @deprecated since v6.4.0 (2019-05-24); Module smarty plugins directory are stored in project configuration file now.
     *             Use appropriate Dao to remove them.
     *
     * @param string $moduleId The ID of the module, for which we want to delete the controllers from the storage.
     */
    public function remove($moduleId)
    {
        unset($this->moduleSmartyPluginDirectories[$moduleId]);
    }

    /**
     * @deprecated since v6.4.0 (2019-05-24); Module smarty plugins directory are stored in project configuration file now.
     *             Use appropriate Dao to get them.
     *
     * @return array The smarty plugin directories of all modules with absolute path as numeric array
     */
    public function getWithRelativePath()
    {
        return $this->moduleSmartyPluginDirectories;
    }

    /**
     * @return array
     */
    public function getWithFullPath()
    {
        $smartyPluginDirectoriesWithFullPath = [];
        $smartyPluginDirectories = $this->getWithRelativePath();

        foreach ($smartyPluginDirectories as $moduleId => $smartyDirectoriesOfOneModule) {
            foreach ($smartyDirectoriesOfOneModule as $smartyPluginDirectory) {
                $smartyPluginDirectoriesWithFullPath[] = $this->module->getModuleFullPath($moduleId) .
                                                         DIRECTORY_SEPARATOR .
                                                         $smartyPluginDirectory;
            }
        }

        return $smartyPluginDirectoriesWithFullPath;
    }
}
