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

namespace OxidEsales\EshopCommunity\Core\Routing\Module;

use OxidEsales\Eshop\Core\Contract\ClassProviderStorageInterface;
use OxidEsales\Eshop\Core\Registry;

/**
 * Handler class for the storing of the metadata controller field of the modules.
 *
 * @deprecated since v6.4.0 (2019-03-22); Use `OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ModuleConfigurationDaoBridgeInterface`.
 * @internal Do not make a module extension for this class.
 */
class ClassProviderStorage implements ClassProviderStorageInterface
{
    /**
     * @var string The key under which the value will be stored.
     */
    const STORAGE_KEY = 'aModuleControllers';

    /**
     * Get the stored controller value from the oxconfig.
     *
     * @return null|array The controllers field of the modules metadata.
     */
    public function get()
    {
        return (array) $this->getConfig()->getShopConfVar(self::STORAGE_KEY);
    }

    /**
     * Set the stored controller value from the oxconfig.
     *
     * @param array $value The controllers field of the modules metadata.
     */
    public function set($value)
    {
        $value = $this->toLowercase($value);

        $this->getConfig()->saveShopConfVar('aarr', self::STORAGE_KEY, $value);
    }

    /**
     * Add the controllers for the module, given by its ID, to the storage.
     *
     * @param string $moduleId    The ID of the module controllers to add.
     * @param array  $controllers The controllers to add to the storage.
     */
    public function add($moduleId, $controllers)
    {
        $controllerMap = $this->get();
        $controllerMap[$moduleId] = $controllers;

        $this->set($controllerMap);
    }

    /**
     * Delete the controllers for the module, given by its ID, from the storage.
     *
     * @param string $moduleId The ID of the module, for which we want to delete the controllers from the storage.
     */
    public function remove($moduleId)
    {
        $controllerMap = $this->get();
        unset($controllerMap[strtolower($moduleId)]);

        $this->set($controllerMap);
    }

    /**
     * Change the module IDs and the controller keys to lower case.
     *
     * @param array $modulesControllers The controller arrays of several modules.
     *
     * @return array The given controller arrays of several modules, with the module IDs and the controller keys in lower case.
     */
    private function toLowercase($modulesControllers)
    {
        $result = [];

        if (!is_null($modulesControllers)) {
            foreach ($modulesControllers as $moduleId => $controllers) {
                $result[strtolower($moduleId)] = $this->controllerKeysToLowercase($controllers);
            }
        }

        return $result;
    }

    /**
     * Change the controller keys to lower case.
     *
     * @param array $controllers The controllers array of one module.
     *
     * @return array The given controllers array with the controller keys in lower case.
     */
    private function controllerKeysToLowercase($controllers)
    {
        $result = [];

        foreach ($controllers as $controllerKey => $controllerClass) {
            $result[strtolower($controllerKey)] = $controllerClass;
        }

        return $result;
    }

    /**
     * Get the config object.
     *
     * @return \oxConfig The config object.
     */
    private function getConfig()
    {
        return Registry::getConfig();
    }
}
