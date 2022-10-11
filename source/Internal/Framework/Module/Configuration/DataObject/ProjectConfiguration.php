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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

use DomainException;

class ProjectConfiguration
{
    /** @var ShopConfiguration[] */
    private $projectConfiguration = [];

    /**
     * @param int $shopId
     *
     * @return ShopConfiguration
     */
    public function getShopConfiguration(int $shopId): ShopConfiguration
    {
        if (array_key_exists($shopId, $this->projectConfiguration)) {
            return $this->projectConfiguration[$shopId];
        }
        throw new DomainException('There is no configuration for shop id ' . $shopId);
    }

    /**
     * @return array
     */
    public function getShopConfigurations(): array
    {
        return $this->projectConfiguration;
    }

    /**
     * @return array
     */
    public function getShopConfigurationIds(): array
    {
        return array_keys($this->projectConfiguration);
    }

    /**
     * @param int               $shopId
     * @param ShopConfiguration $shopConfiguration
     */
    public function addShopConfiguration(int $shopId, ShopConfiguration $shopConfiguration): void
    {
        $this->projectConfiguration[$shopId] = $shopConfiguration;
    }

    /**
     * @param int $shopId
     *
     * @throws DomainException
     */
    public function deleteShopConfiguration(int $shopId): void
    {
        if (array_key_exists($shopId, $this->projectConfiguration)) {
            unset($this->projectConfiguration[$shopId]);
        } else {
            throw new DomainException('There is no configuration for shop id ' . $shopId);
        }
    }
}
