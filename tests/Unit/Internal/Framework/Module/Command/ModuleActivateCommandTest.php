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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ModuleActivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleActivateCommandTest extends TestCase
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

    public function testActivatesInstalledModule(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('activate')
            ->with('mymodule', 1);

        $command = new ModuleActivateCommand($this->makeDao(true), $this->makeContext(1), $activationService);

        $tester = new CommandTester($command);
        $tester->execute(['module-id' => 'mymodule']);

        $this->assertStringContainsString('was activated', $tester->getDisplay());
    }

    public function testReportsModuleNotFoundForUninstalledModule(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->never())->method('activate');

        $command = new ModuleActivateCommand($this->makeDao(false), $this->makeContext(), $activationService);

        $tester = new CommandTester($command);
        $tester->execute(['module-id' => 'mymodule']);

        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testReportsAlreadyActiveWhenActivationServiceThrowsModuleSetupException(): void
    {
        $activationService = $this->createMock(ModuleActivationServiceInterface::class);
        $activationService->expects($this->once())
            ->method('activate')
            ->willThrowException(new ModuleSetupException('already active'));

        $command = new ModuleActivateCommand($this->makeDao(true), $this->makeContext(), $activationService);

        $tester = new CommandTester($command);
        $tester->execute(['module-id' => 'mymodule']);

        $this->assertStringContainsString('already active', $tester->getDisplay());
    }

    public function testMessageConstantsAreFormattableWithModuleId(): void
    {
        $this->assertStringContainsString('%s', ModuleActivateCommand::MESSAGE_MODULE_ACTIVATED);
        $this->assertStringContainsString('%s', ModuleActivateCommand::MESSAGE_MODULE_ALREADY_ACTIVE);
        $this->assertStringContainsString('%s', ModuleActivateCommand::MESSAGE_MODULE_NOT_FOUND);
    }
}
