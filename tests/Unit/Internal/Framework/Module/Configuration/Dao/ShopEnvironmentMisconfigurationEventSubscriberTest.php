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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentMisconfigurationEventSubscriber;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShopEnvironmentMisconfigurationEventSubscriberTest extends TestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentMisconfigurationEventSubscriber::logOrphanSetting
     */
    public function testLogOrphanSettingWritesAWarningWithEventContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('non-existing module setting'),
                [
                    'shopId' => 5,
                    'moduleId' => 'mymodule',
                    'settingId' => 'orphanedSetting',
                ]
            );

        $subscriber = new ShopEnvironmentMisconfigurationEventSubscriber($logger);
        $event = new ShopEnvironmentWithOrphanSettingEvent(5, 'mymodule', 'orphanedSetting');

        $subscriber->logOrphanSetting($event);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentMisconfigurationEventSubscriber::getSubscribedEvents
     */
    public function testGetSubscribedEventsBindsOrphanSettingEventToLoggerHandler(): void
    {
        $subscribed = ShopEnvironmentMisconfigurationEventSubscriber::getSubscribedEvents();

        $this->assertSame(
            [ShopEnvironmentWithOrphanSettingEvent::NAME => 'logOrphanSetting'],
            $subscribed
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent::__construct
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent::getShopId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent::getModuleId
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentWithOrphanSettingEvent::getSettingId
     */
    public function testEventGettersExposeConstructorArguments(): void
    {
        $event = new ShopEnvironmentWithOrphanSettingEvent(11, 'modX', 'settingY');
        $this->assertSame(11, $event->getShopId());
        $this->assertSame('modX', $event->getModuleId());
        $this->assertSame('settingY', $event->getSettingId());
        // The constant is the FQCN itself.
        $this->assertSame(ShopEnvironmentWithOrphanSettingEvent::class, ShopEnvironmentWithOrphanSettingEvent::NAME);
    }
}
