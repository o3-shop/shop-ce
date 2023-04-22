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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\TemplateBlockModuleSettingHandler;

class TemplateBlockModuleSettingHandlerBridge implements TemplateBlockModuleSettingHandlerBridgeInterface
{
    /**
     * @var TemplateBlockModuleSettingHandler
     */
    private $templateBlockModuleSettingHandlerService;

    /**
     * @param TemplateBlockModuleSettingHandler $templateBlockModuleSettingHandlerService
     */
    public function __construct(TemplateBlockModuleSettingHandler $templateBlockModuleSettingHandlerService)
    {
        $this->templateBlockModuleSettingHandlerService = $templateBlockModuleSettingHandlerService;
    }

    public function handleOnModuleActivation(ModuleConfiguration $configuration, int $shopId)
    {
        $this->templateBlockModuleSettingHandlerService->handleOnModuleActivation($configuration, $shopId);
    }

    public function handleOnModuleDeactivation(ModuleConfiguration $configuration, int $shopId)
    {
        $this->templateBlockModuleSettingHandlerService->handleOnModuleDeactivation($configuration, $shopId);
    }
}