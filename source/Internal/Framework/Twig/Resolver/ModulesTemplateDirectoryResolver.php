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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): ported from twig-component v2.0.1 to PHP 7.4
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Webmozart\PathUtil\Path;

/**
 * Finds the "views/twig" directories of the modules active in the current shop, in activation order.
 */
class ModulesTemplateDirectoryResolver
{
    /** @var ShopConfigurationSettingDaoInterface */
    private $shopConfigurationSettingDao;

    /** @var ModulePathResolverInterface */
    private $modulePathResolver;

    /** @var ContextInterface */
    private $context;

    /** @var array<int, array<string, string>> directories per shop id */
    private $directories = [];

    public function __construct(
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao,
        ModulePathResolverInterface $modulePathResolver,
        ContextInterface $context
    ) {
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
        $this->modulePathResolver = $modulePathResolver;
        $this->context = $context;
    }

    /**
     * @return array<string, string> module id => absolute "views/twig" directory, in activation order
     */
    public function getTemplateDirectories(): array
    {
        $shopId = $this->context->getCurrentShopId();
        if (!isset($this->directories[$shopId])) {
            $this->directories[$shopId] = $this->findTemplateDirectories($shopId);
        }

        return $this->directories[$shopId];
    }

    private function findTemplateDirectories(int $shopId): array
    {
        $directories = [];
        foreach ($this->getActiveModuleIds($shopId) as $moduleId) {
            try {
                $modulePath = $this->modulePathResolver->getFullModulePathFromConfiguration($moduleId, $shopId);
            } catch (ModuleConfigurationNotFoundException $exception) {
                continue;
            }
            $directory = Path::join($modulePath, 'views', 'twig');
            if (is_dir($directory)) {
                $directories[$moduleId] = $directory;
            }
        }

        return $directories;
    }

    /**
     * The "activeModules" setting is appended to on activation, so its order is the activation order.
     */
    private function getActiveModuleIds(int $shopId): array
    {
        try {
            $setting = $this->shopConfigurationSettingDao->get(ShopConfigurationSetting::ACTIVE_MODULES, $shopId);
        } catch (EntryDoesNotExistDaoException $exception) {
            return [];
        }

        return array_values((array) $setting->getValue());
    }
}
