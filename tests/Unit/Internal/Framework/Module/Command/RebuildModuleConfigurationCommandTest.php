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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\RebuildModuleConfigurationCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleConfigurationMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RebuildModuleConfigurationCommandTest extends TestCase
{
    private string $tmpModulesPath = '';

    protected function setUp(): void
    {
        $this->tmpModulesPath = sys_get_temp_dir() . '/o3-rebuild-' . uniqid();
        mkdir($this->tmpModulesPath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpModulesPath);
    }

    public function testCommandName(): void
    {
        $command = $this->makeCommand($this->createMock(ShopConfigurationDaoInterface::class));
        $this->assertSame('oe:module:rebuild-configuration', $command->getName());
    }

    public function testPhantomModuleIsRemovedAndShopConfigIsSaved(): void
    {
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration(
            (new ModuleConfiguration())->setId('phantom')->setPath('vendor/phantom')
        );

        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);
        $shopConfigDao->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(fn (ShopConfiguration $c) => !$c->hasModuleConfiguration('phantom')),
                1
            );

        (new CommandTester($this->makeCommand($shopConfigDao)))->execute([]);
    }

    public function testOnDiskModuleIsMergedViaServiceAndSaved(): void
    {
        mkdir($this->tmpModulesPath . '/vendor/mymodule', 0777, true);
        file_put_contents($this->tmpModulesPath . '/vendor/mymodule/metadata.php', '<?php');

        $shopConfig = new ShopConfiguration();
        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);
        $shopConfigDao->expects($this->once())->method('save');

        $freshMetadata = (new ModuleConfiguration())->setId('mymodule');
        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->expects($this->once())
            ->method('get')
            ->with($this->tmpModulesPath . '/vendor/mymodule')
            ->willReturn($freshMetadata);

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->expects($this->once())
            ->method('merge')
            ->with($shopConfig, $freshMetadata)
            ->willReturn($shopConfig);

        $exitCode = (new CommandTester(
            $this->makeCommand($shopConfigDao, null, $metadataDao, $mergingService)
        ))->execute([]);

        $this->assertSame(0, $exitCode);
    }

    public function testDryRunNeitherPrunesNorSaves(): void
    {
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration(
            (new ModuleConfiguration())->setId('phantom')->setPath('vendor/phantom')
        );

        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);
        $shopConfigDao->expects($this->never())->method('save');

        (new CommandTester($this->makeCommand($shopConfigDao)))->execute(['--dry-run' => true]);

        $this->assertTrue($shopConfig->hasModuleConfiguration('phantom'), 'dry-run must not modify in-memory config');
    }

    public function testOutputListsPrunedModuleIdAndPath(): void
    {
        $shopConfig = new ShopConfiguration();
        $shopConfig->addModuleConfiguration(
            (new ModuleConfiguration())->setId('phantom')->setPath('vendor/phantom')
        );

        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);

        $tester = new CommandTester($this->makeCommand($shopConfigDao));
        $tester->execute([]);

        $this->assertStringContainsString('phantom', $tester->getDisplay());
        $this->assertStringContainsString('vendor/phantom', $tester->getDisplay());
    }

    public function testOutputReportsKeptCount(): void
    {
        mkdir($this->tmpModulesPath . '/vendor/mymodule', 0777, true);
        file_put_contents($this->tmpModulesPath . '/vendor/mymodule/metadata.php', '<?php');

        $shopConfig = new ShopConfiguration();
        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);

        $freshMetadata = (new ModuleConfiguration())->setId('mymodule');
        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($freshMetadata);

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->method('merge')->willReturn($shopConfig);

        $tester = new CommandTester(
            $this->makeCommand($shopConfigDao, null, $metadataDao, $mergingService)
        );
        $tester->execute([]);

        $this->assertStringContainsString('Kept: 1', $tester->getDisplay());
    }

    public function testOnDiskModulePathIsSetRelativeToModulesDir(): void
    {
        mkdir($this->tmpModulesPath . '/vendor/mymodule', 0777, true);
        file_put_contents($this->tmpModulesPath . '/vendor/mymodule/metadata.php', '<?php');

        $shopConfig = new ShopConfiguration();
        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);

        $freshMetadata = (new ModuleConfiguration())->setId('mymodule');
        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willReturn($freshMetadata);

        $mergingService = $this->createMock(ModuleConfigurationMergingServiceInterface::class);
        $mergingService->expects($this->once())
            ->method('merge')
            ->with(
                $shopConfig,
                $this->callback(fn (ModuleConfiguration $m) => $m->getPath() === 'vendor/mymodule')
            )
            ->willReturn($shopConfig);

        (new CommandTester(
            $this->makeCommand($shopConfigDao, null, $metadataDao, $mergingService)
        ))->execute([]);
    }

    public function testModulesWithInvalidMetadataAreSkipped(): void
    {
        mkdir($this->tmpModulesPath . '/vendor/broken', 0777, true);
        file_put_contents($this->tmpModulesPath . '/vendor/broken/metadata.php', '<?php');

        $shopConfig = new ShopConfiguration();
        $shopConfigDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigDao->method('getAll')->willReturn([1 => $shopConfig]);
        $shopConfigDao->expects($this->once())->method('save');

        $metadataDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $metadataDao->method('get')->willThrowException(new \RuntimeException('invalid metadata'));

        $exitCode = (new CommandTester(
            $this->makeCommand($shopConfigDao, null, $metadataDao, null)
        ))->execute([]);

        $this->assertSame(0, $exitCode);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function makeCommand(
        ShopConfigurationDaoInterface $shopConfigDao,
        ?BasicContextInterface $context = null,
        ?ModuleConfigurationDaoInterface $metadataDao = null,
        ?ModuleConfigurationMergingServiceInterface $mergingService = null
    ): RebuildModuleConfigurationCommand {
        return new RebuildModuleConfigurationCommand(
            $shopConfigDao,
            $context ?? $this->makeContext(),
            $metadataDao ?? $this->createMock(ModuleConfigurationDaoInterface::class),
            $mergingService ?? $this->createMock(ModuleConfigurationMergingServiceInterface::class)
        );
    }

    private function makeContext(): BasicContextInterface
    {
        $ctx = $this->createMock(BasicContextInterface::class);
        $ctx->method('getModulesPath')->willReturn($this->tmpModulesPath);
        return $ctx;
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
