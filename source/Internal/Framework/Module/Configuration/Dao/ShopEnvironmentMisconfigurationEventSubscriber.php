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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShopEnvironmentMisconfigurationEventSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /** @param ShopEnvironmentWithOrphanSettingEvent $event */
    public function logOrphanSetting(ShopEnvironmentWithOrphanSettingEvent $event): void
    {
        $this->logger->warning(
            'Environment configuration tries to change non-existing module setting. Environment value will be ignored',
            [
                'shopId' => $event->getShopId(),
                'moduleId' => $event->getModuleId(),
                'settingId' => $event->getSettingId()
            ]
        );
    }

    /** @return string[] */
    public static function getSubscribedEvents(): array
    {
        return [ShopEnvironmentWithOrphanSettingEvent::NAME => 'logOrphanSetting'];
    }
}
