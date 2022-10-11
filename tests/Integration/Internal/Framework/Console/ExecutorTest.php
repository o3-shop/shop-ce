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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Console;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\ContainerBuilder;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Framework\Console\ExecutorInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class ExecutorTest extends TestCase
{
    use ConsoleTrait;
    use ContainerTrait;

    public function testIfRegisteredCommandInList()
    {
        $output = $this->executeCommand('list');

        $this->assertRegExp('/oe:tests:test-command/', $this->getOutputFromStream($output));
    }

    public function testCommandExecution()
    {
        $output = $this->executeCommand('oe:tests:test-command');

        $this->assertSame('Command have been executed!'.PHP_EOL, $this->getOutputFromStream($output));
    }

    public function testCommandWithChangedNameExecution()
    {
        $output = $this->executeCommand('oe:tests:test-command-changed-name');

        $this->assertSame('Command have been executed!'.PHP_EOL, $this->getOutputFromStream($output));
    }

    /**
     * @return ExecutorInterface
     */
    private function makeExecutor(): ExecutorInterface
    {
        $context = $this
            ->getMockBuilder(BasicContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGeneratedServicesFilePath'])
            ->getMock();
        $context->method('getGeneratedServicesFilePath')->willReturn(__DIR__ . '/Fixtures/generated_project.yaml');

        $containerBuilder = new ContainerBuilder($context);

        $container = $containerBuilder->getContainer();
        $definition = $container->getDefinition('oxid_esales.console.symfony.component.console.application');
        $definition->addMethodCall('setAutoExit', [false]);

        $container->compile();

        return $container->get(ExecutorInterface::class);
    }

    /**
     * @param StreamOutput $output
     * @return bool|string
     */
    private function getOutputFromStream($output)
    {
        $stream = $output->getStream();
        rewind($stream);
        $display = stream_get_contents($stream);
        return $display;
    }

    /**
     * @param string $command
     * @return StreamOutput
     */
    private function executeCommand(string $command): StreamOutput
    {
        $executor = $this->makeExecutor();
        $output = new StreamOutput(fopen('php://memory', 'w', false));
        $executor->execute(new ArrayInput(['command' => $command]), $output);

        return $output;
    }
}
