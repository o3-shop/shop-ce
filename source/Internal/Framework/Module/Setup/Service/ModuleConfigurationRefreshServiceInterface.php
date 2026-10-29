<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

interface ModuleConfigurationRefreshServiceInterface
{
    /**
     * Re-applies an active module's configuration to the shop: the stored module settings
     * are re-derived from the given configuration, the module's services are re-registered
     * and the class extension chain is rebuilt. The module's activation state is not
     * changed and settings configured by the shop owner are preserved.
     *
     * Unlike a deactivate/activate cycle this also works for configuration changes made
     * after the module was activated, which is what makes it usable to keep a shop
     * consistent with a module whose metadata.php changed in place.
     *
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function refresh(ModuleConfiguration $moduleConfiguration, int $shopId);

    /**
     * Refreshes the given module in every shop where it is configured and active,
     * isolating a failure in one shop from the remaining shops.
     *
     * @param string $moduleId
     *
     * @return array{refreshed: int[], failures: array<int, string>}
     */
    public function refreshActiveModuleWithFailures(string $moduleId): array;
}
