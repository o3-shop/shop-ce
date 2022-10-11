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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Command\UninstallModuleConfigurationCommand;
use Symfony\Component\Console\Input\ArrayInput;

final class UninstallModuleConfigurationCommandTest extends ModuleCommandsTestCase
{
    public function tearDown(): void
    {
        $this->cleanupTestData();
    }
    
    public function testRemoveModuleConfig(): void
    {
        $this->installTestModule();

        $consoleOutput = $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:uninstall-configuration', 'module-id' => $this->moduleId])
        );

        $this->assertSame(
            sprintf(UninstallModuleConfigurationCommand::MESSAGE_REMOVE_WAS_SUCCESSFULL, $this->moduleId) . PHP_EOL,
            $consoleOutput
        );
    }

    public function testRemoveModuleConfigWithFakeId(): void
    {
        $this->installTestModule();

        $consoleOutput = $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:uninstall-configuration', 'module-id' => 'whatsThis'])
        );

        $this->assertStringStartsWith(
            sprintf(UninstallModuleConfigurationCommand::MESSAGE_REMOVE_FAILED, 'whatsThis') . PHP_EOL,
            $consoleOutput
        );
    }
}
