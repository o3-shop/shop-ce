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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\TemplateEngineName;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProviderInterface;

class ThemeEngineMismatchChecker implements ThemeEngineMismatchCheckerInterface
{
    /** @var ThemeTemplateEngineProviderInterface */
    private $engineProvider;

    /** @var string|null */
    private $viewsDir;

    public function __construct(
        ThemeTemplateEngineProviderInterface $engineProvider,
        ?string $viewsDir = null
    ) {
        $this->engineProvider = $engineProvider;
        $this->viewsDir = $viewsDir;
    }

    public function getMismatch(string $themeId): ?array
    {
        $parentId = $this->engineProvider->getParentThemeId($themeId);
        if ($parentId === null) {
            return null;
        }
        $childEngine = $this->engineProvider->getOwnEngineName($themeId)
            ?? ($this->hasOwnSmartyTemplates($themeId) ? TemplateEngineName::SMARTY : null);
        $parentEngine = $this->engineProvider->getEngineName($parentId);
        if ($childEngine === null || $childEngine === $parentEngine) {
            return null;
        }
        return ['child' => $themeId, 'parent' => $parentId, 'childEngine' => $childEngine, 'parentEngine' => $parentEngine];
    }

    private function hasOwnSmartyTemplates(string $themeId): bool
    {
        $dir = ($this->viewsDir ?? Registry::getConfig()->getViewsDir()) . $themeId . '/tpl';
        if (!is_dir($dir)) {
            return false;
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (substr($file->getFilename(), -4) === '.tpl') {
                return true;
            }
        }

        return false;
    }
}
