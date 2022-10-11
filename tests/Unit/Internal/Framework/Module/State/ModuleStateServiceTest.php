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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\State;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModuleStateServiceTest extends TestCase
{
    public function testIsActiveReturnsTrueIfModuleIsActive()
    {
        $moduleStateService = new ModuleStateService($this->getShopConfigurationSettingDao());

        $this->assertTrue(
            $moduleStateService->isActive('activeModuleId', 1)
        );
    }

    public function testIsActiveReturnsFalseIfModuleIsNotActive()
    {
        $moduleStateService = new ModuleStateService($this->getShopConfigurationSettingDao());

        $this->assertFalse(
            $moduleStateService->isActive('notActiveModuleId', 1)
        );
    }

    public function testIsActiveReturnsFalseIfNoActiveModules()
    {
        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willThrowException(new EntryDoesNotExistDaoException());

        $moduleStateService = new ModuleStateService($shopConfigurationSettingDao);

        $this->assertFalse(
            $moduleStateService->isActive('notActiveModuleId', 1)
        );
    }

    private function getShopConfigurationSettingDao(): ShopConfigurationSettingDaoInterface
    {
        $activeModulesSetting = new ShopConfigurationSetting();
        $activeModulesSetting->setValue([
            'activeModuleId',
            'anotherActiveModuleId',
        ]);

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->with(ShopConfigurationSetting::ACTIVE_MODULES, 1)
            ->willReturn($activeModulesSetting);

        return $shopConfigurationSettingDao;
    }
}
