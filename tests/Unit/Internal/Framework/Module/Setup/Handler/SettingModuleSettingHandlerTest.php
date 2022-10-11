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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\SettingModuleSettingHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SettingModuleSettingHandlerTest extends TestCase
{
    public function testHandlingOnModuleActivation(): void
    {
        $shopModuleSetting = $this->getTestSetting();

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');
        $moduleConfiguration->addModuleSetting($shopModuleSetting);

        $shopModuleSettingDao = $this->getMockBuilder(SettingDaoInterface::class)->getMock();
        $shopModuleSettingDao
            ->expects($this->once())
            ->method('save')
            ->with($shopModuleSetting);

        $handler = new SettingModuleSettingHandler($shopModuleSettingDao);
        $handler->handleOnModuleActivation($moduleConfiguration, 1);
    }

    public function testHandlingOnModuleDeactivation(): void
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');
        $moduleConfiguration->addModuleSetting($this->getTestSetting());

        $shopModuleSetting = $this->getTestSetting();

        $shopModuleSettingDao = $this->getMockBuilder(SettingDaoInterface::class)->getMock();
        $shopModuleSettingDao
            ->expects($this->once())
            ->method('delete')
            ->with($shopModuleSetting);

        $handler = new SettingModuleSettingHandler($shopModuleSettingDao);
        $handler->handleOnModuleDeactivation($moduleConfiguration, 1);
    }

    private function getTestSetting(): Setting
    {
        $shopModuleSetting = new Setting();
        $shopModuleSetting
            ->setName('blCustomGridFramework')
            ->setValue('false')
            ->setType('bool')
            ->setConstraints(['1', '2', '3',])
            ->setGroupName('frontend')
            ->setPositionInGroup(5);

        return $shopModuleSetting;
    }
}
