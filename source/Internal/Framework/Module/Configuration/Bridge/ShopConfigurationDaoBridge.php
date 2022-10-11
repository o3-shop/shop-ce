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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopEnvironmentConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class ShopConfigurationDaoBridge implements ShopConfigurationDaoBridgeInterface
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var ShopEnvironmentConfigurationDaoInterface
     */
    private $shopEnvironmentConfigurationDao;

    /**
     * ShopConfigurationDaoBridge constructor.
     *
     * @param ContextInterface                         $context
     * @param ShopConfigurationDaoInterface            $shopConfigurationDao
     * @param ShopEnvironmentConfigurationDaoInterface $shopEnvironmentConfigurationDao
     */
    public function __construct(
        ContextInterface $context,
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ShopEnvironmentConfigurationDaoInterface $shopEnvironmentConfigurationDao
    ) {
        $this->context = $context;
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->shopEnvironmentConfigurationDao = $shopEnvironmentConfigurationDao;
    }

    /**
     * @return ShopConfiguration
     */
    public function get(): ShopConfiguration
    {
        return $this->shopConfigurationDao->get(
            $this->context->getCurrentShopId()
        );
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     */
    public function save(ShopConfiguration $shopConfiguration)
    {
        $this->shopConfigurationDao->save(
            $shopConfiguration,
            $this->context->getCurrentShopId()
        );

        $this->shopEnvironmentConfigurationDao->remove($this->context->getCurrentShopId());
    }
}
