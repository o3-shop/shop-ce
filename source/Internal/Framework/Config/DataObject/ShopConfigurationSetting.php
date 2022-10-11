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

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject;

class ShopConfigurationSetting
{
    const MODULE_CLASS_EXTENSIONS           = 'aModuleExtensions';
    const MODULE_CLASS_EXTENSIONS_CHAIN     = 'aModules';
    const MODULE_CONTROLLERS                = 'aModuleControllers';
    const MODULE_VERSIONS                   = 'aModuleVersions';
    const MODULE_PATHS                      = 'aModulePaths';
    /**
     * @deprecated 6.6.0
     */
    const MODULE_TEMPLATES                  = 'aModuleTemplates';
    const MODULE_SMARTY_PLUGIN_DIRECTORIES  = 'moduleSmartyPluginDirectories';
    const MODULE_EVENTS                     = 'aModuleEvents';
    /**
     * @deprecated 6.6 Will be removed completely
     */
    const MODULE_CLASSES_WITHOUT_NAMESPACES = 'aModuleFiles';

    const ACTIVE_MODULES = 'activeModules';

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @return int
     */
    public function getShopId(): int
    {
        return $this->shopId;
    }

    /**
     * @param int $shopId
     * @return ShopConfigurationSetting
     */
    public function setShopId(int $shopId): ShopConfigurationSetting
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ShopConfigurationSetting
     */
    public function setName(string $name): ShopConfigurationSetting
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ShopConfigurationSetting
     */
    public function setType(string $type): ShopConfigurationSetting
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return ShopConfigurationSetting
     */
    public function setValue($value): ShopConfigurationSetting
    {
        $this->value = $value;
        return $this;
    }
}
