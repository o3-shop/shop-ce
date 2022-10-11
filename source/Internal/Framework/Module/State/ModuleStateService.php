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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\State;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;

use function in_array;

class ModuleStateService implements ModuleStateServiceInterface
{
    /**
     * @var ShopConfigurationSettingDaoInterface
     */
    private $shopConfigurationSettingDao;

    /**
     * ModuleStateService constructor.
     * @param ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
     */
    public function __construct(
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao
    ) {
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     * @return bool
     */
    public function isActive(string $moduleId, int $shopId): bool
    {
        $activeModuleIdsSetting = $this->getActiveModulesShopConfigurationSetting($shopId);

        return in_array($moduleId, $activeModuleIdsSetting->getValue(), true);
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     *
     * @throws ModuleStateIsAlreadySetException
     */
    public function setActive(string $moduleId, int $shopId)
    {
        if ($this->isActive($moduleId, $shopId)) {
            throw new ModuleStateIsAlreadySetException(
                'Active status for module "' . $moduleId . '" and shop with id "' . $shopId . '" is already set.'
            );
        }

        $activeModuleIdsSetting = $this->getActiveModulesShopConfigurationSetting($shopId);

        $activeModuleIds = $activeModuleIdsSetting->getValue();
        $activeModuleIds[] = $moduleId;
        $activeModuleIdsSetting->setValue($activeModuleIds);

        $this->shopConfigurationSettingDao->save($activeModuleIdsSetting);
    }

    /**
     * @param string $moduleId
     * @param int    $shopId
     *
     * @throws ModuleStateIsAlreadySetException
     */
    public function setDeactivated(string $moduleId, int $shopId)
    {
        if (!$this->isActive($moduleId, $shopId)) {
            throw new ModuleStateIsAlreadySetException(
                'Deactivated status for module "' . $moduleId . '" and shop with id "' . $shopId . '" is already set.'
            );
        }

        $activeModuleIdsSetting = $this->getActiveModulesShopConfigurationSetting($shopId);

        $activeModuleIds = $activeModuleIdsSetting->getValue();

        $activeModuleIds = array_diff($activeModuleIds, [$moduleId]);
        $activeModuleIdsSetting->setValue($activeModuleIds);

        $this->shopConfigurationSettingDao->save($activeModuleIdsSetting);
    }

    /**
     * @param int $shopId
     * @return ShopConfigurationSetting
     */
    private function getActiveModulesShopConfigurationSetting(int $shopId): ShopConfigurationSetting
    {
        try {
            $activeModuleIdsSetting = $this->shopConfigurationSettingDao->get(
                ShopConfigurationSetting::ACTIVE_MODULES,
                $shopId
            );
        } catch (EntryDoesNotExistDaoException $exception) {
            $activeModuleIdsSetting = new ShopConfigurationSetting();
            $activeModuleIdsSetting
                ->setShopId($shopId)
                ->setName(ShopConfigurationSetting::ACTIVE_MODULES)
                ->setType(ShopSettingType::ARRAY)
                ->setValue([]);
        }

        return $activeModuleIdsSetting;
    }
}
