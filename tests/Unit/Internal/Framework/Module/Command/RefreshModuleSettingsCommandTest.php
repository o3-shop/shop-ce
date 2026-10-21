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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\RefreshModuleSettingsCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleConfigurationMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationRefreshServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Covers the writing behaviour of oe:module:refresh-settings --from-metadata: metadata is
 * merged for every module first and the project configuration is written exactly once,
 * after a timestamped backup; a module with broken metadata is skipped without aborting
 * the run.
 *
 * @internal
 */
final class RefreshModuleSettingsCommandTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/o3-refresh-settings-' . uniqid();
        mkdir($this->tmpDir . '/var/configuration/shops', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpDir);
    }

    public function testRefreshFromMetadataSavesOnceAndBacksUpBeforeWriting(): void
    {
        file_put_contents($this->tmpDir . '/var/configuration/shops/1.yaml', 'modules: [mymodule]');

        $projectConfiguration = $this->makeProjectConfiguration(['mymodule']);

        $projectConfigurationDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectConfigurationDao->method('getConfiguration')->willReturn($projectConfiguration);
        $projectConfigurationDao->expects($this->once())->method('save')->with($projectConfiguration);

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->expects($this->once())->method('merge')->willReturn(
            $projectConfiguration->getShopConfiguration(1)
        );

        $refreshService = $this->createMock(ModuleConfigurationRefreshServiceInterface::class);
        $refreshService->expects($this->once())->method('refresh');

        $tester = $this->makeTester(
            $projectConfigurationDao,
            $mergingService,
            $this->makeMetadataDao(),
            $refreshService
        );
        $tester->execute(['--from-metadata' => true], ['interactive' => false]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Refreshed settings of mymodule in shop 1.', $tester->getDisplay());
        $this->assertStringContainsString('Backed up ', $tester->getDisplay());

        $backups = glob($this->tmpDir . '/var/configuration/shops.bak.*/1.yaml');
        $this->assertCount(1, $backups, 'Expected exactly one backup for the whole run');
        $this->assertSame('modules: [mymodule]', file_get_contents($backups[0]));
    }

    public function testRefreshFromMetadataSkipsBrokenModuleAndRefreshesTheOthers(): void
    {
        $projectConfiguration = $this->makeProjectConfiguration(['good', 'bad']);

        $projectConfigurationDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectConfigurationDao->method('getConfiguration')->willReturn($projectConfiguration);
        $projectConfigurationDao->expects($this->once())->method('save');

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->expects($this->once())->method('merge')->willReturn(
            $projectConfiguration->getShopConfiguration(1)
        );

        $refreshService = $this->createMock(ModuleConfigurationRefreshServiceInterface::class);
        $refreshService->expects($this->once())->method('refresh');

        $tester = $this->makeTester(
            $projectConfigurationDao,
            $mergingService,
            $this->makeMetadataDao('bad'),
            $refreshService
        );
        $tester->execute(['--from-metadata' => true], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Skipped bad in shop 1', $tester->getDisplay());
        $this->assertStringContainsString('Skipped 1 module(s)', $tester->getDisplay());
        $this->assertStringContainsString('Refreshed settings of good in shop 1.', $tester->getDisplay());
    }

    public function testRefreshFromMetadataWritesNothingWhenEveryModuleFails(): void
    {
        file_put_contents($this->tmpDir . '/var/configuration/shops/1.yaml', 'modules: [bad]');

        $projectConfiguration = $this->makeProjectConfiguration(['bad']);

        $projectConfigurationDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectConfigurationDao->method('getConfiguration')->willReturn($projectConfiguration);
        $projectConfigurationDao->expects($this->never())->method('save');

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->expects($this->never())->method('merge');

        $refreshService = $this->createMock(ModuleConfigurationRefreshServiceInterface::class);
        $refreshService->expects($this->never())->method('refresh');

        $tester = $this->makeTester(
            $projectConfigurationDao,
            $mergingService,
            $this->makeMetadataDao('bad'),
            $refreshService
        );
        $tester->execute(['--from-metadata' => true], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Skipped 1 module(s)', $tester->getDisplay());
        $this->assertCount(0, glob($this->tmpDir . '/var/configuration/shops.bak.*'));
    }

    public function testRefreshWithoutFromMetadataDoesNotWriteProjectConfiguration(): void
    {
        $projectConfiguration = $this->makeProjectConfiguration(['mymodule']);

        $projectConfigurationDao = $this->createMock(ProjectConfigurationDaoInterface::class);
        $projectConfigurationDao->method('getConfiguration')->willReturn($projectConfiguration);
        $projectConfigurationDao->expects($this->never())->method('save');

        $refreshService = $this->createMock(ModuleConfigurationRefreshServiceInterface::class);
        $refreshService->expects($this->once())->method('refresh');

        $tester = $this->makeTester(
            $projectConfigurationDao,
            $this->createMock(ModuleConfigurationMergingServiceInterface::class),
            $this->makeMetadataDao(),
            $refreshService
        );
        $tester->execute(['module-id' => 'mymodule'], ['interactive' => false]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @param string|null $brokenModuleId module whose metadata read must fail
     */
    private function makeMetadataDao(?string $brokenModuleId = null): ModuleConfigurationDaoInterface
    {
        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturnCallback(
            function (string $modulePath) use ($brokenModuleId): ModuleConfiguration {
                if ($brokenModuleId !== null && str_ends_with($modulePath, '/' . $brokenModuleId)) {
                    throw new \InvalidArgumentException('metadata.php could not be read');
                }

                return $this->makeModuleConfiguration(basename($modulePath));
            }
        );

        return $metadataDao;
    }

    /**
     * @param string[] $moduleIds
     */
    private function makeProjectConfiguration(array $moduleIds): ProjectConfiguration
    {
        $shopConfiguration = new ShopConfiguration();
        foreach ($moduleIds as $moduleId) {
            $shopConfiguration->addModuleConfiguration($this->makeModuleConfiguration($moduleId));
        }

        $projectConfiguration = new ProjectConfiguration();
        $projectConfiguration->addShopConfiguration(1, $shopConfiguration);

        return $projectConfiguration;
    }

    private function makeModuleConfiguration(string $moduleId): ModuleConfiguration
    {
        return (new ModuleConfiguration())
            ->setId($moduleId)
            ->setPath($moduleId)
            ->setVersion('1.0')
            ->setConfigured(true);
    }

    private function makeTester(
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ModuleConfigurationMergingServiceInterface $mergingService,
        ModuleConfigurationDaoInterface $metadataDao,
        ModuleConfigurationRefreshServiceInterface $refreshService
    ): CommandTester {
        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(true);

        $pathResolver = $this->createMock(ModulePathResolverInterface::class);
        $pathResolver->method('getFullModulePathFromConfiguration')
            ->willReturnCallback(fn (string $moduleId): string => $this->tmpDir . '/modules/' . $moduleId);

        $context = $this->createMock(BasicContextInterface::class);
        $context->method('getAllShopIds')->willReturn([1]);
        $context->method('getProjectConfigurationDirectory')
            ->willReturn($this->tmpDir . '/var/configuration/');

        $command = new RefreshModuleSettingsCommand(
            $projectConfigurationDao,
            $mergingService,
            $stateService,
            $refreshService,
            $pathResolver,
            $metadataDao,
            $context
        );

        return new CommandTester($command);
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
