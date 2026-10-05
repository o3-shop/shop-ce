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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Console;

use OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider\CommandsProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ExecutorTest extends TestCase
{
    public function testShopIdParameterOptionConstantValue(): void
    {
        $this->assertSame('shop-id', Executor::SHOP_ID_PARAMETER_OPTION_NAME);
    }

    public function testExecuteAddsCommandsAndRunsApplication(): void
    {
        $command = new class () extends Command {
            public bool $executed = false;
            public function configure(): void
            {
                $this->setName('o3:test:noop');
            }
            protected function execute($input, $output): int
            {
                $this->executed = true;
                return 0;
            }
        };

        $provider = $this->createMock(CommandsProviderInterface::class);
        $provider->expects($this->once())
            ->method('getCommands')
            ->willReturn([$command]);

        $application = new Application();
        // Symfony's Application::run normally calls exit(); disable that
        // so the test process keeps running and we can assert afterwards.
        $application->setAutoExit(false);

        $executor = new Executor($application, $provider);
        $executor->execute(new ArrayInput(['command' => 'o3:test:noop']), new BufferedOutput());

        $this->assertTrue($command->executed);
    }
}
