<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ActiveClassExtensionChainResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ClassExtensionChainService;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ClassExtensionChainServiceTest extends TestCase
{
    use ContainerTrait;

    public function testUpdateChain()
    {
        $activeClassExtensionChain = new ClassExtensionsChain();
        $activeClassExtensionChain->setChain(
            [
                'shopClassNamespace' => [
                    'activeModule2ExtensionClass',
                    'activeModuleExtensionClass',
                ],
                'anotherShopClassNamespace' => [
                    'activeModuleExtensionClass',
                    'activeModule2ExtensionClass',
                ],
            ]
        );

        $activeClassExtensionChainResolver = $this
            ->getMockBuilder(ActiveClassExtensionChainResolverInterface::class)
            ->getMock();

        $activeClassExtensionChainResolver
            ->method('getActiveExtensionChain')
            ->willReturn($activeClassExtensionChain);

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting
            ->setName(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN)
            ->setShopId(1)
            ->setType(ShopSettingType::ASSOCIATIVE_ARRAY)
            ->setValue(
                [
                    'shopClassNamespace'        => 'activeModule2ExtensionClass&activeModuleExtensionClass',
                    'anotherShopClassNamespace' => 'activeModuleExtensionClass&activeModule2ExtensionClass',
                ]
            );

        $shopConfigurationSettingDao = $this->get(ShopConfigurationSettingDaoInterface::class);

        $classExtensionChainService = new ClassExtensionChainService(
            $shopConfigurationSettingDao,
            $activeClassExtensionChainResolver
        );

        $classExtensionChainService->updateChain(1);

        $this->assertEquals(
            $shopConfigurationSetting,
            $shopConfigurationSettingDao->get(ShopConfigurationSetting::MODULE_CLASS_EXTENSIONS_CHAIN, 1)
        );
    }
}
