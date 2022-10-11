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
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use OxidEsales\Facts\Facts;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShopAwareEventsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $container;

    /**
     * @var EventDispatcherInterface $dispatcher
     */
    private $dispatcher;

    public function setup(): void
    {
        $context = $this->getMockBuilder(BasicContext::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getSourcePath',
                'getCommunityEditionSourcePath',
                'getConfigurationDirectoryPath',
                'getGeneratedServicesFilePath',
            ])->getMock();

        $context->method('getCommunityEditionSourcePath')->willReturn(
            (new Facts)->getCommunityEditionSourcePath()
        );
        $context->method('getGeneratedServicesFilePath')->willReturn(__DIR__ . '/generated_project.yaml');
        $context->method('getConfigurationDirectoryPath')->willReturn(__DIR__);

        $builder = $this->makeContainerBuilder($context);
        $this->container = $builder->getContainer();
        $definition = $this->container->getDefinition(ContextInterface::class);
        $definition->setClass(ContextStub::class);

        $this->container->compile();

        $this->dispatcher = $this->container->get(EventDispatcherInterface::class);
    }

    /**
     * All three subscribers are active for shop 1, current shop is 1
     * but propagation is stopped after the second handler, so
     * we should have 2 active event handlers
     */
    public function testShopActivatedEvent()
    {
        /**
         * @var $event TestEvent
         */
        $event = $this->dispatcher->dispatch('oxidesales.testevent', new TestEvent());
        $this->assertEquals(2, $event->getNumberOfActiveHandlers());
    }

    /**
     * Only the second and third subscriber are active for shop 2, current shop is 2
     * but propagation is stopped after the second handler, so
     * we should have 1 active event handler
     */
    public function testShopNotActivatedEvent()
    {
        /**
         * @var ContextStub $contextStub
         */
        $contextStub = $this->container->get(ContextInterface::class);
        $contextStub->setCurrentShopId(2);
        /**
         * @var $event TestEvent
         */
        $event = $this->dispatcher->dispatch('oxidesales.testevent', new TestEvent());
        $this->assertEquals(1, $event->getNumberOfActiveHandlers());
    }

    /**
     * @param BasicContext $context
     * @return ContainerBuilder
     */
    private function makeContainerBuilder(BasicContext $context): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder($context);
        return $containerBuilder;
    }
}
