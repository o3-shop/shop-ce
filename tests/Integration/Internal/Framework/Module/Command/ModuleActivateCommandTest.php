<?php declare(strict_types=1);
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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Command;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ModuleActivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use Symfony\Component\Console\Input\ArrayInput;

final class ModuleActivateCommandTest extends ModuleCommandsTestCase
{
    public function testModuleActivation(): void
    {
        $this->installTestModule();

        $consoleOutput = $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:activate', 'module-id' => $this->moduleId])
        );

        $this->assertSame(
            sprintf(ModuleActivateCommand::MESSAGE_MODULE_ACTIVATED, $this->moduleId) . PHP_EOL,
            $consoleOutput
        );

        $module = oxNew(Module::class);
        $module->load($this->moduleId);
        $this->assertTrue($module->isActive());

        $this->cleanupTestData();
    }

    public function testWhenModuleAlreadyActive(): void
    {
        $this->installTestModule();

        $this->get(ModuleActivationBridgeInterface::class)->activate($this->moduleId, 1);

        $consoleOutput = $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:activate', 'module-id' => $this->moduleId])
        );

        $this->assertSame(
            sprintf(ModuleActivateCommand::MESSAGE_MODULE_ALREADY_ACTIVE, $this->moduleId) . PHP_EOL,
            $consoleOutput
        );

        $this->cleanupTestData();
    }

    public function testNonExistingModuleActivation(): void
    {
        $moduleId = 'test';
        $consoleOutput = $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:activate', 'module-id' => $moduleId])
        );

        $this->assertSame(
            sprintf(ModuleActivateCommand::MESSAGE_MODULE_NOT_FOUND, $moduleId) . PHP_EOL,
            $consoleOutput
        );
    }
}
