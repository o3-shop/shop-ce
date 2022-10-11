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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Event;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\ContainerBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\ServicesYamlConfigurationErrorEvent;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use OxidEsales\TestingLibrary\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventLoggingSubscriberTest extends UnitTestCase
{
    /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
    private $container;

    private $testlog = __DIR__ . DIRECTORY_SEPARATOR . 'test.log';

    public function setup(): void
    {
        $containerBuilder = new ContainerBuilder(new BasicContextStub());
        $this->container = $containerBuilder->getContainer();
        $contextDefinition = $this->container->getDefinition(ContextInterface::class);
        $contextDefinition->setClass(ContextStub::class);
        $this->container->compile();
    }

    public function tearDown(): void
    {
        if (file_exists($this->testlog)) {
            unlink($this->testlog);
        }
    }

    public function testLoggingOnConfigurationErrorEvent()
    {
        /** @var ContextStub $context */
        $context = $this->container->get(ContextInterface::class);
        $context->setLogFilePath(__dir__ . DIRECTORY_SEPARATOR . 'test.log');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            ServicesYamlConfigurationErrorEvent::NAME,
            new ServicesYamlConfigurationErrorEvent('error', 'just/some/path/services.yaml')
        );

        $this->assertTrue(file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'test.log'));
    }
}
