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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private $container;

    public function setup(): void
    {
        $this->container = ContainerFactory::getInstance()->getContainer();

        $this->container->get('oxid_esales.module.install.service.launched_shop_project_configuration_generator')
             ->generate();

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->removeTestModules();

        $this->container->get('oxid_esales.module.install.service.launched_shop_project_configuration_generator')
            ->generate();

        Registry::getConfig()->saveShopConfVar('aarr', 'activeModules', []);
    }

    public function testIsActiveIfModuleIsActive()
    {
        $moduleId = 'with_metadata_v21';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertTrue($module->isActive());
    }

    public function testIsActiveIfModuleIsNotActive()
    {
        $moduleId = 'with_metadata_v21';
        $this->installModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertFalse($module->isActive());
    }

    public function testHasExtendClassReturnsTrue()
    {
        $moduleId = 'with_class_extensions';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertTrue($module->hasExtendClass());
    }

    public function testHasExtendClassReturnsFalse()
    {
        $moduleId = 'with_metadata_v21';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertFalse($module->hasExtendClass());
    }

    public function testGetExtensions()
    {
        $moduleId = 'with_class_extensions';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertSame(
            [
                'OxidEsales\Eshop\Application\Model\Article' => 'with_class_extensions/ModuleArticle',
            ],
            $module->getExtensions()
        );
    }

    public function testGetExtensionsReturnsEmptyArrayIfNoExtensions()
    {
        $moduleId = 'with_metadata_v21';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertSame(
            [],
            $module->getExtensions()
        );
    }

    public function testModuleGetTemplates()
    {
        $moduleId = "with_extending_blocks";

        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $expected = [
            'page/checkout/basket.tpl',
            'page/checkout/payment.tpl',
            'page/checkout/basket.tpl'
        ];

        $actual = $module->getTemplates();

        $this->assertEquals(0, count(array_diff($expected, $actual)) + count(array_diff($actual, $expected)));
    }

    public function testGetModuleDataWillReturnMetadataArray(): void
    {
        $moduleId = "with_everything";

        $this->installModule($moduleId);
        $this->activateModule($moduleId);
        $module = oxNew(Module::class);
        $module->load($moduleId);

        $moduleData = $module->getModuleData();

        $metadata = $this->loadMetadataPhp($moduleId);
        $this->assertEquals($metadata['id'], $moduleData['id']);
        $this->assertEquals($metadata['title'], $moduleData['title']['en']);
        $this->assertEquals($metadata['description'], $moduleData['description']['en']);
        $this->assertEquals($metadata['thumbnail'], $moduleData['thumbnail']);
        $this->assertEquals($metadata['version'], $moduleData['version']);
        $this->assertEquals($metadata['author'], $moduleData['author']);

        $this->assertEquals(array_values($metadata['extend']), array_values($moduleData['extend']));
        $this->assertEquals($metadata['blocks'], $moduleData['blocks']);
        $this->assertEquals($metadata['templates'], $moduleData['templates']);
        $this->assertEquals($metadata['files'], $moduleData['files']);
        $this->assertEquals($metadata['settings'], $moduleData['settings']);
    }

    public function testGetPathsReturnsInstalledModulePaths()
    {
        $this->installModule('with_class_extensions');
        $this->installModule('with_metadata_v21');

        $module = oxNew(Module::class);


        $this->assertSame(
            [
                'with_class_extensions' => $this->getModuleConfiguration('with_class_extensions')->getPath(),
                'with_metadata_v21'     => $this->getModuleConfiguration('with_metadata_v21')->getPath(),
            ],
            $module->getModulePaths()
        );
    }

    public function testHasMetadataReturnsTrue()
    {
        $moduleId = 'with_metadata_v21';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->assertTrue($module->hasMetadata());
    }

    public function testGetModuleIdByClassName()
    {
        $moduleId = 'with_class_extensions';
        $this->installModule($moduleId);
        $this->activateModule($moduleId);

        $this->assertEquals(
            'with_class_extensions',
            oxNew(Module::class)->getModuleIdByClassName("with_class_extensions/ModuleArticle")
        );
    }

    private function installModule(string $id)
    {
        $package = new OxidEshopPackage($id, __DIR__ . '/Fixtures/' . $id);
        $package->setTargetDirectory('oeTest/' . $id);

        $this->container->get(ModuleInstallerInterface::class)
            ->install($package);
    }

    private function activateModule(string $id)
    {
        $this->container->get(ModuleActivationBridgeInterface::class)
            ->activate($id, 1);
    }

    private function getModuleConfiguration(string $moduleId)
    {
        return $this->container->get(ModuleConfigurationDaoBridgeInterface::class)
            ->get($moduleId);
    }

    private function removeTestModules()
    {
        $fileSystem = $this->container->get('oxid_esales.symfony.file_system');
        $fileSystem->remove($this->container->get(ContextInterface::class)->getModulesPath() . '/oeTest/');
    }

    private function loadMetadataPhp(string $moduleId): array
    {
        require __DIR__ . "/Fixtures/$moduleId/metadata.php";
        return $aModule;
    }
}
