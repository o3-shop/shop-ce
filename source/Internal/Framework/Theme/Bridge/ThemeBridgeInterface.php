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

namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface ThemeBridgeInterface
{
    /**
     * Activates a theme, writing its theme.php defaults to the shop configuration.
     *
     * @throws ThemeNotFoundException
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException on activation errors (e.g. parent version mismatch)
     */
    public function activate(string $themeId): void;

    /**
     * Deactivates a theme.
     * - If $themeId is the active child theme (sCustomTheme), clears it (reverts to the parent base theme).
     * - If $themeId is the active base theme (sTheme), refuses.
     * - If $themeId is not active, does nothing.
     *
     * @return bool true if the theme was deactivated, false if it was not active (no-op)
     * @throws ThemeNotFoundException
     * @throws CannotDeactivateThemeException
     */
    public function deactivate(string $themeId): bool;

    /**
     * Returns the configured active theme id (sCustomTheme if set, else sTheme), or '' if none.
     */
    public function getActiveThemeId(): string;

    /**
     * @return ThemeDataObject[]
     */
    public function list(): array;
}
