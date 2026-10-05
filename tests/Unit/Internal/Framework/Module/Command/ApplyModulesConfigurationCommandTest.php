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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ApplyModulesConfigurationCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

class ApplyModulesConfigurationCommandTest extends TestCase
{
    private function makeModuleConfiguration(string $id, bool $configured): ModuleConfiguration
    {
        $config = $this->createMock(ModuleConfiguration::class);
        $config->method('getId')->willReturn($id);
        $config->method('isConfigured')->willReturn($configured);
        return $config;
    }

    private function makeShopConfiguration(array $moduleConfigs): ShopConfiguration
    {
        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('getModuleConfigurations')->willReturn($moduleConfigs);
        return $shopConfig;
    }

    private function buildCommand(
        ShopConfigurationDaoInterface $dao,
        ModuleActivationServiceInterface $activationService,
        ModuleStateServiceInterface $stateService
    ): ApplyModulesConfigurationCommand {
        $command = new ApplyModulesConfigurationCommand($dao, $activationService, $stateService);
        // Add the option so CommandTester can see hasOption('shop-id') / getOption('shop-id').
        $command->setDefinition(new InputDefinition([
            new InputOption('shop-id', null, InputOption::VALUE_OPTIONAL),
        ]));
        return $command;
    }

    public function testIteratesAllShopsWhenNoShopIdOptionGiven(): void
    {
        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->expects($this->once())->method('getAll')->willReturn([
            1 => $this->makeShopConfiguration([]),
            2 => $this->makeShopConfiguration([]),
        ]);
        $dao->expects($this->never())->method('get');

        $command = $this->buildCommand(
            $dao,
            $this->createMock(ModuleActivationServiceInterface::class),
            $this->createMock(ModuleStateServiceInterface::class)
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('shop with id 1', $tester->getDisplay());
        $this->assertStringContainsString('shop with id 2', $tester->getDisplay());
    }

    public function testTargetsSingleShopWhenShopIdOptionProvided(): void
    {
        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->expects($this->once())->method('get')->with(7)->willReturn(
            $this->makeShopConfiguration([])
        );
        $dao->expects($this->never())->method('getAll');

        $command = $this->buildCommand(
            $dao,
            $this->createMock(ModuleActivationServiceInterface::class),
            $this->createMock(ModuleStateServiceInterface::class)
        );

        $tester = new CommandTester($command);
        $tester->execute(['--shop-id' => '7']);

        $this->assertStringContainsString('shop with id 7', $tester->getDisplay());
    }

    public function testDeactivatesNotConfiguredButActiveModule(): void
    {
        $module = $this->makeModuleConfiguration('mod-x', false); // !isConfigured
        $shopConfig = $this->makeShopConfiguration([$module]);

        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->method('getAll')->willReturn([1 => $shopConfig]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(true);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->once())->method('deactivate')->with('mod-x', 1);
        $activationService->expects($this->never())->method('activate');

        $tester = new CommandTester($this->buildCommand($dao, $activationService, $stateService));
        $tester->execute([]);
    }

    public function testReactivatesConfiguredAndActiveModule(): void
    {
        $module = $this->makeModuleConfiguration('mod-y', true);
        $shopConfig = $this->makeShopConfiguration([$module]);

        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->method('getAll')->willReturn([1 => $shopConfig]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(true);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        // The configured-and-active branch performs deactivate-then-activate.
        $activationService->expects($this->once())->method('deactivate')->with('mod-y', 1);
        $activationService->expects($this->once())->method('activate')->with('mod-y', 1);

        $tester = new CommandTester($this->buildCommand($dao, $activationService, $stateService));
        $tester->execute([]);
    }

    public function testActivatesConfiguredButInactiveModule(): void
    {
        $module = $this->makeModuleConfiguration('mod-z', true);
        $shopConfig = $this->makeShopConfiguration([$module]);

        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->method('getAll')->willReturn([1 => $shopConfig]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(false);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->never())->method('deactivate');
        $activationService->expects($this->once())->method('activate')->with('mod-z', 1);

        $tester = new CommandTester($this->buildCommand($dao, $activationService, $stateService));
        $tester->execute([]);
    }

    public function testCatchesAndDisplaysExceptionFromActivationService(): void
    {
        $module = $this->makeModuleConfiguration('mod-fail', true);
        $shopConfig = $this->makeShopConfiguration([$module]);

        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->method('getAll')->willReturn([1 => $shopConfig]);

        $stateService = $this->createMock(ModuleStateServiceInterface::class);
        $stateService->method('isActive')->willReturn(false);

        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->method('activate')
            ->willThrowException(new \RuntimeException('something broke'));

        $tester = new CommandTester($this->buildCommand($dao, $activationService, $stateService));
        $tester->execute([]);

        $this->assertStringContainsString("wasn't applied", $tester->getDisplay());
        $this->assertStringContainsString('something broke', $tester->getDisplay());
    }
}
