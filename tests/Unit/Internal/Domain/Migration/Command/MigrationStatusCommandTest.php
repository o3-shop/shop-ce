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

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Command\MigrationStatusCommand;
use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\MigrationStateServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MigrationStatusCommandTest extends TestCase
{
    public function testReportsUpToDateSchema(): void
    {
        $service = $this->createMock(MigrationStateServiceInterface::class);
        $service->method('isTracked')->willReturn(true);
        $service->method('getCurrentVersion')->willReturn('20260427090000');
        $service->method('getLatestAvailableVersion')->willReturn('20260427090000');
        $service->method('getExecutedVersions')->willReturn(['20230322213324', '20260427090000']);
        $service->method('getAvailableVersions')->willReturn(['20230322213324', '20260427090000']);
        $service->method('getPendingVersions')->willReturn([]);
        $service->method('getUnknownVersions')->willReturn([]);

        $tester = new CommandTester(new MigrationStatusCommand($service));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationStatusCommand::EXIT_OK, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Current applied version: 20260427090000', $display);
        $this->assertStringContainsString('schema is up to date', $display);
    }

    public function testListsPendingMigrations(): void
    {
        $service = $this->createMock(MigrationStateServiceInterface::class);
        $service->method('isTracked')->willReturn(true);
        $service->method('getCurrentVersion')->willReturn('20230322213324');
        $service->method('getLatestAvailableVersion')->willReturn('20260427090000');
        $service->method('getExecutedVersions')->willReturn(['20230322213324']);
        $service->method('getAvailableVersions')->willReturn(['20230322213324', '20260427090000']);
        $service->method('getPendingVersions')->willReturn(['20260427090000']);
        $service->method('getUnknownVersions')->willReturn([]);

        $tester = new CommandTester(new MigrationStatusCommand($service));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationStatusCommand::EXIT_OK, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Pending migrations: 1', $display);
        $this->assertStringContainsString('- 20260427090000', $display);
    }

    public function testWarnsWhenTrackingTableMissing(): void
    {
        $service = $this->createMock(MigrationStateServiceInterface::class);
        $service->method('isTracked')->willReturn(false);
        $service->method('getCurrentVersion')->willReturn(null);
        $service->method('getLatestAvailableVersion')->willReturn('20260427090000');
        $service->method('getExecutedVersions')->willReturn([]);
        $service->method('getAvailableVersions')->willReturn(['20260427090000']);
        $service->method('getPendingVersions')->willReturn(['20260427090000']);
        $service->method('getUnknownVersions')->willReturn([]);

        $tester = new CommandTester(new MigrationStatusCommand($service));
        $exit = $tester->execute([]);

        $this->assertSame(MigrationStatusCommand::EXIT_OK, $exit);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('has not been migrated yet', $display);
        $this->assertStringContainsString('Current applied version: (none)', $display);
    }
}
