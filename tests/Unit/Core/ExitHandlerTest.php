<?php

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\ExitHandler;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Tests for ExitHandler and ExitHandlerTestTrait.
 *
 * Note: ExitHandler::exit() calls PHP's exit() unconditionally, which terminates
 * the process. It cannot be called in-process during a unit test. The real-exit
 * behaviour is exercised by integration tests or manual QA. The tests here cover:
 *   1. Class structure (interface implementation).
 *   2. The test-side fake handler provided by ExitHandlerTestTrait.
 */
class ExitHandlerTest extends UnitTestCase
{
    use \OxidEsales\EshopCommunity\Tests\Unit\ExitHandlerTestTrait;

    public function testInterfaceIsImplemented()
    {
        $this->assertInstanceOf(
            \OxidEsales\Eshop\Core\ExitHandlerInterface::class,
            new ExitHandler()
        );
    }

    public function testFakeHandlerFromTraitThrowsExitCalledException()
    {
        $this->installFakeExitHandler();

        $handler = \OxidEsales\Eshop\Core\Registry::get(
            \OxidEsales\Eshop\Core\ExitHandlerInterface::class
        );

        try {
            $handler->exit(42, 'boom');
            $this->fail('Expected ExitCalledException');
        } catch (\OxidEsales\Eshop\Core\Exception\ExitCalledException $e) {
            $this->assertSame(42, $e->getCode());
            $this->assertSame('boom', $e->getExitMessage());
        }
    }
}
