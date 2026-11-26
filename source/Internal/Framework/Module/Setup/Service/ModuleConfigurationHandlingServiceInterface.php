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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

interface ModuleConfigurationHandlingServiceInterface
{
    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function handleOnActivation(ModuleConfiguration $moduleConfiguration, int $shopId);

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function handleOnDeactivation(ModuleConfiguration $moduleConfiguration, int $shopId);

    /**
     * Re-applies the given configuration to the shop without changing the module's
     * activation state, so the shop follows configuration changes made after the module
     * was activated. Settings configured by the shop owner are left untouched.
     *
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function handleOnRefresh(ModuleConfiguration $moduleConfiguration, int $shopId);

    /**
     * Validates the module configuration without applying any changes to the shop.
     *
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function validate(ModuleConfiguration $moduleConfiguration, int $shopId): void;
}
