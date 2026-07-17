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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Migration\Command;

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Command\MigrationVerifyCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\ComposerLockInspectorInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\MigrationStateServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MigrationVerifyCommandTest extends TestCase
{
    private function makeCommand(
        MigrationStateServiceInterface $state,
        ComposerLockInspectorInterface $lock
    ): MigrationVerifyCommand {
        return new MigrationVerifyCommand($state, $lock);
    }

    public function testPassesWhenEverythingIsGreen(): void
    {
        $state = $this->createMock(MigrationStateServiceInterface::class);
        $state->method('isTracked')->willReturn(true);
        $state->method('getPendingVersions')->willReturn([]);

        $lock = $this->createMock(ComposerLockInspectorInterface::class);
        $lock->method('exists')->willReturn(true);
        $lock->method('findInstalledPackagesByPrefix')->willReturn([]);

        $tester = new CommandTester($this->makeCommand($state, $lock));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationVerifyCommand::EXIT_OK, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Migration verification passed.', $display);
        $this->assertStringNotContainsString('[FAIL]', $display);
    }

    public function testFailsWhenTrackingTableMissing(): void
    {
        $state = $this->createMock(MigrationStateServiceInterface::class);
        $state->method('isTracked')->willReturn(false);
        $state->method('getPendingVersions')->willReturn([]);

        $lock = $this->createMock(ComposerLockInspectorInterface::class);
        $lock->method('exists')->willReturn(true);
        $lock->method('findInstalledPackagesByPrefix')->willReturn([]);

        $tester = new CommandTester($this->makeCommand($state, $lock));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationVerifyCommand::EXIT_FAILED, $exit);
        $this->assertStringContainsString('The migrator has not run', $tester->getDisplay());
    }

    public function testFailsWhenMigrationsPending(): void
    {
        $state = $this->createMock(MigrationStateServiceInterface::class);
        $state->method('isTracked')->willReturn(true);
        $state->method('getPendingVersions')->willReturn(['20260427090000']);

        $lock = $this->createMock(ComposerLockInspectorInterface::class);
        $lock->method('exists')->willReturn(true);
        $lock->method('findInstalledPackagesByPrefix')->willReturn([]);

        $tester = new CommandTester($this->makeCommand($state, $lock));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationVerifyCommand::EXIT_FAILED, $exit);
        $this->assertStringContainsString('1 migration(s) still pending', $tester->getDisplay());
    }

    public function testFailsWhenUpstreamPackagesRemain(): void
    {
        $state = $this->createMock(MigrationStateServiceInterface::class);
        $state->method('isTracked')->willReturn(true);
        $state->method('getPendingVersions')->willReturn([]);

        $lock = $this->createMock(ComposerLockInspectorInterface::class);
        $lock->method('exists')->willReturn(true);
        $lock->method('findInstalledPackagesByPrefix')->willReturn(['oxid-esales/oxideshop-ce']);

        $tester = new CommandTester($this->makeCommand($state, $lock));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationVerifyCommand::EXIT_FAILED, $exit);
        $this->assertStringContainsString('oxid-esales/oxideshop-ce', $tester->getDisplay());
    }

    public function testSkipsUpstreamCheckWhenNoComposerLock(): void
    {
        $state = $this->createMock(MigrationStateServiceInterface::class);
        $state->method('isTracked')->willReturn(true);
        $state->method('getPendingVersions')->willReturn([]);

        $lock = $this->createMock(ComposerLockInspectorInterface::class);
        $lock->method('exists')->willReturn(false);
        $lock->expects($this->never())->method('findInstalledPackagesByPrefix');

        $tester = new CommandTester($this->makeCommand($state, $lock));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationVerifyCommand::EXIT_OK, $exit);
        $this->assertStringContainsString('[SKIP]', $tester->getDisplay());
    }
}
