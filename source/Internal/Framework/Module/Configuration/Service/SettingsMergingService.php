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
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;

class SettingsMergingService implements SettingsMergingServiceInterface
{
    /**
     * @param ShopConfiguration   $shopConfiguration
     * @param ModuleConfiguration $moduleConfigurationToMerge
     *
     * @return ModuleConfiguration
     */
    public function merge(
        ShopConfiguration $shopConfiguration,
        ModuleConfiguration $moduleConfigurationToMerge
    ): ModuleConfiguration {
        if ($shopConfiguration->hasModuleConfiguration($moduleConfigurationToMerge->getId())) {
            $existingModuleConfiguration = $shopConfiguration->getModuleConfiguration($moduleConfigurationToMerge->getId());
            if (
                !empty($existingModuleConfiguration->getModuleSettings()) &&
                !empty($moduleConfigurationToMerge->getModuleSettings())
            ) {
                $mergedModuleSettings = $this->mergeModuleSettings(
                    $existingModuleConfiguration->getModuleSettings(),
                    $moduleConfigurationToMerge->getModuleSettings()
                );
                $moduleConfigurationToMerge->setModuleSettings($mergedModuleSettings);
            }
        }
        return $moduleConfigurationToMerge;
    }

    /**
     * @param Setting[] $existingSettings
     * @param Setting[] $settingsToMerge
     *
     * @return Setting[]
     */
    private function mergeModuleSettings(array $existingSettings, array $settingsToMerge): array
    {
        foreach ($settingsToMerge as &$settingToMerge) {
            foreach ($existingSettings as $existingSetting) {
                if ($this->shouldMerge($existingSetting, $settingToMerge)) {
                    $settingToMerge->setValue($existingSetting->getValue());
                }
            }
        }

        return $settingsToMerge;
    }

    /**
     * @param Setting $existingSetting
     * @param Setting $settingToMerge
     * @return bool
     */
    private function shouldMerge(Setting $existingSetting, Setting $settingToMerge): bool
    {
        $shouldMerge = $existingSetting->getValue() !== null &&
            $existingSetting->getName() === $settingToMerge->getName() &&
            $existingSetting->getType() === $settingToMerge->getType();

        if (
            $shouldMerge === true
            && !empty($settingToMerge->getConstraints())
            && ($settingToMerge->getType() === 'select')
        ) {
            $resultPosition = array_search($existingSetting->getValue(), $settingToMerge->getConstraints(), true);
            $shouldMerge = $resultPosition !== false;
        }

        return $shouldMerge;
    }
}
