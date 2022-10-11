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

class SmartyBuilder implements SmartyBuilderInterface
{
    /**
     * @var \Smarty
     */
    private $smarty;

    /**
     * SmartyBuilder constructor.
     */
    public function __construct()
    {
        $this->smarty = new \Smarty();
    }

    /**
     * Sets properties of smarty object.
     *
     * @param array $settings
     *
     * @return self
     */
    public function setSettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->smarty->$key = $value;
        }
        return $this;
    }

    /**
     * Sets security options of smarty object.
     *
     * @param array $settings
     *
     * @return self
     */
    public function setSecuritySettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue)) {
                        $originalSettings = $this->smarty->{$key}[$subKey];
                        $this->smarty->{$key}[$subKey] = array_merge($originalSettings, $subValue);
                    } else {
                        $this->smarty->{$key}[$subKey] = $subValue;
                    }
                }
            } else {
                $this->smarty->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Registers a resource of smarty object.
     *
     * @param array $resourcesToRegister
     *
     * @return self
     */
    public function registerResources(array $resourcesToRegister)
    {
        foreach ($resourcesToRegister as $key => $resources) {
            $this->smarty->register_resource($key, $resources);
        }
        return $this;
    }

    /**
     * Register prefilters of smarty object.
     *
     * @param array $prefilters
     *
     * @return self
     */
    public function registerPrefilters(array $prefilters)
    {
        foreach ($prefilters as $prefilter => $path) {
            if (file_exists($path)) {
                include_once $path;
                $this->smarty->register_prefilter($prefilter);
            }
        }
        return $this;
    }

    /**
     * Register plugins of smarty object.
     *
     * @param array $plugins
     *
     * @return self
     */
    public function registerPlugins(array $plugins)
    {
        if (is_array($plugins)) {
            $this->smarty->plugins_dir = array_merge(
                $plugins,
                $this->smarty->plugins_dir
            );
        }
        return $this;
    }

    /**
     * @return \Smarty
     */
    public function getSmarty(): \Smarty
    {
        return $this->smarty;
    }
}
