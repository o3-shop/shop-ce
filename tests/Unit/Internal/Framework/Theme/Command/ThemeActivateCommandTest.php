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

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeActivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeActivateCommandTest extends TestCase
{
    public function testActivatesGivenTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->expects($this->once())->method('activate')->with('wave');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'wave']);

        $this->assertStringContainsString('was activated', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testResolvesConfiguredThemeWhenNoArgumentGiven(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('getActiveThemeId')->willReturn('o3-theme');
        $bridge->expects($this->once())->method('activate')->with('o3-theme');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute([]);

        $this->assertStringContainsString('o3-theme', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testFailsWhenNoArgumentAndNoConfiguredTheme(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('getActiveThemeId')->willReturn('');
        $bridge->expects($this->never())->method('activate');

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsThemeNotFound(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('activate')->willThrowException(new ThemeNotFoundException('ghost'));

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'ghost']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testReportsActivationError(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('activate')->willThrowException(new StandardException('EXCEPTION_PARENT_VERSION_MISMATCH'));

        $tester = new CommandTester(new ThemeActivateCommand($bridge));
        $tester->execute(['theme-id' => 'child']);

        $this->assertStringContainsString('EXCEPTION_PARENT_VERSION_MISMATCH', $tester->getDisplay());
        $this->assertSame(1, $tester->getStatusCode());
    }
}
