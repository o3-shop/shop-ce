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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;

class ClassExtensionChainService implements ExtensionChainServiceInterface
{
    /**
     * @var ShopConfigurationSettingDaoInterface
     */
    private $shopConfigurationSettingDao;

    /**
     * @var ActiveClassExtensionChainResolverInterface
     */
    private $activeClassExtensionChainResolver;

    /**
     * @param ShopConfigurationSettingDaoInterface       $shopConfigurationSettingDao
     * @param ActiveClassExtensionChainResolverInterface $activeClassExtensionChainResolver
     */
    public function __construct(
        ShopConfigurationSettingDaoInterface $shopConfigurationSettingDao,
        ActiveClassExtensionChainResolverInterface $activeClassExtensionChainResolver
    ) {
        $this->shopConfigurationSettingDao = $shopConfigurationSettingDao;
        $this->activeClassExtensionChainResolver = $activeClassExtensionChainResolver;
    }

    /**
     * @param int $shopId
     */
    public function updateChain(int $shopId)
    {
        $activeClassExtensionChain = $this->activeClassExtensionChainResolver->getActiveExtensionChain($shopId);
        $formattedClassExtensions = $this->formatClassExtensionChain($activeClassExtensionChain);

        $shopConfigurationSetting = $this->getClassExtensionChainShopConfigurationSetting($shopId);
        $shopConfigurationSetting->setValue($formattedClassExtensions);

        $this->shopConfigurationSettingDao->save($shopConfigurationSetting);
    }

    /**
     * @param ClassExtensionsChain $chain
     * @return array
     */
    private function formatClassExtensionChain(ClassExtensionsChain $chain): array
    {
        $classExtensions = [];

        foreach ($chain as $shopClass => $moduleExtensionClasses) {
            $classExtensions[$shopClass] = implode('&', $moduleExtensionClasses);
        }

        return $classExtensions;
    }

    /**
     * @param int $shopId
     * @return ShopConfigurationSetting
     */
    private function getClassExtensionChainShopConfigurationSetting(int $shopId): ShopConfigurationSetting
    {
        try {
            $shopConfigurationSetting = $this->shopConfigurationSettingDao->get(
                ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN,
                $shopId
            );
        } catch (EntryDoesNotExistDaoException $exception) {
            $shopConfigurationSetting = new ShopConfigurationSetting();
            $shopConfigurationSetting
                ->setShopId($shopId)
                ->setName(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN)
                ->setType(ShopSettingType::ASSOCIATIVE_ARRAY)
                ->setValue([]);
        }

        return $shopConfigurationSetting;
    }
}
