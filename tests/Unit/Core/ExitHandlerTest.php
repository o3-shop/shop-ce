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

    /**
     * Regression guard for o3-shop/o3-shop#166.
     *
     * ExitHandlerInterface is an interface, so class_exists() returns false
     * for it — interface_exists() is the correct probe. bootstrap.php relies
     * on this when deciding whether to register the default ExitHandler; the
     * wrong probe leaves it unregistered and breaks the fresh-install Setup
     * redirect with a DatabaseNotConfiguredException loop.
     */
    public function testExitHandlerInterfaceIsDetectedByInterfaceExistsNotClassExists()
    {
        $interface = \OxidEsales\Eshop\Core\ExitHandlerInterface::class;

        $this->assertTrue(
            interface_exists($interface),
            'unified-namespace ExitHandlerInterface must resolve as an interface'
        );
        $this->assertFalse(
            class_exists($interface),
            'ExitHandlerInterface is an interface; class_exists() is always false for it (see #166)'
        );
    }

    /**
     * Regression guard for o3-shop/o3-shop#166: bootstrap.php must probe the
     * ExitHandlerInterface with interface_exists() before registering the
     * default ExitHandler. Probing with class_exists() (as shipped in
     * shop-ce#156) is always false for an interface, so the handler is never
     * registered. This asserts the source guard directly — it would have
     * failed on the broken fix.
     */
    public function testBootstrapRegistersDefaultExitHandlerViaInterfaceExists()
    {
        $bootstrap = file_get_contents(dirname(__DIR__, 3) . '/source/bootstrap.php');

        $this->assertStringNotContainsString(
            'class_exists(\OxidEsales\Eshop\Core\ExitHandlerInterface::class)',
            $bootstrap,
            'bootstrap.php must not guard ExitHandler registration with class_exists() on the interface (#166)'
        );
        $this->assertStringContainsString(
            'interface_exists(\OxidEsales\Eshop\Core\ExitHandlerInterface::class)',
            $bootstrap,
            'bootstrap.php must guard ExitHandler registration with interface_exists() (#166)'
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
