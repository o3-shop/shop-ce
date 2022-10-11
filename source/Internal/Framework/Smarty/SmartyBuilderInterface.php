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

namespace OxidEsales\EshopCommunity\Internal\Framework\Smarty;

interface SmartyBuilderInterface
{
    /**
     * Sets properties of smarty object.
     *
     * @param array $settings
     *
     * @return self
     */
    public function setSettings(array $settings);

    /**
     * Sets security options of smarty object.
     *
     * @param array $settings
     *
     * @return self
     */
    public function setSecuritySettings(array $settings);

    /**
     * Registers a resource of smarty object.
     *
     * @param array $resourcesToRegister
     *
     * @return self
     */
    public function registerResources(array $resourcesToRegister);

    /**
     * Register prefilters of smarty object.
     *
     * @param array $prefilters
     *
     * @return self
     */
    public function registerPrefilters(array $prefilters);

    /**
     * Register plugins of smarty object.
     *
     * @param array $plugins
     *
     * @return self
     */
    public function registerPlugins(array $plugins);

    /**
     * @return \Smarty
     */
    public function getSmarty(): \Smarty;
}
