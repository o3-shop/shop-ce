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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;

class ProjectConfigurationDataMapper implements ProjectConfigurationDataMapperInterface
{
    /**
     * @var ShopConfigurationDataMapperInterface
     */
    private $shopConfigurationDataMapper;

    /**
     * ProjectConfigurationDataMapper constructor.
     * @param ShopConfigurationDataMapperInterface $shopConfigurationDataMapper
     */
    public function __construct(ShopConfigurationDataMapperInterface $shopConfigurationDataMapper)
    {
        $this->shopConfigurationDataMapper = $shopConfigurationDataMapper;
    }

    /**
     * @param ProjectConfiguration $configuration
     * @return array
     */
    public function toData(ProjectConfiguration $configuration): array
    {
        $data = [];

        $data['shops'] = $this->getShopsConfigurationData($configuration);

        return $data;
    }

    /**
     * @param array $data
     * @return ProjectConfiguration
     */
    public function fromData(array $data): ProjectConfiguration
    {
        $projectConfiguration = new ProjectConfiguration();
        $this->setProjectConfiguration($projectConfiguration, $data);

        return $projectConfiguration;
    }

    /**
     * @param ProjectConfiguration $projectConfiguration
     * @param array                $data
     */
    private function setProjectConfiguration(ProjectConfiguration $projectConfiguration, array $data)
    {
        if (isset($data['shops'])) {
            $this->setShopsConfiguration($projectConfiguration, $data['shops']);
        }
    }

    /**
     * @param ProjectConfiguration $projectConfiguration
     * @param array                $shopsData
     */
    private function setShopsConfiguration(ProjectConfiguration $projectConfiguration, array $shopsData)
    {
        foreach ($shopsData as $shopId => $shopData) {
            $projectConfiguration->addShopConfiguration(
                $shopId,
                $this->shopConfigurationDataMapper->fromData($shopData)
            );
        }
    }

    /**
     * @param ProjectConfiguration $projectConfiguration
     *
     * @return array
     */
    private function getShopsConfigurationData(ProjectConfiguration $projectConfiguration): array
    {
        $data = [];

        foreach ($projectConfiguration->getShopConfigurations() as $shopId => $shopConfiguration) {
            $data[$shopId] = $this->shopConfigurationDataMapper->toData($shopConfiguration);
        }

        return $data;
    }
}
