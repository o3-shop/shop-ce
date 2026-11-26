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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache\TemplateCacheServiceInterface;

/**
 * Links the Twig fixture theme (Fixtures/views/twigfixture) into the shop's views dir and switches themes.
 */
trait FixtureThemeTrait
{
    /** @var string */
    private $themeLink;

    /** @var array */
    private $originalThemes;

    /** @var array */
    private $originalServer;

    private function installFixtureTheme(): void
    {
        // A storefront GET request, as the web server would describe it.
        $this->originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $config = Registry::getConfig();
        $this->originalThemes = [$config->getConfigParam('sTheme'), $config->getConfigParam('sCustomTheme')];

        // The shop reads themes from its views dir only, so the fixture theme is linked in for the test.
        $this->themeLink = $config->getViewsDir() . 'twigfixture';
        $this->removeThemeLink();
        symlink(__DIR__ . '/Fixtures/views/twigfixture', $this->themeLink);

        $this->activateTheme('twigfixture', '');
    }

    private function uninstallFixtureTheme(): void
    {
        $this->activateTheme((string) $this->originalThemes[0], (string) $this->originalThemes[1]);
        $this->removeThemeLink();
        $_SERVER = $this->originalServer;
    }

    /**
     * Twig's template directories and UtilsView's template dirs are resolved once, so both are rebuilt.
     */
    private function activateTheme(string $theme, string $customTheme): void
    {
        $config = Registry::getConfig();
        $config->setConfigParam('sTheme', $theme);
        $config->setConfigParam('sCustomTheme', $customTheme);

        Registry::set(UtilsView::class, null);
        ContainerFactory::resetContainer();
        ContainerFactory::getInstance()->getContainer()
            ->get(TemplateCacheServiceInterface::class)
            ->invalidateTemplateCache();
    }

    private function removeThemeLink(): void
    {
        if (is_link($this->themeLink)) {
            unlink($this->themeLink);
        }
    }
}
