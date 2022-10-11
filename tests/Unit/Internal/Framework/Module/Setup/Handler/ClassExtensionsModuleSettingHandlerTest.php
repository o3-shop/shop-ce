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
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\ShopConfigurationClassExtensionsHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ClassExtensionsModuleSettingHandlerTest extends TestCase
{
    public function testHandlingOnModuleActivation(): void
    {
        $shopConfigurationSettingBeforeHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingBeforeHandling
            ->setValue([
                'alreadyExistentModuleId' => ['extensionClass'],
            ]);

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willReturn($shopConfigurationSettingBeforeHandling);

        $shopConfigurationSettingAfterHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingAfterHandling
            ->setValue([
                'alreadyExistentModuleId' => ['extensionClass'],
                'newModuleId'             => ['moduleExtensionClass', 'anotherModuleExtensionClass'],
            ]);

        $shopConfigurationSettingDao
            ->expects($this->once())
            ->method('save')
            ->with($shopConfigurationSettingAfterHandling);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('newModuleId');

        $classExtensions =      [
            'originalClass'         => 'moduleExtensionClass',
            'anotherOriginalClass'  => 'anotherModuleExtensionClass',
        ];

        foreach ($classExtensions as $classNamespace => $moduleNamespace) {
            $moduleConfiguration->addClassExtension(new ClassExtension($classNamespace, $moduleNamespace));
        }

        $handler = new ShopConfigurationClassExtensionsHandler($shopConfigurationSettingDao);
        $handler->handleOnModuleActivation($moduleConfiguration, 1);
    }

    public function testHandlingOnModuleDeactivation(): void
    {
        $shopConfigurationSettingBeforeHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingBeforeHandling
            ->setValue([
                'moduleIdToDeactivate' => ['extensionClass'],
                'anotherModuleId'      => ['anotherExtensionClass'],
            ]);

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willReturn($shopConfigurationSettingBeforeHandling);

        $shopConfigurationSettingAfterHandling = new ShopConfigurationSetting();
        $shopConfigurationSettingAfterHandling
            ->setValue([
                'anotherModuleId' => ['anotherExtensionClass'],
            ]);

        $shopConfigurationSettingDao
            ->expects($this->once())
            ->method('save')
            ->with($shopConfigurationSettingAfterHandling);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('moduleIdToDeactivate');
        $moduleConfiguration->addClassExtension(
            new ClassExtension(
                '',
                ''
            )
        );

        $handler = new ShopConfigurationClassExtensionsHandler($shopConfigurationSettingDao);
        $handler->handleOnModuleDeactivation($moduleConfiguration, 1);
    }
}
