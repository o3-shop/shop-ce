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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleSettingBridge implements ModuleSettingBridgeInterface
{
    /**
     * @var ModuleConfigurationDaoInterface
     */
    private $moduleConfigurationDao;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var SettingDaoInterface
     */
    private $settingDao;

    public function __construct(
        ContextInterface $context,
        ModuleConfigurationDaoInterface $moduleConfigurationDao,
        SettingDaoInterface $settingDao
    ) {
        $this->context = $context;
        $this->moduleConfigurationDao = $moduleConfigurationDao;
        $this->settingDao = $settingDao;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string $moduleId
     */
    public function save(string $name, $value, string $moduleId): void
    {
        $moduleConfiguration = $this->moduleConfigurationDao->get($moduleId, $this->context->getCurrentShopId());
        $setting = $moduleConfiguration->getModuleSetting($name);
        $setting->setValue($value);
        $this->settingDao->save($setting, $moduleId, $this->context->getCurrentShopId());
        $this->moduleConfigurationDao->save($moduleConfiguration, $this->context->getCurrentShopId());
    }

    /**
     * @param string $name
     * @param string $moduleId
     * @return mixed
     */
    public function get(string $name, string $moduleId)
    {
        $configuration = $this->moduleConfigurationDao->get($moduleId, $this->context->getCurrentShopId());
        return $configuration->getModuleSetting($name)->getValue();
    }
}
