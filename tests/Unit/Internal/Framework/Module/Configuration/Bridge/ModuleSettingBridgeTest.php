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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridge;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;

class ModuleSettingBridgeTest extends TestCase
{
    private function makeContext(int $shopId = 1): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getCurrentShopId')->willReturn($shopId);
        return $context;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridge::save
     */
    public function testSaveUpdatesSettingValueOnConfigurationAndPersistsBoth(): void
    {
        $setting = new Setting();
        $setting->setName('mySetting')->setValue('oldValue');

        $moduleConfig = $this->createMock(ModuleConfiguration::class);
        $moduleConfig->method('getModuleSetting')->with('mySetting')->willReturn($setting);

        $configurationDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $configurationDao->expects($this->once())
            ->method('get')
            ->with('mymod', 7)
            ->willReturn($moduleConfig);
        $configurationDao->expects($this->once())
            ->method('save')
            ->with($moduleConfig, 7);

        $settingDao = $this->createMock(SettingDaoInterface::class);
        $settingDao->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Setting $persisted) {
                $this->assertSame('newValue', $persisted->getValue());
                return true;
            }), 'mymod', 7);

        $bridge = new ModuleSettingBridge($this->makeContext(7), $configurationDao, $settingDao);
        $bridge->save('mySetting', 'newValue', 'mymod');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridge::get
     */
    public function testGetReturnsValueOfModuleSettingFromCurrentShopConfiguration(): void
    {
        $setting = new Setting();
        $setting->setName('mySetting')->setValue('storedValue');

        $moduleConfig = $this->createMock(ModuleConfiguration::class);
        $moduleConfig->method('getModuleSetting')->with('mySetting')->willReturn($setting);

        $configurationDao = $this->createMock(ModuleConfigurationDaoInterface::class);
        $configurationDao->expects($this->once())
            ->method('get')
            ->with('mymod', 3)
            ->willReturn($moduleConfig);

        $settingDao = $this->createMock(SettingDaoInterface::class);

        $bridge = new ModuleSettingBridge($this->makeContext(3), $configurationDao, $settingDao);
        $this->assertSame('storedValue', $bridge->get('mySetting', 'mymod'));
    }
}
