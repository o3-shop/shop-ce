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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\UninstallModuleConfigurationCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UninstallModuleConfigurationCommandTest extends TestCase
{
    public function testCommandNameIsModuleUninstallConfiguration(): void
    {
        $command = new UninstallModuleConfigurationCommand(
            $this->createMock(ModuleConfigurationInstallerInterface::class)
        );
        $this->assertSame('oe:module:uninstall-configuration', $command->getName());
    }

    public function testUninstallByIdAndReportsSuccess(): void
    {
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->expects($this->once())
            ->method('uninstallById')
            ->with('mymodule');

        $tester = new CommandTester(new UninstallModuleConfigurationCommand($installer));
        $exitCode = $tester->execute(['module-id' => 'mymodule']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'configuration for module mymodule has been removed',
            $tester->getDisplay()
        );
    }

    public function testRethrowsAndReportsErrorOnInstallerException(): void
    {
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->method('uninstallById')
            ->willThrowException(new \RuntimeException('not installed'));

        $tester = new CommandTester(new UninstallModuleConfigurationCommand($installer));

        $this->expectException(\RuntimeException::class);
        try {
            $tester->execute(['module-id' => 'mymodule']);
        } finally {
            $this->assertStringContainsString(
                'error occurred while removing module mymodule',
                $tester->getDisplay()
            );
        }
    }
}
