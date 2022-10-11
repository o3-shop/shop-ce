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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ExtensionNotInChainException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;

class ModuleConfigurationMergingService implements ModuleConfigurationMergingServiceInterface
{
    /**
     * @var SettingsMergingService
     */
    private $settingsMergingService;

    /**
     * @var ModuleClassExtensionsMergingService
     */
    private $classExtensionsMergingService;

    /**
     * @param SettingsMergingServiceInterface $moduleSettingsMergingService
     * @param ModuleClassExtensionsMergingServiceInterface $classExtensionsMergingService
     */
    public function __construct(
        SettingsMergingServiceInterface $moduleSettingsMergingService,
        ModuleClassExtensionsMergingServiceInterface $classExtensionsMergingService
    ) {
        $this->settingsMergingService = $moduleSettingsMergingService;
        $this->classExtensionsMergingService = $classExtensionsMergingService;
    }

    /**
     * @inheritDoc
     * @throws ModuleConfigurationNotFoundException
     * @throws ExtensionNotInChainException
     */
    public function merge(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $moduleConfiguration
    ): ShopConfiguration {
        $moduleConfigurationClone = $this->cloneModuleConfiguration($moduleConfiguration);

        $mergedClassExtensionChain = $this->classExtensionsMergingService->merge(
            $shopConfiguration,
            $moduleConfigurationClone
        );
        $shopConfiguration->setClassExtensionsChain($mergedClassExtensionChain);

        $mergedModuleConfiguration = $this->settingsMergingService->merge(
            $shopConfiguration,
            $moduleConfigurationClone
        );

        $this->setConfiguredOptionToMergedConfiguration($shopConfiguration, $mergedModuleConfiguration);

        $shopConfiguration->addModuleConfiguration($mergedModuleConfiguration);

        return $shopConfiguration;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @return ModuleConfiguration
     */
    private function cloneModuleConfiguration(ModuleConfiguration $moduleConfiguration): ModuleConfiguration
    {
        $moduleSettingClones = [];
        foreach ($moduleConfiguration->getModuleSettings() as $moduleSetting) {
            $moduleSettingClones[] = clone $moduleSetting;
        }
        $moduleConfigurationClone = clone $moduleConfiguration;
        $moduleConfigurationClone->setModuleSettings($moduleSettingClones);
        return $moduleConfigurationClone;
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     * @param ModuleConfiguration $mergedModuleConfiguration
     * @throws ModuleConfigurationNotFoundException
     */
    private function setConfiguredOptionToMergedConfiguration(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $mergedModuleConfiguration
    ): void {
        if ($shopConfiguration->hasModuleConfiguration($mergedModuleConfiguration->getId())) {
            $isConfigured = $shopConfiguration->getModuleConfiguration($mergedModuleConfiguration->getId())->isConfigured();
            $mergedModuleConfiguration->setConfigured($isConfigured);
        }
    }
}
