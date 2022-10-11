<?php
declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleSettingNotFountException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModuleConfigurationTest extends TestCase
{
    public function testAddModuleSetting()
    {
        $moduleConfiguration = new ModuleConfiguration();
        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setValue([]);
        $moduleConfiguration->addModuleSetting($setting);

        $this->assertSame(
            $setting,
            $moduleConfiguration->getModuleSetting('testSetting')
        );
    }

    public function testConfigurationHasSetting()
    {
        $moduleConfiguration = new ModuleConfiguration();

        $this->assertFalse($moduleConfiguration->hasModuleSetting('testSetting'));

        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setValue([]);
        $moduleConfiguration->addModuleSetting($setting);

        $this->assertTrue($moduleConfiguration->hasModuleSetting('testSetting'));
    }

    public function testConfigurationHasClassExtension()
    {
        $moduleConfiguration = new ModuleConfiguration();

        $moduleConfiguration->addClassExtension(
            new ClassExtension(
                'extendedClassNamespace',
                'expectedExtensionNamespace'
            )
        );

        $this->assertTrue(
            $moduleConfiguration->hasClassExtension('expectedExtensionNamespace')
        );
    }

    public function testConfigurationDoesNotHaveClassExtension()
    {
        $moduleConfiguration = new ModuleConfiguration();

        $this->assertFalse(
            $moduleConfiguration->hasClassExtension('expectedExtensionNamespace')
        );

        $moduleConfiguration->addClassExtension(
            new ClassExtension(
                'extendedClassNamespace',
                'anotherExtensionNamespace'
            )
        );

        $this->assertFalse(
            $moduleConfiguration->hasClassExtension('expectedExtensionNamespace')
        );
    }

    public function testGetModuleSettingWhenSettingNotFound(): void
    {
        $this->expectException(ModuleSettingNotFountException::class);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->getModuleSetting('nonExistingSetting');
    }
}
