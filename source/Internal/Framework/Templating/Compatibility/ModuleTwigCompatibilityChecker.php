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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Templating\Compatibility;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use Webmozart\PathUtil\Path;

class ModuleTwigCompatibilityChecker implements ModuleTwigCompatibilityCheckerInterface
{
    /** @var ShopConfigurationDaoInterface */
    private $shopConfigurationDao;

    /** @var ShopConfigurationSettingDaoInterface */
    private $shopConfigurationSettingDao;

    /** @var ModulePathResolverInterface */
    private $modulePathResolver;

    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao,
        ModulePathResolverInterface $modulePathResolver
    ) {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
        $this->modulePathResolver = $modulePathResolver;
    }

    public function getModulesWithoutTwigTemplates(int $shopId): array
    {
        $shopConfiguration = $this->shopConfigurationDao->get($shopId);
        $moduleIds = [];
        foreach ($this->getActiveModuleIds($shopId) as $moduleId) {
            if (!$shopConfiguration->hasModuleConfiguration($moduleId)) {
                continue;
            }
            try {
                $modulePath = $this->modulePathResolver->getFullModulePathFromConfiguration($moduleId, $shopId);
            } catch (ModuleConfigurationNotFoundException $exception) {
                continue;
            }
            if ($this->lacksTwigTemplates($shopConfiguration->getModuleConfiguration($moduleId), $modulePath)) {
                $moduleIds[] = $moduleId;
            }
        }
        return $moduleIds;
    }

    private function lacksTwigTemplates(ModuleConfiguration $configuration, string $modulePath): bool
    {
        if (is_dir(Path::join($modulePath, 'views', 'twig'))) {
            return false;
        }
        $paths = [];
        foreach ($configuration->getTemplates() as $template) {
            $paths[] = $template->getTemplatePath();
        }
        foreach ($configuration->getTemplateBlocks() as $block) {
            $paths[] = $block->getModuleTemplatePath();
        }
        foreach ($paths as $path) {
            if ($this->isStorefrontSmartyTemplate($path) && !is_file(Path::join($modulePath, substr($path, 0, -4) . '.html.twig'))) {
                return true;
            }
        }

        return false;
    }

    private function isStorefrontSmartyTemplate(string $path): bool
    {
        return substr($path, -4) === '.tpl' && !preg_match('#(^|/)admin/#', $path);
    }

    /**
     * @return string[]
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
