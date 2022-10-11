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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Handler;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Controller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\ControllersModuleSettingHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ControllersModuleSettingHandlerTest extends TestCase
{
    public function testHandleConvertsModuleIdsAndControllerKeysLowercase(): void
    {
        $shopConfigurationSettingDaoMock = $this->getShopConfigurationSettingDaoMock();

        $this->getShopConfigurationSettingWithExistingModuleControllers($shopConfigurationSettingDaoMock);

        $settingHandler = new ControllersModuleSettingHandler($shopConfigurationSettingDaoMock);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('newmodule')
            ->addController(
                new Controller('firstControllerNewModule', '\NewModule\FirstClass')
            )->addController(
                new Controller('secondControllerNewModule', '\NewModule\SecondClass')
            );

        $settingHandler->handleOnModuleActivation($moduleConfiguration, 1);

        $this->assertSame(
            [
                'existingmodule' => [
                    'firstcontrollerexistingmodule'  => '\OldModule\FirstControllerClass',
                ],
                'newmodule' => [
                    'firstcontrollernewmodule'  => '\NewModule\FirstClass',
                    'secondcontrollernewmodule' => '\NewModule\SecondClass',
                ]
            ],
            $shopConfigurationSettingDaoMock->get(ShopConfigurationSetting::MODULE_CONTROLLERS, 1)->getValue()
        );
    }

    public function testHandleSavesEmptyShopConfigurationSettingIfNoControllersFound(): void
    {
        $shopConfigurationSettingDaoMock = $this->getShopConfigurationSettingDaoMock();

        $shopConfigurationSettingWithEmptyValue = $this->getShopConfigurationSettingWithEmptyValue();

        $shopConfigurationSettingDaoMock->method('get')->willReturn(
            $shopConfigurationSettingWithEmptyValue
        );

        $settingHandler = new ControllersModuleSettingHandler($shopConfigurationSettingDaoMock);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('newmodule')
            ->addController(new Controller('', ''));

        $settingHandler->handleOnModuleActivation($moduleConfiguration, 1);

        $shopConfigurationSettingWithEmptyValue->setValue(['newmodule' => []]);

        $shopConfigurationSetting =
            $shopConfigurationSettingDaoMock->get(ShopConfigurationSetting::MODULE_CONTROLLERS, 1);

        $this->assertEquals(
            $shopConfigurationSettingWithEmptyValue,
            $shopConfigurationSetting
        );
        $this->assertSame(
            $shopConfigurationSettingWithEmptyValue->getValue(),
            $shopConfigurationSetting->getValue()
        );
    }

    public function testHandlingOnModuleDeactivation(): void
    {
        $shopConfigurationSettingDaoMock = $this->getShopConfigurationSettingDaoMock();

        $moduleControllers = new ShopConfigurationSetting();
        $moduleControllers
            ->setName(ShopConfigurationSetting::MODULE_CONTROLLERS)
            ->setShopId(1)
            ->setType(ShopSettingType::ARRAY)
            ->setValue(['existingmodule' => ['controller' => 'someNamespace']]);

        $shopConfigurationSettingDaoMock
            ->method('get')
            ->willReturn($moduleControllers);

        $settingHandler = new ControllersModuleSettingHandler($shopConfigurationSettingDaoMock);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('existingmodule');

        $settingHandler->handleOnModuleDeactivation($moduleConfiguration, 1);

        $this->assertSame(
            [],
            $shopConfigurationSettingDaoMock->get(ShopConfigurationSetting::MODULE_CONTROLLERS, 1)->getValue()
        );
    }

    /**
     * @return MockObject|ShopConfigurationSettingDaoInterface
     */
    private function getShopConfigurationSettingDaoMock(): ShopConfigurationSettingDaoInterface
    {
        return $this
            ->getMockBuilder(ShopConfigurationSettingDaoInterface::class)
            ->getMock();
    }

    /**
     * @return ShopConfigurationSetting
     */
    private function getShopConfigurationSettingWithEmptyValue(): ShopConfigurationSetting
    {
        $moduleControllers = new ShopConfigurationSetting();
        $moduleControllers
            ->setName(ShopConfigurationSetting::MODULE_CONTROLLERS)
            ->setShopId(1)
            ->setType(ShopSettingType::ARRAY)
            ->setValue([]);

        return $moduleControllers;
    }

    /**
     * @param $shopConfigurationSettingDaoMock
     */
    private function getShopConfigurationSettingWithExistingModuleControllers($shopConfigurationSettingDaoMock): void
    {
        $moduleControllers = new ShopConfigurationSetting();
        $moduleControllers
            ->setName(ShopConfigurationSetting::MODULE_CONTROLLERS)
            ->setShopId(1)
            ->setType(ShopSettingType::ARRAY)
            ->setValue([
                'existingmodule' => ['firstcontrollerexistingmodule' => '\OldModule\FirstControllerClass']
            ]);

        $shopConfigurationSettingDaoMock->method('get')->willReturn($moduleControllers);
    }
}
