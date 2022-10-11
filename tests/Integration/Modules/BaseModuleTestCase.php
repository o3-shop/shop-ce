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
namespace OxidEsales\EshopCommunity\Tests\Integration\Modules;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Base class for module integration tests.
 *
 * @group module
 */
abstract class BaseModuleTestCase extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Ensure a clean environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = ContainerFactory::getInstance()->getContainer();

        $environment = new Environment();
        $environment->clean();
    }

    protected function tearDown(): void
    {
        $this->removeTestModules();

        parent::tearDown();
    }

    protected function installAndActivateModule(string $moduleId, int $shopId = 1)
    {
        $installService = $this->container->get(ModuleInstallerInterface::class);
        $package = new OxidEshopPackage($moduleId, __DIR__ . '/TestData/modules/' . $moduleId);
        $package->setTargetDirectory('oeTest/' . $moduleId);
        $installService->install($package);

        $activationService = $this->container->get(ModuleActivationBridgeInterface::class);
        $activationService->activate($moduleId, $shopId);
    }

    /**
     * Deactivates module.
     *
     * @param \OxidEsales\Eshop\Core\Module\Module $module
     * @param string   $moduleId
     */
    protected function deactivateModule($module, $moduleId = null, int $shopId = 1)
    {
        if (!$moduleId) {
            $moduleId = $module->getId();
        }

        $activationService = $this->container->get(ModuleActivationBridgeInterface::class);

        $activationService->deactivate($moduleId, $shopId);
    }

    /**
     * Runs all asserts
     *
     * @param array $expectedResult
     */
    protected function runAsserts($expectedResult)
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();

        $validator = new Validator($config);

        if (isset($expectedResult['blocks'])) {
            $this->assertTrue($validator->checkBlocks($expectedResult['blocks']), 'Blocks do not match expectations');
        }

        if (isset($expectedResult['extend'])) {
            $this->assertEquals(
                $expectedResult['extend'],
                $config->getConfigParam('aModules'),
                'Extensions do not match expectations'
            );
        }

        if (isset($expectedResult['files'])) {
            $this->assertTrue($validator->checkFiles($expectedResult['files']), 'Files do not match expectations');
        }

        if (isset($expectedResult['controllers'])) {
            $this->assertTrue($validator->checkControllers($expectedResult['controllers']), 'Controllers do not match expectations');
        }

        if (isset($expectedResult['events'])) {
            $this->assertTrue($validator->checkEvents($expectedResult['events']), 'Events do not match expectations');
        }

        if (isset($expectedResult['settings'])) {
            $this->assertTrue($validator->checkConfigAmount($expectedResult['settings']), 'Configs do not match expectations');
        }

        if (isset($expectedResult['versions'])) {
            $this->assertEquals(
                $expectedResult['versions'],
                $config->getConfigParam('aModuleVersions'),
                'Versions do not match expectations'
            );
        }

        if (isset($expectedResult['templates'])) {
            $this->assertTrue($validator->checkTemplates($expectedResult['templates']), 'Templates do not match expectations');
        }

        if (isset($expectedResult['settings_values'])) {
            $this->assertTrue(
                $validator->checkConfigValues($expectedResult['settings_values']),
                'Config values does not match expectations'
            );
        }
    }

    private function removeTestModules()
    {
        $fileSystem = $this->container->get('oxid_esales.symfony.file_system');
        $fileSystem->remove($this->container->get(ContextInterface::class)->getModulesPath() . '/oeTest/');
    }
}
