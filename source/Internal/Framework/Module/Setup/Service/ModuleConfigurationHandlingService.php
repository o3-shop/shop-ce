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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\ModuleConfigurationHandlerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator\ModuleConfigurationValidatorInterface;

class ModuleConfigurationHandlingService implements ModuleConfigurationHandlingServiceInterface
{
    /**
     * @var ModuleConfigurationHandlerInterface[]
     */
    private $handlers = [];

    /**
     * @var ModuleConfigurationValidatorInterface[]
     */
    private $moduleConfigurationValidators = [];

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function handleOnActivation(ModuleConfiguration $moduleConfiguration, int $shopId)
    {
        $this->validateModuleConfiguration($moduleConfiguration, $shopId);

        foreach ($this->handlers as $handler) {
            $handler->handleOnModuleActivation($moduleConfiguration, $shopId);
        }
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    public function handleOnDeactivation(ModuleConfiguration $moduleConfiguration, int $shopId)
    {
        foreach ($this->handlers as $handler) {
            $handler->handleOnModuleDeactivation($moduleConfiguration, $shopId);
        }
    }

    /**
     * @param ModuleConfigurationHandlerInterface $moduleSettingHandler
     */
    public function addHandler(ModuleConfigurationHandlerInterface $moduleSettingHandler)
    {
        $this->handlers[] = $moduleSettingHandler;
    }

    /**
     * @param ModuleConfigurationValidatorInterface $configuration
     */
    public function addValidator(ModuleConfigurationValidatorInterface $configuration)
    {
        $this->moduleConfigurationValidators[] = $configuration;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param int                 $shopId
     */
    private function validateModuleConfiguration(ModuleConfiguration $moduleConfiguration, int $shopId)
    {
        foreach ($this->moduleConfigurationValidators as $moduleConfigurationValidator) {
            $moduleConfigurationValidator->validate($moduleConfiguration, $shopId);
        }
    }
}
