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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Migration\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Repository\ExecutedMigrationsRepositoryInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\MigrationStateService;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;

final class MigrationStateServiceTest extends TestCase
{
    private string $sourcePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sourcePath = sys_get_temp_dir() . '/o3-migration-state-' . uniqid('', true);
        mkdir($this->sourcePath . '/migration/data', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->sourcePath . '/migration/data/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->sourcePath . '/migration/data');
        @rmdir($this->sourcePath . '/migration');
        @rmdir($this->sourcePath);
        parent::tearDown();
    }

    private function shipMigrations(string ...$versions): void
    {
        foreach ($versions as $version) {
            file_put_contents(
                $this->sourcePath . '/migration/data/Version' . $version . '.php',
                '<?php // fixture'
            );
        }
        // A non-matching file must be ignored.
        file_put_contents($this->sourcePath . '/migration/data/migrations.yml', 'name: fixture');
    }

    private function makeService(array $executed): MigrationStateService
    {
        $repository = $this->createMock(ExecutedMigrationsRepositoryInterface::class);
        $repository->method('getExecutedVersions')->willReturn($executed);
        $repository->method('tableExists')->willReturn($executed !== []);

        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getSourcePath')->willReturn($this->sourcePath);

        return new MigrationStateService($repository, $context);
    }

    public function testAvailableVersionsAreParsedFromFilenamesAndSorted(): void
    {
        $this->shipMigrations('20230405094126', '20230322213324', '20260427090000');
        $service = $this->makeService([]);

        $this->assertSame(
            ['20230322213324', '20230405094126', '20260427090000'],
            $service->getAvailableVersions()
        );
    }

    public function testPendingVersionsAreAvailableMinusExecuted(): void
    {
        $this->shipMigrations('20230322213324', '20230405094126', '20260427090000');
        $service = $this->makeService(['20230322213324']);

        $this->assertSame(['20230405094126', '20260427090000'], $service->getPendingVersions());
    }

    public function testNoPendingVersionsWhenEverythingApplied(): void
    {
        $this->shipMigrations('20230322213324', '20230405094126');
        $service = $this->makeService(['20230405094126', '20230322213324']);

        $this->assertSame([], $service->getPendingVersions());
    }

    public function testUnknownVersionsAreExecutedMinusAvailable(): void
    {
        $this->shipMigrations('20230322213324');
        $service = $this->makeService(['20230322213324', '99999999999999']);

        $this->assertSame(['99999999999999'], $service->getUnknownVersions());
    }

    public function testCurrentAndLatestVersions(): void
    {
        $this->shipMigrations('20230322213324', '20260427090000');
        $service = $this->makeService(['20230322213324']);

        $this->assertSame('20230322213324', $service->getCurrentVersion());
        $this->assertSame('20260427090000', $service->getLatestAvailableVersion());
    }

    public function testCurrentVersionIsNullWhenNothingApplied(): void
    {
        $this->shipMigrations('20230322213324');
        $service = $this->makeService([]);

        $this->assertNull($service->getCurrentVersion());
        $this->assertFalse($service->isTracked());
    }
}
