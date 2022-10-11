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

namespace OxidEsales\EshopCommunity\Core\Contract;

/**
 * Interface for Handling the storing/loading of the metadata controller field of the modules.
 *
 * @deprecated since v6.4.0 (2019-03-22); Use `OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ModuleConfigurationDaoBridgeInterface`.
 * @internal Do not make a module extension for this class.
 */
interface ClassProviderStorageInterface
{
    /**
     * Get the stored controller value from the storage.
     *
     * @return array The controllers field of the modules metadata.
     */
    public function get();

    /**
     * Set the stored controller value from the storage.
     *
     * @param array $value The controllers field of the modules metadata.
     */
    public function set($value);

    /**
     * Add the controllers for the module, given by its ID, to the storage.
     *
     * @param string $moduleId    The ID of the module controllers to add.
     * @param array  $controllers The controllers to add to the storage.
     */
    public function add($moduleId, $controllers);

    /**
     * Delete the controllers for the module, given by its ID, from the storage.
     *
     * @param string $moduleId The ID of the module, for which we want to delete the controllers from the storage.
     */
    public function remove($moduleId);
}
