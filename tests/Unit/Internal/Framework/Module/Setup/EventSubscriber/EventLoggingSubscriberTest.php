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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\EventSubscriber;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\ServicesYamlConfigurationErrorEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\EventSubscriber\EventLoggingSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class EventLoggingSubscriberTest extends TestCase
{
    public function testLogConfigurationErrorWritesAtErrorLevelWithBothErrorAndPath(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::ERROR),
                $this->stringContains('class missing'),
            );

        $subscriber = new EventLoggingSubscriber($logger);
        $subscriber->logConfigurationError(
            new ServicesYamlConfigurationErrorEvent('class missing', '/path/services.yaml')
        );
    }

    public function testLoggedMessageIncludesConfigurationFilePath(): void
    {
        $captured = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willReturnCallback(function ($level, $message) use (&$captured) {
            $captured = $message;
        });

        $subscriber = new EventLoggingSubscriber($logger);
        $subscriber->logConfigurationError(
            new ServicesYamlConfigurationErrorEvent('msg', '/etc/services.yaml')
        );

        $this->assertStringContainsString('msg', (string) $captured);
        $this->assertStringContainsString('/etc/services.yaml', (string) $captured);
    }

    public function testGetSubscribedEventsBindsLogConfigurationErrorToTheConfigurationErrorEvent(): void
    {
        $subscribed = EventLoggingSubscriber::getSubscribedEvents();
        $this->assertSame(
            ['logConfigurationError'],
            array_values($subscribed),
            'EventLoggingSubscriber must hook the configuration-error event to logConfigurationError.'
        );
        $this->assertArrayHasKey(
            ServicesYamlConfigurationErrorEvent::class,
            $subscribed
        );
    }
}
