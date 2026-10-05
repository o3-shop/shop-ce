<?php

namespace OxidEsales\EshopCommunity\Core;

class ExitHandler implements \OxidEsales\Eshop\Core\ExitHandlerInterface
{
    public function exit(int $code = 0, ?string $message = null): void
    {
        if ($message !== null && $message !== '') {
            echo $message;
        }
        exit($code);
    }
}
