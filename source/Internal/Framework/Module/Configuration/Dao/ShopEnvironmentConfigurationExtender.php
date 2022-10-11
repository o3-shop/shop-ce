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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShopEnvironmentConfigurationExtender implements ShopConfigurationExtenderInterface
{
    /** @var ShopEnvironmentConfigurationDaoInterface */
    private $shopEnvironmentConfigurationDao;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var int */
    private $shopId;

    public function __construct(
        ShopEnvironmentConfigurationDaoInterface $shopEnvironmentConfigurationDao,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->shopEnvironmentConfigurationDao = $shopEnvironmentConfigurationDao;
        $this->eventDispatcher = $eventDispatcher;
    }

    /** @inheritDoc */
    public function getExtendedConfiguration(int $shopId, array $shopConfiguration): array
    {
        $this->shopId = $shopId;
        $environmentData = $this->shopEnvironmentConfigurationDao->get($this->shopId);
        $environmentConfiguration = $this->filterEnvironmentData($shopConfiguration, $environmentData);
        return \array_replace_recursive($shopConfiguration, $environmentConfiguration);
    }

    private function filterEnvironmentData(array $shopConfiguration, array $environmentData): array
    {
        if (empty($environmentData['modules'])) {
            return [];
        }
        unset($environmentData['moduleChains']);
        foreach ($environmentData['modules'] as $moduleId => $moduleConfiguration) {
            if (!isset($shopConfiguration['modules'][$moduleId])) {
                unset($environmentData['modules'][$moduleId]);
                continue;
            }
            if (isset($moduleConfiguration['moduleSettings'])) {
                foreach (\array_keys($moduleConfiguration['moduleSettings']) as $settingId) {
                    if (!isset($shopConfiguration['modules'][$moduleId]['moduleSettings'][$settingId])) {
                        unset($environmentData['modules'][$moduleId]['moduleSettings'][$settingId]);
                        $this->processOrphanSetting($moduleId, $settingId);
                    }
                }
            }
        }
        return $environmentData;
    }

    private function processOrphanSetting(string $moduleId, string $orphanSettingId): void
    {
        $this->eventDispatcher->dispatch(
            ShopEnvironmentWithOrphanSettingEvent::NAME,
            new ShopEnvironmentWithOrphanSettingEvent($this->shopId, $moduleId, $orphanSettingId)
        );
    }
}
