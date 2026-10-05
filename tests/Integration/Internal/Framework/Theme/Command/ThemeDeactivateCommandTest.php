<?php

declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Registry;

final class ThemeDeactivateCommandTest extends ThemeCommandsTestCase
{
    public function testDeactivatesActiveCustomThemeAndClearsIt(): void
    {
        $this->setActiveTheme('o3-theme', 'wave');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('was deactivated', $output);
        $this->assertSame('', (string) Registry::getConfig()->getConfigParam('sCustomTheme'));
    }

    public function testRefusesToDeactivateBaseTheme(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('Cannot deactivate base theme', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testReportsNotActiveForInactiveTheme(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'o3-theme']);

        $this->assertStringContainsString('not active', $output);
    }

    public function testReportsNotFoundForUnknownTheme(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:deactivate', 'theme-id' => 'does-not-exist']);

        $this->assertStringContainsString('not found', $output);
    }
}
