<?php

/**
 * Compatibility shim for OXID 7's ContainerFacade.
 * o3-shop is based on OXID 6.x and does not ship this class.
 * Modules built for OXID 7 (e.g. unzerdev/oxid7) use ContainerFacade::get()
 * as a static shortcut to the DI container.
 */

namespace OxidEsales\EshopCommunity\Core\Di;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;

class ContainerFacade
{
    public static function get(string $serviceId): mixed
    {
        return ContainerFactory::getInstance()->getContainer()->get($serviceId);
    }
}