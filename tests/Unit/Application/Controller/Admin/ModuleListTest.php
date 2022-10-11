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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use oxConfig;
use OxidEsales\Eshop\Application\Controller\Admin\ModuleList;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ModuleListTest extends \OxidTestCase
{
    public function setup(): void
    {
        parent::setUp();
        ContainerFactory::getInstance()
            ->getContainer()
            ->get('oxid_esales.module.install.service.launched_shop_project_configuration_generator')
            ->generate();
    }

    /**
     * Module_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $moduleList = oxNew(ModuleList::class);
        $this->assertEquals('module_list.tpl', $moduleList->render());
    }

    public function testRenderWithCorrectModuleNames()
    {
        $modulesDirectory = __DIR__.'/../../../testData/modules/';

        /** @var oxConfig|MockObject $config */
        $config = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getModulesDir'));
        $config->expects($this->any())->method('getModulesDir')->will($this->returnValue($modulesDirectory));

        $container = ContainerFactory::getInstance()->getContainer();

        $container->get(ModuleInstallerInterface::class)->install(
            new OxidEshopPackage('testmodule', $modulesDirectory . 'testmodule')
        );

        $oView = oxNew('Module_List');
        $oView->setConfig($config);
        $this->assertEquals('module_list.tpl', $oView->render());

        $aViewData = $oView->getViewData();
        $aModulesNames = array_keys($aViewData['mylist']);
        $this->assertSame('testmodule', current($aModulesNames));
    }

    public function testModulesSortedByTitle(): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoBridgeInterface::class)->get();

        $moduleA = new ModuleConfiguration();
        $moduleA
            ->setId('a')
            ->setPath('some')
            ->setTitle(['en' => 'A']);

        $moduleB = new ModuleConfiguration();
        $moduleB
            ->setId('b')
            ->setPath('some')
            ->setTitle(['en' => 'B']);

        $shopConfiguration->addModuleConfiguration($moduleB);
        $shopConfiguration->addModuleConfiguration($moduleA);

        $container->get(ShopConfigurationDaoBridgeInterface::class)->save($shopConfiguration);

        $moduleList = oxNew(ModuleList::class);
        $moduleList->render();

        $modules = array_values($moduleList->getViewData()['mylist']);

        $this->assertSame(
            'A',
            $modules[0]->getTitle()
        );

        $this->assertSame(
            'B',
            $modules[1]->getTitle()
        );
    }
}
