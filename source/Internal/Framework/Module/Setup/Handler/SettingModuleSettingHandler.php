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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;

class SettingModuleSettingHandler implements ModuleConfigurationHandlerInterface
{
    /** @var SettingDaoInterface */
    private $settingDao;

    /**
     * SettingModuleSettingHandler constructor.
     * @param SettingDaoInterface $shopModuleSettingDao
     */
    public function __construct(SettingDaoInterface $shopModuleSettingDao)
    {
        $this->settingDao = $shopModuleSettingDao;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     */
    public function handleOnModuleActivation(ModuleConfiguration $configuration, int $shopId): void
    {
        foreach ($configuration->getModuleSettings() as $setting) {
            $this->settingDao->save($setting, $configuration->getId(), $shopId);
        }
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     */
    public function handleOnModuleRefresh(ModuleConfiguration $configuration, int $shopId)
    {
        // Seed settings that are new in metadata.php with their default, but never
        // overwrite a value the shop owner configured. Without this a setting added
        // in an update is not in oxconfig, so getConfigParam() returns null until the
        // admin saves the module configuration once.
        foreach ($configuration->getModuleSettings() as $setting) {
            try {
                $this->settingDao->get($setting->getName(), $configuration->getId(), $shopId);
            } catch (EntryDoesNotExistDaoException $exception) {
                $this->settingDao->save($setting, $configuration->getId(), $shopId);
            }
        }
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int                 $shopId
     */
    public function handleOnModuleDeactivation(ModuleConfiguration $configuration, int $shopId): void
    {
        foreach ($configuration->getModuleSettings() as $setting) {
            $this->settingDao->delete($setting, $configuration->getId(), $shopId);
        }
    }
}
