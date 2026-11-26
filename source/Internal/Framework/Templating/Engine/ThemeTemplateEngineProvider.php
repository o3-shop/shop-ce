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

namespace OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use Psr\Log\LoggerInterface;

class ThemeTemplateEngineProvider implements ThemeTemplateEngineProviderInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string|null */
    private $viewsDir;

    public function __construct(LoggerInterface $logger, ?string $viewsDir = null)
    {
        $this->logger = $logger;
        $this->viewsDir = $viewsDir;
    }

    public function getEngineName(?string $themeId = null): string
    {
        $themeId = $themeId ?? (string) (new Theme())->getActiveThemeId();
        $seen = [];

        while ($themeId !== '' && !isset($seen[$themeId])) {
            $seen[$themeId] = true;
            $info = $this->loadThemeInfo($themeId);
            if ($info === null) {
                $this->logger->warning(
                    __METHOD__ . " - Theme '$themeId' could not be loaded. Falling back to template engine '"
                    . TemplateEngineName::SMARTY . "'."
                );

                return TemplateEngineName::SMARTY;
            }
            $own = $this->ownEngineFrom($info, $themeId);
            if ($own !== null) {
                return $own;
            }
            $themeId = (string) ($info['parentTheme'] ?? '');
        }

        return TemplateEngineName::SMARTY;
    }

    public function getOwnEngineName(string $themeId): ?string
    {
        $info = $this->loadThemeInfo($themeId);

        return $info === null ? null : $this->ownEngineFrom($info, $themeId);
    }

    public function getParentThemeId(string $themeId): ?string
    {
        $info = $this->loadThemeInfo($themeId);
        $parent = $info === null ? '' : (string) ($info['parentTheme'] ?? '');

        return $parent === '' ? null : $parent;
    }

    private function ownEngineFrom(array $info, string $themeId): ?string
    {
        if (!isset($info['templateEngine'])) {
            return null;
        }
        $engine = $info['templateEngine'];
        if ($engine === TemplateEngineName::TWIG || $engine === TemplateEngineName::SMARTY) {
            return $engine;
        }
        $this->logger->warning(
            __METHOD__ . " - Theme '$themeId' declares an invalid template engine. Treating it as '"
            . TemplateEngineName::SMARTY . "'."
        );

        return TemplateEngineName::SMARTY;
    }

    private function loadThemeInfo(string $themeId): ?array
    {
        $file = ($this->viewsDir ?? Registry::getConfig()->getViewsDir()) . $themeId . '/theme.php';
        if (!is_readable($file)) {
            return null;
        }
        $aTheme = [];
        include $file;

        return $aTheme;
    }
}
