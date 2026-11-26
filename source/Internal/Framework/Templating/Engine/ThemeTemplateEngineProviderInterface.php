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

interface ThemeTemplateEngineProviderInterface
{
    /**
     * Engine name of the given theme (null = active theme), parent theme inherited, default 'smarty'.
     */
    public function getEngineName(?string $themeId = null): string;

    /**
     * The theme's own 'templateEngine' value, or null if it declares none (or is unknown).
     */
    public function getOwnEngineName(string $themeId): ?string;

    /**
     * The id of the theme's parent theme, or null if it has none (or is unknown).
     */
    public function getParentThemeId(string $themeId): ?string;
}
