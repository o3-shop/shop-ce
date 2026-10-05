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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\InstallModuleConfigurationCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InstallModuleConfigurationCommandTest extends TestCase
{
    private string $tmpRoot = '';
    private string $modulesPath = '';

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir() . '/o3-install-' . uniqid();
        $this->modulesPath = $this->tmpRoot . '/modules';
        mkdir($this->modulesPath . '/mymodule', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpRoot);
    }

    private function makeContext(): BasicContextInterface
    {
        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getShopRootPath')->willReturn($this->tmpRoot);
        $context->method('getModulesPath')->willReturn($this->modulesPath);
        return $context;
    }

    public function testCommandNameAndDescription(): void
    {
        $command = new InstallModuleConfigurationCommand(
            $this->createMock(ModuleConfigurationInstallerInterface::class),
            $this->makeContext()
        );
        $this->assertSame('oe:module:install-configuration', $command->getName());
    }

    public function testInstallsConfigurationWithExplicitTargetPath(): void
    {
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->expects($this->once())
            ->method('install')
            ->with($this->modulesPath . '/mymodule', $this->modulesPath . '/mymodule');

        $command = new InstallModuleConfigurationCommand($installer, $this->makeContext());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'module-source-path' => $this->modulesPath . '/mymodule',
            'module-target-path' => $this->modulesPath . '/mymodule',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('been installed', $tester->getDisplay());
    }

    public function testReportsTargetPathRequiredWhenSourceIsOutsideShopModules(): void
    {
        // Create a directory OUTSIDE modulesPath.
        $outsidePath = $this->tmpRoot . '/elsewhere';
        mkdir($outsidePath, 0777, true);

        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->expects($this->never())->method('install');

        $command = new InstallModuleConfigurationCommand($installer, $this->makeContext());
        $tester = new CommandTester($command);

        // Single arg → the command tries to derive target from source. Source is
        // outside modulesPath → throws ModuleTargetPathIsMissingException → human
        // error message.
        $tester->execute(['module-source-path' => $outsidePath]);

        $this->assertStringContainsString(
            InstallModuleConfigurationCommand::MESSAGE_TARGET_PATH_IS_REQUIRED,
            $tester->getDisplay()
        );
    }

    public function testInstallsWithImplicitTargetWhenSourceIsInsideShopModules(): void
    {
        // Source IS inside modulesPath → target is auto-derived from source.
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->expects($this->once())
            ->method('install')
            ->with($this->modulesPath . '/mymodule', $this->modulesPath . '/mymodule');

        $command = new InstallModuleConfigurationCommand($installer, $this->makeContext());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'module-source-path' => $this->modulesPath . '/mymodule',
        ]);
        $this->assertSame(0, $exitCode);
    }

    public function testRethrowsAndReportsForUnexpectedException(): void
    {
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->method('install')
            ->willThrowException(new \RuntimeException('disk full'));

        $command = new InstallModuleConfigurationCommand($installer, $this->makeContext());
        $tester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        try {
            $tester->execute([
                'module-source-path' => $this->modulesPath . '/mymodule',
                'module-target-path' => $this->modulesPath . '/mymodule',
            ]);
        } finally {
            $this->assertStringContainsString(
                InstallModuleConfigurationCommand::MESSAGE_INSTALLATION_FAILED,
                $tester->getDisplay()
            );
        }
    }

    public function testThrowsInvalidArgumentExceptionForNonExistentSourcePath(): void
    {
        $installer = $this->createMock(ModuleConfigurationInstallerInterface::class);
        $installer->expects($this->never())->method('install');

        $command = new InstallModuleConfigurationCommand($installer, $this->makeContext());
        $tester = new CommandTester($command);

        $this->expectException(\Throwable::class);
        $tester->execute([
            'module-source-path' => $this->tmpRoot . '/does/not/exist',
            'module-target-path' => $this->modulesPath . '/mymodule',
        ]);
    }

    private function rmrf(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->rmrf($path . '/' . $entry);
        }
        rmdir($path);
    }
}
