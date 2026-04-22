<?php

namespace OxidEsales\EshopCommunity\Tests\Unit;

use OxidEsales\Eshop\Core\Exception\ExitCalledException;
use OxidEsales\Eshop\Core\ExitHandlerInterface;
use OxidEsales\Eshop\Core\Registry;

/**
 * Use in any unit test that exercises code paths which call exit().
 * Call installFakeExitHandler() in your test body (before invoking the SUT)
 * and optionally restoreRealExitHandler() in tearDown if your test suite does
 * not already null out Registry entries between tests.
 */
trait ExitHandlerTestTrait
{
    protected function installFakeExitHandler(): void
    {
        Registry::set(
            ExitHandlerInterface::class,
            new class () implements ExitHandlerInterface {
                public function exit(int $code = 0, ?string $message = null): void
                {
                    throw new ExitCalledException($code, $message);
                }
            }
        );
    }

    protected function restoreRealExitHandler(): void
    {
        Registry::set(
            ExitHandlerInterface::class,
            new \OxidEsales\Eshop\Core\ExitHandler()
        );
    }
}
