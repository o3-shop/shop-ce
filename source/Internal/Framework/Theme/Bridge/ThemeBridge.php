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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;

class ThemeBridge implements ThemeBridgeInterface
{
    public function activate(string $themeId): void
    {
        $this->loadTheme($themeId)->activate();
    }

    public function deactivate(string $themeId): bool
    {
        $this->loadTheme($themeId);

        $config = Registry::getConfig();
        $customTheme = (string) $config->getConfigParam('sCustomTheme');
        $baseTheme = (string) $config->getConfigParam('sTheme');

        if ($customTheme !== '' && $customTheme === $themeId) {
            $config->saveShopConfVar('str', 'sCustomTheme', '');
            return true;
        }

        if ($baseTheme === $themeId) {
            throw new CannotDeactivateThemeException($themeId);
        }

        return false;
    }

    public function getActiveThemeId(): string
    {
        $theme = oxNew(Theme::class);
        return (string) $theme->getActiveThemeId();
    }

    public function list(): array
    {
        $activeThemeIds = $this->getActiveThemeIds();

        $themes = [];
        foreach (oxNew(Theme::class)->getList() as $theme) {
            $id = (string) $theme->getId();
            $themes[] = new ThemeDataObject(
                $id,
                (string) $theme->getInfo('title'),
                (string) $theme->getInfo('version'),
                (string) $theme->getInfo('parentTheme'),
                in_array($id, $activeThemeIds, true)
            );
        }

        return $themes;
    }

    /**
     * @throws ThemeNotFoundException
     */
    private function loadTheme(string $themeId): Theme
    {
        $theme = oxNew(Theme::class);
        if (!$theme->load($themeId)) {
            throw new ThemeNotFoundException($themeId);
        }
        return $theme;
    }

    /**
     * @return string[]
     */
    private function getActiveThemeIds(): array
    {
        $config = Registry::getConfig();
        return array_values(array_filter([
            (string) $config->getConfigParam('sTheme'),
            (string) $config->getConfigParam('sCustomTheme'),
        ]));
    }
}
