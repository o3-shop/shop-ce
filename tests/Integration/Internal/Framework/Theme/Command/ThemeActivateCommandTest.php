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

final class ThemeActivateCommandTest extends ThemeCommandsTestCase
{
    public function testActivatesRealThemeAndWritesConfig(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:activate', 'theme-id' => 'wave']);

        $this->assertStringContainsString('was activated', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testActivatesConfiguredThemeWhenNoArgument(): void
    {
        $this->setActiveTheme('wave', '');

        $output = $this->runCommand(['command' => 'oe:theme:activate']);

        $this->assertStringContainsString('wave', $output);
        $this->assertSame('wave', (string) Registry::getConfig()->getConfigParam('sTheme'));
    }

    public function testReportsNotFoundForUnknownTheme(): void
    {
        $output = $this->runCommand(['command' => 'oe:theme:activate', 'theme-id' => 'does-not-exist']);

        $this->assertStringContainsString('not found', $output);
    }
}
