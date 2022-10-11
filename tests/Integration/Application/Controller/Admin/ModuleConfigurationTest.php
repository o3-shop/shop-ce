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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Application\Controller\Admin\ModuleConfiguration as ModuleConfigurationController;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * @internal
 */
final class ModuleConfigurationTest extends UnitTestCase
{
    private $testModuleId = 'testModuleId';

    protected function tearDown(): void
    {
        $this->uninstallTestModule();
        parent::tearDown();
    }

    public function testRender(): void
    {
        $this->installTestModule();

        $_POST['oxid'] = $this->testModuleId;

        $moduleConfigurationController = oxNew(ModuleConfigurationController::class);

        $this->assertEquals('module_config.tpl', $moduleConfigurationController->render());

        $viewData = $moduleConfigurationController->getViewData();

        $this->assertSame(
            $this->testModuleId,
            $viewData['oModule']->getId()
        );
    }

    public function testSaveConfVarsForInactiveModule(): void
    {
        $this->installTestModule();

        $_POST['oxid'] = $this->testModuleId;
        $_POST['confstrs'] = ['stringSetting' => 'newValue'];

        $moduleConfigurationController = oxNew(ModuleConfigurationController::class);
        $moduleConfigurationController->saveConfVars();

        $container = ContainerFactory::getInstance()->getContainer();
        $moduleConfiguration = $container->get(ModuleConfigurationDaoBridgeInterface::class)->get($this->testModuleId);

        $this->assertSame(
            'newValue',
            $moduleConfiguration->getModuleSettings()[0]->getValue()
        );
    }

    public function testSaveConfVarsForActiveModule(): void
    {
        $this->installTestModule();
        $this->activateTestModule();

        $_POST['oxid'] = $this->testModuleId;
        $_POST['confstrs'] = ['stringSetting' => 'newValue'];

        $moduleConfigurationController = oxNew(ModuleConfigurationController::class);
        $moduleConfigurationController->saveConfVars();

        $moduleConfiguration = $this->getModuleConfiguration();

        $this->assertSame(
            'newValue',
            $moduleConfiguration->getModuleSettings()[0]->getValue(),
            'This test is expected to pass only if run from console (headers already sent issue)'
        );
    }

    private function installTestModule(): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $container->get(ModuleInstallerInterface::class)->install(
            new OxidEshopPackage('testModule', __DIR__ . '/Fixtures/testModule/')
        );
    }

    private function uninstallTestModule(): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $container->get(ModuleInstallerInterface::class)->uninstall(
            new OxidEshopPackage('testModule', __DIR__ . '/Fixtures/testModule/')
        );
    }

    private function activateTestModule(): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $container->get(ModuleActivationBridgeInterface::class)->activate($this->testModuleId, 1);
    }

    private function getModuleConfiguration(): ModuleConfiguration
    {
        $container = ContainerFactory::getInstance()->getContainer();
        return $container->get(ModuleConfigurationDaoBridgeInterface::class)->get($this->testModuleId);
    }
}
