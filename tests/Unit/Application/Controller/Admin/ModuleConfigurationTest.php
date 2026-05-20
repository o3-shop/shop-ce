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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration as ModuleConfigurationDto;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use Psr\Container\ContainerInterface;

class ModuleConfigurationTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The "swallow exception" branches log at ERROR level, which the
        // testing-library treats as a test failure. Route logging through
        // a NullLogger so those branches don't poison the harness.
        Registry::set('logger', new \Psr\Log\NullLogger());
    }

    public function testConstructorAddsPasswordTypeToConfParams(): void
    {
        $controller = $this->getProxyClass(ModuleConfiguration::class);
        $params = $controller->getNonPublicVar('_aConfParams');
        $this->assertSame('confpassword', $params['password'] ?? null);
        // Existing parent-level types must remain untouched.
        $this->assertSame('confbools', $params['bool'] ?? null);
        $this->assertSame('confstrs', $params['str'] ?? null);
    }

    public function testTemplateNameIsModuleConfig(): void
    {
        $controller = $this->getProxyClass(ModuleConfiguration::class);
        $this->assertSame('shop_config.tpl', $controller->getNonPublicVar('_sModule'));
    }

    public function testRenderReturnsModuleConfigTemplate(): void
    {
        $moduleId = 'mymodule';
        $this->setRequestParameter('oxid', $moduleId);

        $configDto = $this->makeConfigurationDto([]);
        $bridge = $this->makeDaoBridge($moduleId, $configDto);
        $controller = $this->makeControllerWithContainer([
            ModuleConfigurationDaoBridgeInterface::class => $bridge,
        ]);

        $this->assertSame('module_config.tpl', $controller->render());
    }

    public function testRenderExposesFormattedSettingsForTemplate(): void
    {
        $moduleId = 'mymodule';
        $this->setRequestParameter('oxid', $moduleId);

        $boolSetting = $this->makeSettingMock('show_banner', 'bool', '1', 'banner_group', 'required');
        $strSetting = $this->makeSettingMock('label', 'str', 'Hello', 'banner_group', '');

        $configDto = $this->makeConfigurationDto([$boolSetting, $strSetting]);
        $bridge = $this->makeDaoBridge($moduleId, $configDto);

        $controller = $this->makeControllerWithContainer([
            ModuleConfigurationDaoBridgeInterface::class => $bridge,
        ]);
        $controller->render();

        $viewData = $controller->getViewData();
        $this->assertArrayHasKey('var_constraints', $viewData);
        $this->assertArrayHasKey('var_grouping', $viewData);
        $this->assertArrayHasKey('show_banner', $viewData['var_constraints']);
        $this->assertArrayHasKey('banner_group', $viewData['var_grouping']);
        // Boolean values arrive in the 'confbools' bucket — htmlentities()
        // stringifies them, so a truthy 'true' becomes '1'.
        $this->assertArrayHasKey('confbools', $viewData);
        $this->assertSame('1', (string) $viewData['confbools']['show_banner']);
        // Strings go in the 'confstrs' bucket, html-encoded.
        $this->assertArrayHasKey('confstrs', $viewData);
        $this->assertSame('Hello', $viewData['confstrs']['label']);
    }

    public function testRenderSwallowsBridgeExceptionsAndStillReturnsTemplate(): void
    {
        $this->setRequestParameter('oxid', 'mymodule');
        $bridge = $this->createMock(ModuleConfigurationDaoBridgeInterface::class);
        $bridge->method('get')->willThrowException(new \RuntimeException('bridge failure'));

        $controller = $this->makeControllerWithContainer([
            ModuleConfigurationDaoBridgeInterface::class => $bridge,
        ]);

        $this->assertSame('module_config.tpl', $controller->render());
    }

    public function testRenderThrowsWhenModuleIdMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $controller = oxNew(ModuleConfiguration::class);
        // No oxid request param, no edit object id, no saved_oxid.
        $controller->render();
    }

    public function testSaveConfVarsToggleActivationAroundSave(): void
    {
        $moduleId = 'mymodule';
        $this->setRequestParameter('oxid', $moduleId);
        $this->setRequestParameter('confbools', ['show_banner' => '1']);

        $boolSetting = $this->makeSettingMock('show_banner', 'bool', false, '', '');
        $configDto = $this->makeConfigurationDto([$boolSetting]);

        $daoBridge = $this->makeDaoBridge($moduleId, $configDto);
        $daoBridge->expects($this->atLeastOnce())->method('save');

        $activationBridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $activationBridge->method('isActive')->willReturn(true);
        $activationCalls = [];
        $activationBridge->expects($this->once())
            ->method('deactivate')
            ->willReturnCallback(function ($id, $shopId) use (&$activationCalls) {
                $activationCalls[] = ['op' => 'deactivate', 'id' => $id];
            });
        $activationBridge->expects($this->once())
            ->method('activate')
            ->willReturnCallback(function ($id, $shopId) use (&$activationCalls) {
                $activationCalls[] = ['op' => 'activate', 'id' => $id];
            });

        $controller = $this->makeControllerWithContainer([
            ModuleConfigurationDaoBridgeInterface::class => $daoBridge,
            ModuleActivationBridgeInterface::class       => $activationBridge,
        ]);

        $controller->saveConfVars();

        $this->assertSame('deactivate', $activationCalls[0]['op'] ?? null);
        $this->assertSame('activate', $activationCalls[1]['op'] ?? null);
        // The bool setting must have received its FILTER_VALIDATE_BOOLEAN value.
        $boolSetting->expects($this->any())->method('setValue');
    }

    public function testSaveConfVarsSkipsActivationToggleWhenInactive(): void
    {
        $moduleId = 'mymodule';
        $this->setRequestParameter('oxid', $moduleId);

        $configDto = $this->makeConfigurationDto([]);
        $daoBridge = $this->makeDaoBridge($moduleId, $configDto);

        $activationBridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $activationBridge->method('isActive')->willReturn(false);
        $activationBridge->expects($this->never())->method('deactivate');
        $activationBridge->expects($this->never())->method('activate');

        $controller = $this->makeControllerWithContainer([
            ModuleConfigurationDaoBridgeInterface::class => $daoBridge,
            ModuleActivationBridgeInterface::class       => $activationBridge,
        ]);

        $controller->saveConfVars();
        $this->assertTrue(true);
    }

    public function testSaveConfVarsSwallowsBridgeExceptions(): void
    {
        $this->setRequestParameter('oxid', 'mymodule');
        $activationBridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $activationBridge->method('isActive')->willThrowException(new \RuntimeException('boom'));

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $activationBridge,
        ]);

        $controller->saveConfVars();
        $this->assertTrue(true);
    }

    private function makeSettingMock(string $name, string $type, $value, string $group, $constraints): Setting
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getName')->willReturn($name);
        $setting->method('getType')->willReturn($type);
        $setting->method('getValue')->willReturn($value);
        $setting->method('getGroupName')->willReturn($group);
        // getConstraints declared return type may be array (or null in
        // some versions); accept whatever shape the test passes in.
        $setting->method('getConstraints')->willReturn(is_array($constraints) ? $constraints : []);
        return $setting;
    }

    /**
     * @param Setting[] $settings
     */
    private function makeConfigurationDto(array $settings): ModuleConfigurationDto
    {
        $dto = $this->createMock(ModuleConfigurationDto::class);
        $dto->method('getModuleSettings')->willReturn($settings);
        return $dto;
    }

    private function makeDaoBridge(string $moduleId, ModuleConfigurationDto $dto): ModuleConfigurationDaoBridgeInterface
    {
        $bridge = $this->createMock(ModuleConfigurationDaoBridgeInterface::class);
        $bridge->method('get')->with($moduleId)->willReturn($dto);
        return $bridge;
    }

    /**
     * @param array<string,object> $services
     */
    private function makeControllerWithContainer(array $services): ModuleConfiguration
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($id) use ($services) {
            return $services[$id] ?? null;
        });
        $container->method('has')->willReturnCallback(fn ($id) => isset($services[$id]));

        $controller = $this->getMock(ModuleConfiguration::class, ['getContainer']);
        $controller->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($container));

        return $controller;
    }
}
