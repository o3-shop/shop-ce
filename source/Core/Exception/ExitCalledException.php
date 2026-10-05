<?php

namespace OxidEsales\EshopCommunity\Core\Exception;

/**
 * Thrown by the test ExitHandler in place of a real exit().
 * Do not catch in production code — production installs the default ExitHandler,
 * which terminates the process normally.
 */
class ExitCalledException extends \RuntimeException
{
    private ?string $exitMessage;

    public function __construct(int $code = 0, ?string $message = null)
    {
        parent::__construct(
            sprintf('ExitHandler::exit() called (code=%d, message=%s)', $code, $message ?? '<null>'),
            $code
        );
        $this->exitMessage = $message;
    }

    public function getExitMessage(): ?string
    {
        return $this->exitMessage;
    }
}
