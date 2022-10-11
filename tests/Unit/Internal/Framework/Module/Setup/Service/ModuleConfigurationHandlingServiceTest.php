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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationHandlingService;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Handler\ModuleConfigurationHandlerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSettingNotValidException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator\ModuleConfigurationValidatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModuleConfigurationHandlingServiceTest extends TestCase
{
    public function testHandlingOnActivation()
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');

        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setValue('value');

        $moduleConfiguration->addModuleSetting($setting);

        $handler = $this->getMockBuilder(ModuleConfigurationHandlerInterface::class)->getMock();
        $handler
            ->expects($this->atLeastOnce())
            ->method('handleOnModuleActivation');

        $moduleSettingsHandlingService = new ModuleConfigurationHandlingService();
        $moduleSettingsHandlingService->addHandler($handler);

        $moduleSettingsHandlingService->handleOnActivation($moduleConfiguration, 1);
    }

    public function testHandlingOnDeactivation()
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');
        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setValue('value');

        $moduleConfiguration->addModuleSetting($setting);


        $handler = $this->getMockBuilder(ModuleConfigurationHandlerInterface::class)->getMock();

        $handler
            ->expects($this->atLeastOnce())
            ->method('handleOnModuleDeactivation');

        $moduleSettingsHandlingService = new ModuleConfigurationHandlingService();
        $moduleSettingsHandlingService->addHandler($handler);

        $moduleSettingsHandlingService->handleOnDeactivation($moduleConfiguration, 1);
    }

    public function testModuleSettingInvalid()
    {
        $this->expectException(ModuleSettingNotValidException::class);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');
        $setting = new Setting();
        $setting
            ->setName('testSetting')
            ->setValue('value');

        $moduleConfiguration->addModuleSetting($setting);

        $moduleConfigurationValidator = $this->getMockBuilder(ModuleConfigurationValidatorInterface::class)->getMock();
        $moduleConfigurationValidator
            ->method('validate')
            ->willThrowException(new ModuleSettingNotValidException());

        $moduleSettingsHandlingService = new ModuleConfigurationHandlingService();

        $moduleSettingsHandlingService->addValidator($moduleConfigurationValidator);

        $moduleSettingsHandlingService->handleOnActivation($moduleConfiguration, 1);
    }
}
