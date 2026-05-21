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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Exception\RoutingException;
use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\Eshop\Core\Registry;
use Psr\Log\AbstractLogger;

class ShopControlTest extends \OxidTestCase
{
    use \OxidEsales\EshopCommunity\Tests\Unit\ExitHandlerTestTrait;

    /** @var string|false */
    private $originalEnvValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnvValue = $_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL'] ?? false;
        unset($_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL']);
    }

    protected function tearDown(): void
    {
        if ($this->originalEnvValue === false) {
            unset($_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL']);
        } else {
            $_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL'] = $this->originalEnvValue;
        }
        parent::tearDown();
    }

    public function testHandleAccessDeniedExceptionRoutesThroughExitHandler()
    {
        $this->installFakeExitHandler();

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $exception = new \OxidEsales\Eshop\Core\Exception\AccessDeniedException('nope');

        try {
            $control->handleAccessDeniedException($exception);
            $this->fail('Expected ExitCalledException');
        } catch (\OxidEsales\Eshop\Core\Exception\ExitCalledException $e) {
            $this->assertSame('nope', $e->getExitMessage());
            $this->assertSame(0, $e->getCode());
        }
    }

    public function testHandleRoutingExceptionLogsAtErrorLevelByDefault()
    {
        $logger = $this->makeCaptureLogger();
        Registry::set('logger', $logger);

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->handleRoutingException(new RoutingException('no-such-key'));

        $this->assertCount(1, $logger->captured);
        $this->assertSame('error', $logger->captured[0]['level']);
    }

    public function testHandleRoutingExceptionLogsAtConfiguredLevel()
    {
        $_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL'] = 'warning';

        $logger = $this->makeCaptureLogger();
        Registry::set('logger', $logger);

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->handleRoutingException(new RoutingException('no-such-key'));

        $this->assertCount(1, $logger->captured);
        $this->assertSame('warning', $logger->captured[0]['level']);
    }

    /** @dataProvider validLogLevels */
    public function testHandleRoutingExceptionAcceptsAllPsr3Levels(string $level)
    {
        $_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL'] = $level;

        $logger = $this->makeCaptureLogger();
        Registry::set('logger', $logger);

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->handleRoutingException(new RoutingException('no-such-key'));

        $this->assertSame($level, $logger->captured[0]['level']);
    }

    public function validLogLevels(): array
    {
        return [
            ['debug'], ['info'], ['notice'], ['warning'],
            ['error'], ['critical'], ['alert'], ['emergency'],
        ];
    }

    public function testHandleRoutingExceptionFallsBackToErrorForInvalidLevel()
    {
        $_ENV['O3SHOP_CONF_UNKNOWN_CONTROLLER_LOG_LEVEL'] = 'not-a-level';

        $logger = $this->makeCaptureLogger();
        Registry::set('logger', $logger);

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->handleRoutingException(new RoutingException('no-such-key'));

        $levels = array_column($logger->captured, 'level');
        $this->assertContains('notice', $levels, 'Expected a notice about the invalid level.');
        $this->assertContains('error', $levels, 'Expected the routing failure logged at error (fallback).');
    }

    public function testHandleRoutingExceptionMessageContainsControllerKey()
    {
        $logger = $this->makeCaptureLogger();
        Registry::set('logger', $logger);

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->handleRoutingException(new RoutingException('my-bad-key'));

        $this->assertStringContainsString('my-bad-key', $logger->captured[0]['message']);
    }

    public function testHandleSystemExceptionSkipsDebugOutWhenRoutingWasAlreadyHandled()
    {
        $this->installFakeExitHandler();

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);
        $control->setNonPublicVar('routingExceptionHandled', true);

        $exception = $this->getMockBuilder(SystemComponentException::class)->getMock();
        $exception->expects($this->never())->method('debugOut');

        try {
            $control->_handleSystemException($exception);
        } catch (\OxidEsales\Eshop\Core\Exception\ExitCalledException $e) {
            // redirect triggers exit — that is expected in production mode
        }
    }

    public function testHandleSystemExceptionCallsDebugOutWhenNotARoutingFailure()
    {
        $this->installFakeExitHandler();

        $control = $this->getProxyClass(\OxidEsales\Eshop\Core\ShopControl::class);

        $exception = $this->getMockBuilder(SystemComponentException::class)->getMock();
        $exception->expects($this->once())->method('debugOut');

        try {
            $control->_handleSystemException($exception);
        } catch (\OxidEsales\Eshop\Core\Exception\ExitCalledException $e) {
            // redirect triggers exit — that is expected in production mode
        }
    }

    // -------------------------------------------------------------------------

    private function makeCaptureLogger(): object
    {
        return new class () extends AbstractLogger {
            public array $captured = [];

            public function log($level, $message, array $context = []): void
            {
                $this->captured[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }
}
