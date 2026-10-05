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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ModuleMain;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

class ModuleMainTest_StubModule extends Module
{
    public static bool $loadReturns = true;
    public ?string $loadedWith = null;

    public function __construct()
    {
        // Skip parent::__construct so it doesn't reach for module DB metadata.
    }

    public function load($oxId)
    {
        $this->loadedWith = (string) $oxId;
        return self::$loadReturns;
    }

    public function getInfo($key, $iLang = null)
    {
        return 'My Module';
    }

    public function getModulePath($sModuleId = null)
    {
        return 'vendor/mymodule';
    }
}

class ModuleMainTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Activate/deactivate exception branches log at ERROR level —
        // route through NullLogger so the testing-library doesn't flag.
        Registry::set('logger', new NullLogger());
    }

    public function testRenderReturnsModuleMainTemplate(): void
    {
        $controller = oxNew(ModuleMain::class);
        $this->assertEquals('module_main.tpl', $controller->render());
    }

    public function testRenderLoadsModuleByModuleIdParameter(): void
    {
        $stub = new ModuleMainTest_StubModule();
        \oxTestModules::addModuleObject(Module::class, $stub);

        $this->setRequestParameter('moduleId', 'mymodule');

        $controller = oxNew(ModuleMain::class);
        $controller->render();

        $this->assertSame('mymodule', $stub->loadedWith);

        $viewData = $controller->getViewData();
        $this->assertSame($stub, $viewData['oModule'] ?? null);
        $this->assertSame('My Module', $viewData['sModuleName'] ?? null);
        $this->assertSame('vendor_mymodule', $viewData['sModuleId'] ?? null);
    }

    public function testRenderUsesEditObjectIdWhenModuleIdMissing(): void
    {
        $stub = new ModuleMainTest_StubModule();
        \oxTestModules::addModuleObject(Module::class, $stub);

        $controller = $this->getMock(ModuleMain::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue('mymodule-edit'));

        $controller->render();

        $this->assertSame('mymodule-edit', $stub->loadedWith);
    }

    public function testRenderSurfacesErrorWhenModuleLoadFails(): void
    {
        $stub = new ModuleMainTest_StubModule();
        ModuleMainTest_StubModule::$loadReturns = false;
        \oxTestModules::addModuleObject(Module::class, $stub);

        $errors = [];
        \oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{ $aA = func_get_args(); $GLOBALS["__moduleMainTestErrors"][] = $aA[0]; }');
        $GLOBALS['__moduleMainTestErrors'] = [];

        $this->setRequestParameter('moduleId', 'mymodule');
        $controller = oxNew(ModuleMain::class);
        $controller->render();

        $this->assertNotEmpty($GLOBALS['__moduleMainTestErrors']);
        ModuleMainTest_StubModule::$loadReturns = true;
    }

    public function testActivateModuleIsBlockedInDemoShop(): void
    {
        $this->getConfig()->setConfigParam('blDemoShop', 1);
        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->expects($this->never())->method('activate');

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $bridge,
        ]);
        $controller->activateModule();

        $this->assertTrue(true);
    }

    public function testActivateModuleSetsUpdatenavFlagOnSuccess(): void
    {
        $this->getConfig()->setConfigParam('blDemoShop', 0);

        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->expects($this->once())
            ->method('activate')
            ->with('mymodule');

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $bridge,
        ]);

        // Force getEditObjectId to return our module id.
        $reflection = new \ReflectionClass($controller);
        $editProp = $reflection->getParentClass()->getProperty('_sEditObjectId');
        $editProp->setAccessible(true);
        $editProp->setValue($controller, 'mymodule');

        $controller->activateModule();

        $this->assertSame('1', $controller->getViewData()['updatenav'] ?? null);
    }

    public function testActivateModuleHandlesBridgeException(): void
    {
        $this->getConfig()->setConfigParam('blDemoShop', 0);

        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->method('activate')->willThrowException(new \RuntimeException('boom'));

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $bridge,
        ]);
        $reflection = new \ReflectionClass($controller);
        $editProp = $reflection->getParentClass()->getProperty('_sEditObjectId');
        $editProp->setAccessible(true);
        $editProp->setValue($controller, 'mymodule');

        // Must not throw — exception is captured into the error display.
        $controller->activateModule();
        $this->assertArrayNotHasKey('updatenav', $controller->getViewData());
    }

    public function testDeactivateModuleIsBlockedInDemoShop(): void
    {
        $this->getConfig()->setConfigParam('blDemoShop', 1);
        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->expects($this->never())->method('deactivate');

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $bridge,
        ]);
        $controller->deactivateModule();

        $this->assertTrue(true);
    }

    public function testDeactivateModuleSetsUpdatenavFlagOnSuccess(): void
    {
        $this->getConfig()->setConfigParam('blDemoShop', 0);

        $bridge = $this->createMock(ModuleActivationBridgeInterface::class);
        $bridge->expects($this->once())
            ->method('deactivate')
            ->with('mymodule');

        $controller = $this->makeControllerWithContainer([
            ModuleActivationBridgeInterface::class => $bridge,
        ]);

        $reflection = new \ReflectionClass($controller);
        $editProp = $reflection->getParentClass()->getProperty('_sEditObjectId');
        $editProp->setAccessible(true);
        $editProp->setValue($controller, 'mymodule');

        $controller->deactivateModule();

        $this->assertSame('1', $controller->getViewData()['updatenav'] ?? null);
    }

    /**
     * @param array<string,object> $services
     */
    private function makeControllerWithContainer(array $services): ModuleMain
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn ($id) => $services[$id] ?? null);
        $container->method('has')->willReturnCallback(fn ($id) => isset($services[$id]));

        $controller = $this->getMock(ModuleMain::class, ['getContainer']);
        $controller->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($container));

        return $controller;
    }
}
