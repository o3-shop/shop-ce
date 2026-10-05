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
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Command\ThemeListCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\DataObject\ThemeDataObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ThemeListCommandTest extends TestCase
{
    public function testListsThemesWithActiveFlag(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('list')->willReturn([
            new ThemeDataObject('o3-theme', 'O3-Theme', '1.5.0', '', true),
            new ThemeDataObject('wave', 'Wave', '1.2.3', '', false),
        ]);

        $tester = new CommandTester(new ThemeListCommand($bridge));
        $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('o3-theme', $display);
        $this->assertStringContainsString('O3-Theme', $display);
        $this->assertStringContainsString('1.5.0', $display);
        $this->assertStringContainsString('wave', $display);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testHandlesEmptyThemeList(): void
    {
        $bridge = $this->createMock(ThemeBridgeInterface::class);
        $bridge->method('list')->willReturn([]);

        $tester = new CommandTester(new ThemeListCommand($bridge));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
