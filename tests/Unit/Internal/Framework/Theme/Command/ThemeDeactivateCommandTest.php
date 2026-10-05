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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeDeactivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeDeactivateCommandTest extends TestCase
{
    public function testDeactivatesActiveChildTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->with('child')->willReturn(true);

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'child']);

        $this->assertStringContainsString('was deactivated', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testReportsNotActiveWhenNoOp(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willReturn(false);

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'wave']);

        $this->assertStringContainsString('not active', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testRefusesToDeactivateBaseTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willThrowException(new CannotDeactivateThemeException('o3-theme'));

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'o3-theme']);

        $this->assertStringContainsString('Cannot deactivate base theme', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsThemeNotFound(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('deactivate')->willThrowException(new ThemeNotFoundException('ghost'));

        $tester = new CommandTester(new ThemeDeactivateCommand($bridge));
        $tester->execute(['theme-id' => 'ghost']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }
}
