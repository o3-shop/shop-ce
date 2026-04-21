<?php

namespace OxidEsales\EshopCommunity\Core;

/**
 * Abstraction over PHP's exit() so tests can intercept termination.
 * Production is wired to the default ExitHandler in source/bootstrap.php.
 * Tests swap a fake via Registry::set() that throws ExitCalledException.
 */
interface ExitHandlerInterface
{
    /**
     * Terminate the application. Implementations MUST NOT return.
     *
     * @param int         $code    exit status passed to exit()
     * @param string|null $message optional message echoed before exit (e.g. 404 body)
     *
     * @return never
     */
    public function exit(int $code = 0, ?string $message = null): void;
}
