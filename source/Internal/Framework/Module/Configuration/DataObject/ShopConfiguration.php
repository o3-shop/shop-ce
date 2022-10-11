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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;

class ShopConfiguration
{
    /** @var ModuleConfiguration[] */
    private $moduleConfigurations = [];

    /**
     * @var ClassExtensionsChain
     */
    private $chain;

    /**
     * ShopConfiguration constructor.
     */
    public function __construct()
    {
        $classExtensionChain = new ClassExtensionsChain();
        $this->setClassExtensionsChain($classExtensionChain);
    }

    /**
     * @param string $moduleId
     *
     * @return ModuleConfiguration
     * @throws ModuleConfigurationNotFoundException
     */
    public function getModuleConfiguration(string $moduleId): ModuleConfiguration
    {
        if (array_key_exists($moduleId, $this->moduleConfigurations)) {
            return $this->moduleConfigurations[$moduleId];
        }
        throw new ModuleConfigurationNotFoundException('There is no module configuration with id ' . $moduleId);
    }

    /**
     * @return ModuleConfiguration[]
     */
    public function getModuleConfigurations(): array
    {
        return $this->moduleConfigurations;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @return $this
     */
    public function addModuleConfiguration(ModuleConfiguration $moduleConfiguration)
    {
        $this->moduleConfigurations[$moduleConfiguration->getId()] = $moduleConfiguration;

        return $this;
    }

    /**
     * @param string $moduleId
     *
     * @throws ModuleConfigurationNotFoundException
     */
    public function deleteModuleConfiguration(string $moduleId)
    {
        if (\array_key_exists($moduleId, $this->moduleConfigurations)) {
            $this->removeModuleExtensionFromClassChain($moduleId);
            unset($this->moduleConfigurations[$moduleId]);
        } else {
            throw new ModuleConfigurationNotFoundException('There is no module configuration with id ' . $moduleId);
        }
    }

    /**
     * @return array
     */
    public function getModuleIdsOfModuleConfigurations(): array
    {
        return array_keys($this->moduleConfigurations);
    }

    /**
     * @param ClassExtensionsChain $chain
     */
    public function setClassExtensionsChain(ClassExtensionsChain $chain)
    {
        $this->chain = $chain;
    }

    /**
     * @return ClassExtensionsChain
     */
    public function getClassExtensionsChain(): ClassExtensionsChain
    {
        return $this->chain;
    }

    /**
     * @param string $moduleId
     * @return bool
     */
    public function hasModuleConfiguration(string $moduleId): bool
    {
        return isset($this->moduleConfigurations[$moduleId]);
    }

    /**
     * @param string $moduleId
     */
    private function removeModuleExtensionFromClassChain(string $moduleId): void
    {
        $moduleConfiguration = $this->moduleConfigurations[$moduleId];
        foreach ($moduleConfiguration->getClassExtensions() as $classExtension) {
            $this->getClassExtensionsChain()->removeExtension($classExtension);
        }
    }
}
