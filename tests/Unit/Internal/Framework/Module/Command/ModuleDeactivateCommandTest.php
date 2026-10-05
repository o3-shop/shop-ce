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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ModuleDeactivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleDeactivateCommandTest extends TestCase
{
    private function makeContext(int $shopId = 1): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getCurrentShopId')->willReturn($shopId);
        return $context;
    }

    private function makeDao(bool $hasModule): ShopConfigurationDaoInterface
    {
        $shopConfig = $this->createMock(ShopConfiguration::class);
        $shopConfig->method('hasModuleConfiguration')->willReturn($hasModule);

        $dao = $this->createMock(ShopConfigurationDaoInterface::class);
        $dao->method('get')->willReturn($shopConfig);
        return $dao;
    }

    public function testDeactivatesInstalledModule(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('deactivate')
            ->with('mymodule', 1);

        $command = new ModuleDeactivateCommand($this->makeDao(true), $this->makeContext(1), $activationService);

        $tester = new CommandTester($command);
        $tester->execute([ModuleDeactivateCommand::ARGUMENT_MODULE_ID => 'mymodule']);

        $this->assertStringContainsString('has been deactivated', $tester->getDisplay());
    }

    public function testReportsModuleNotFoundForUninstalledModule(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->never())->method('deactivate');

        $command = new ModuleDeactivateCommand($this->makeDao(false), $this->makeContext(), $activationService);

        $tester = new CommandTester($command);
        $tester->execute([ModuleDeactivateCommand::ARGUMENT_MODULE_ID => 'unknown']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testReportsCannotDeactivateWhenServiceThrowsModuleSetupException(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('deactivate')
            ->willThrowException(new ModuleSetupException('not active'));

        $command = new ModuleDeactivateCommand($this->makeDao(true), $this->makeContext(), $activationService);

        $tester = new CommandTester($command);
        $tester->execute([ModuleDeactivateCommand::ARGUMENT_MODULE_ID => 'mymodule']);

        $this->assertStringContainsString('not possible to deactivate', $tester->getDisplay());
    }

    public function testArgumentNameConstantIsModuleId(): void
    {
        $this->assertSame('module-id', ModuleDeactivateCommand::ARGUMENT_MODULE_ID);
    }
}
