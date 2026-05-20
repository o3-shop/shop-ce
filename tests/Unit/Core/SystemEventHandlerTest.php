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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface;
use OxidEsales\EshopCommunity\Core\SystemEventHandler;
use Psr\Log\NullLogger;

class SystemEventHandlerTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // validateOnline() catches and logs at ERROR level on failure;
        // route through a NullLogger so the testing-library doesn't fail
        // tests that exercise the catch path.
        Registry::set('logger', new NullLogger());
    }

    public function testOnAdminLoginIsNoopByDefault(): void
    {
        // Method is empty by design — call to confirm no side effects + cover the line.
        $this->assertNull((new SystemEventHandler())->onAdminLogin());
    }

    public function testOnShopStartDelegatesToValidateOffline(): void
    {
        $handler = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['validateOffline'])
            ->getMock();
        $handler->expects($this->once())->method('validateOffline');

        $handler->onShopStart();
    }

    public function testOnShopEndDelegatesToValidateOnline(): void
    {
        $handler = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['validateOnline'])
            ->getMock();
        $handler->expects($this->once())->method('validateOnline');

        $handler->onShopEnd();
    }

    public function testValidateOnlineCallsAdminUpdaterWhenInAdmin(): void
    {
        $service = $this->createMock(ApplicationServerServiceInterface::class);
        $service->expects($this->once())->method('updateAppServerInformationInAdmin');
        $service->expects($this->never())->method('updateAppServerInformationInFrontend');

        $config = $this->getMock(Config::class, ['isAdmin']);
        $config->expects($this->any())->method('isAdmin')->will($this->returnValue(true));

        $handler = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['getAppServerService', 'getConfig'])
            ->getMock();
        $handler->expects($this->any())->method('getAppServerService')->will($this->returnValue($service));
        $handler->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        $reflection = new \ReflectionMethod($handler, 'validateOnline');
        $reflection->setAccessible(true);
        $reflection->invoke($handler);
    }

    public function testValidateOnlineCallsFrontendUpdaterWhenNotInAdmin(): void
    {
        $service = $this->createMock(ApplicationServerServiceInterface::class);
        $service->expects($this->once())->method('updateAppServerInformationInFrontend');
        $service->expects($this->never())->method('updateAppServerInformationInAdmin');

        $config = $this->getMock(Config::class, ['isAdmin']);
        $config->expects($this->any())->method('isAdmin')->will($this->returnValue(false));

        $handler = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['getAppServerService', 'getConfig'])
            ->getMock();
        $handler->expects($this->any())->method('getAppServerService')->will($this->returnValue($service));
        $handler->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        $reflection = new \ReflectionMethod($handler, 'validateOnline');
        $reflection->setAccessible(true);
        $reflection->invoke($handler);
    }

    public function testValidateOnlineSwallowsExceptionFromService(): void
    {
        $service = $this->createMock(ApplicationServerServiceInterface::class);
        $service->method('updateAppServerInformationInFrontend')
            ->willThrowException(new \RuntimeException('appserver bridge down'));

        $config = $this->getMock(Config::class, ['isAdmin']);
        $config->expects($this->any())->method('isAdmin')->will($this->returnValue(false));

        $handler = $this->getMockBuilder(SystemEventHandler::class)
            ->onlyMethods(['getAppServerService', 'getConfig'])
            ->getMock();
        $handler->expects($this->any())->method('getAppServerService')->will($this->returnValue($service));
        $handler->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        $reflection = new \ReflectionMethod($handler, 'validateOnline');
        $reflection->setAccessible(true);
        // Must not propagate — caught and logged.
        $reflection->invoke($handler);
        $this->assertTrue(true);
    }

    public function testValidateOfflineIsNoopByDefault(): void
    {
        $handler = new SystemEventHandler();
        $reflection = new \ReflectionMethod($handler, 'validateOffline');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->invoke($handler));
    }

    public function testGetConfigReturnsRegistryConfig(): void
    {
        $handler = new SystemEventHandler();
        $reflection = new \ReflectionMethod($handler, 'getConfig');
        $reflection->setAccessible(true);
        $this->assertSame(Registry::getConfig(), $reflection->invoke($handler));
    }

    public function testGetAppServerServiceWiresUpRequiredDependencies(): void
    {
        // The real method news up an ApplicationServerDao, UtilsServer and
        // ApplicationServerService — exercising the wiring is enough to
        // cover the method body.
        $handler = new SystemEventHandler();
        $reflection = new \ReflectionMethod($handler, 'getAppServerService');
        $reflection->setAccessible(true);
        $service = $reflection->invoke($handler);

        $this->assertInstanceOf(
            \OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface::class,
            $service
        );
    }
}
