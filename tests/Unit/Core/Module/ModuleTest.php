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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use oxModule;
use oxRegistry;

/**
 * @group module
 * @package Unit\Core
 */
class ModuleTest extends \OxidTestCase
{
    /**
     * Tear down the fixture.
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxconfig');
        $this->cleanUpTable('oxconfigdisplay');
        $this->cleanUpTable('oxtplblocks');

        parent::tearDown();
    }

    /**
     * oxModule::load() test case
     *
     * @return null
     */
    public function testLoadWhenModuleDoesNotExists()
    {
        $oModule = oxNew('oxModule');
        $this->assertFalse($oModule->load('non_existing_module'));
    }

    /**
     * oxModule::loadByDir()
     *
     * @return null
     */
    public function testLoadByDir()
    {
        $aModulesPaths = ['testModuleId' => 'test/path'];
        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['load', 'getModulePaths']);
        $oModule->expects($this->exactly(2))
            ->method('getModulePaths')
            ->willReturn($aModulesPaths);
        $oModule->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                [$this->equalTo('noSuchTest/path')],
                [$this->equalTo('testModuleId')]
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($oModule->loadByDir('noSuchTest/path'));
        $this->assertTrue($oModule->loadByDir('test/path'));
    }

    /**
     * oxModule::getInfo() test case
     *
     * @return null
     */
    public function testGetInfo()
    {
        $aModule = [
            'id'    => 'testModuleId',
            'title' => 'testModuleTitle',
        ];

        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertEquals('testModuleId', $oModule->getInfo('id'));
        $this->assertEquals('testModuleTitle', $oModule->getInfo('title'));
    }

    /**
     * oxModule::getInfo() test case - selecting multi language value
     *
     * @return null
     */
    public function testGetInfo_usingLanguage()
    {
        $aModule = [
            'title'       => 'testModuleTitle',
            'description' => ['en' => 'test EN value', 'de' => 'test DE value'],
        ];

        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertEquals('testModuleTitle', $oModule->getInfo('title'));
        $this->assertEquals('testModuleTitle', $oModule->getInfo('title', 1));

        $this->assertEquals('test DE value', $oModule->getInfo('description', 0));
        $this->assertEquals('test EN value', $oModule->getInfo('description', 1));
        $this->assertEquals('test EN value', $oModule->getInfo('description', 2));
    }

    /**
     * oxModule::isActive() test case, empty
     *
     * @return null
     */
    public function testIsActiveEmpty()
    {
        $aModules = [];
        $this->getConfig()->setConfigParam('aModules', $aModules);

        $aExtend = ['extend' => []];
        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aExtend);

        $this->assertFalse($oModule->isActive());
    }

    /**
     * oxModule::isActive() test case, inactive
     *
     * @return null
     */
    public function testIsActiveInactive()
    {
        $aModule = ['extend' => ['oxtest' => 'test/mytest']];
        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertFalse($oModule->isActive());
    }

    /**
     * oxModule::isActive() test case, inactive in chain
     *
     * @return null
     */
    public function testIsActiveInactiveChain()
    {
        $aModules = ['oxtest' => 'test1/mytest1&test2/mytest2'];
        $this->getConfig()->setConfigParam('aModules', $aModules);

        $aExtend = ['extend' => ['oxtest' => 'test/mytest'], 'id' => 'test'];
        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aExtend);

        $this->assertFalse($oModule->isActive());
    }

    /**
     * oxModule::isActive() test case, deactivated
     *
     * @return null
     */
    public function testIsActiveDeactivated()
    {
        $aDisabledModules = ['test'];
        $this->getConfig()->setConfigParam('aDisabledModules', $aDisabledModules);

        $aModule = ['id' => 'test'];
        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertFalse($oModule->isActive());
    }

    /**
     * oxModule::isActive() test case, not deactivated in chain
     *
     * @return null
     */
    public function testIsActiveDeactivatedChain()
    {
        $aDisabledModules = ['mytest1', 'test', 'test2'];
        $this->getConfig()->setConfigParam('aDisabledModules', $aDisabledModules);

        $aModule = ['id' => 'test'];
        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertFalse($oModule->isActive());
    }

    /**
     * oxModule::isActive() test case, active
     *
     * @return null
     */
    public function testIsActiveWithNonExistingModuleLoaded()
    {
        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getDisabledModules']);
        $oModule->expects($this->any())->method('getDisabledModules')->will($this->returnValue([]));
        $oModule->load('non_existing_module');

        $this->assertFalse($oModule->isActive());
    }

    public function providerGetMetadataPath()
    {
        return [
            ['oe/module/'],
            ['oe/module'],
        ];
    }

    /**
     * Return full path to module metadata.
     *
     * @parameter    string $sModuleId
     *
     * @dataProvider providerGetMetadataPath
     *
     * @return bool
     */
    public function testGetMetadataPath($sModuleId)
    {
        $sModId = 'testModule';

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, ['getModulesDir']);
        $oConfig->expects($this->any())
            ->method('getModulesDir')
            ->will($this->returnValue('/var/path/to/modules/'));

        $oModuleStub = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getModulePath', 'getConfig']);
        $oModuleStub->expects($this->any())
            ->method('getModulePath')
            ->will($this->returnValue($sModuleId));

        $oModuleStub->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($oConfig));

        $aModule = ['id' => $sModId];
        /** @var oxModule $oModule */
        $oModule = $oModuleStub;
        $oModule->setModuleData($aModule);

        $this->assertEquals('/var/path/to/modules/oe/module/metadata.php', $oModule->getMetadataPath());

        return true;
    }

    /**
     * oxModule::testGetModuleFullPaths() test case
     *
     * @return null
     */
    public function testGetModuleFullPath()
    {
        $sModId = 'testModule';

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, ['getModulesDir']);
        $oConfig->expects($this->any())
            ->method('getModulesDir')
            ->will($this->returnValue('/var/path/to/modules/'));

        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getModulePath', 'getConfig']);
        $oModule->expects($this->any())
            ->method('getModulePath')
            ->with($this->equalTo($sModId))
            ->will($this->returnValue('oe/module/'));

        $oModule->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($oConfig));

        $this->assertEquals('/var/path/to/modules/oe/module/', $oModule->getModuleFullPath($sModId));
    }

    /**
     * oxModule::testGetModuleFullPaths() test case
     *
     * @return null
     */
    public function testGetModuleFullPathWhenModuleIdNotGiven()
    {
        $sModId = 'testModule';

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, ['getModulesDir']);
        $oConfig->expects($this->any())
            ->method('getModulesDir')
            ->will($this->returnValue('/var/path/to/modules/'));

        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getModulePath', 'getConfig']);
        $oModule->expects($this->any())
            ->method('getModulePath')
            ->with($this->equalTo($sModId))
            ->will($this->returnValue('oe/module/'));

        $oModule->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($oConfig));

        $aModule = ['id' => $sModId];
        $oModule->setModuleData($aModule);

        $this->assertEquals('/var/path/to/modules/oe/module/', $oModule->getModuleFullPath());
    }

    /**
     * oxModule::testGetModuleFullPaths() test case
     *
     * @return null
     */
    public function testGetModuleFullPathWhenNoModulePathExists()
    {
        $sModId = 'testModule';

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, ['getModulesDir']);
        $oConfig->expects($this->any())
            ->method('getModulesDir')
            ->will($this->returnValue('/var/path/to/modules/'));

        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getModulePath', 'getConfig']);
        $oModule->expects($this->any())
            ->method('getModulePath')
            ->with($this->equalTo($sModId))
            ->will($this->returnValue(null));

        $oModule->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($oConfig));

        $this->assertEquals(false, $oModule->getModuleFullPath($sModId));
    }

    /**
     * oxModule::getId() test case
     */
    public function testGetId()
    {
        $aModule = [
            'id' => 'testModuleId',
        ];

        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertEquals('testModuleId', $oModule->getId());
    }

    public function testGetFilesWhenModuleHasFiles()
    {
        $aModule = [
            'id'    => 'testModuleId',
            'files' => ['class' => 'vendor/module/path/class.php'],
        ];

        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertEquals(['class' => 'vendor/module/path/class.php'], $oModule->getFiles());
    }

    public function testGetFilesWhenModuleHasNoFiles()
    {
        $aModule = [
            'id' => 'testModuleId',
        ];

        $oModule = oxNew('oxModule');
        $oModule->setModuleData($aModule);

        $this->assertEquals([], $oModule->getFiles());
    }

    /**
     * @covers OxidEsales\Eshop\Core\Module\Module::getControllers()
     */
    public function testGetControllersWithMissingControllersKey()
    {
        $metaData = [
            'id' => 'testModuleId',
        ];

        $module = oxNew(Module::class);
        $module->setModuleData($metaData);

        $this->assertEquals([], $module->getControllers(), 'If key controllers is not set in metadata.php, Module::getControllers() will return an empty array');
    }

    /**
     * @covers OxidEsales\Eshop\Core\Module\Module::getControllers()
     *
     * @dataProvider dataProviderTestGetControllersWithExistingControllers
     *
     * @param $metaDataControllers
     * @param $expectedResult
     * @param $message
     */
    public function testGetControllersWithExistingControllers($metaDataControllers, $expectedResult, $message)
    {
        $expectedControllers = ['controller_id' => 'ControllerName'];

        $metaData = [
            'id' => 'testModuleId',
            'controllers' => $metaDataControllers,
        ];

        $module = oxNew(Module::class);
        $module->setModuleData($metaData);

        $this->assertEquals($expectedResult, $module->getControllers(), $message);
    }

    public function dataProviderTestGetControllersWithExistingControllers()
    {
        return [
            [
                'metaDataControllers' => ['controller_id' => 'ControllerName'],
                'expectedResult' => ['controller_id' => 'ControllerName'],
                'message' => 'Controller value is not converted to lowercase',
            ],
            [
                'metaDataControllers' => ['Controller_Id' => 'ControllerName'],
                'expectedResult' => ['controller_id' => 'ControllerName'],
                'message' => 'Controller Id is converted to lowercase',
            ],
            [
                'metaDataControllers' => [],
                'expectedResult' => [],
                'message' => 'An empty array is returned, if controllers is an empty array',
            ],
            [
                'metaDataControllers' => null,
                'expectedResult' => [],
                'message' => 'An empty array is returned, if controllers is null',
            ],
        ];
    }

    /**
     * If the value for key controllers in metadata.php is set, but not an array an exception will be thrown
     *
     * @covers OxidEsales\Eshop\Core\Module\Module::getControllers()
     *
     * @dataProvider dataProviderTestGetControllersWithWrongMetadataValue
     *
     * @param $metaDataControllers
     * @param $expectedException
     */
    public function testGetControllersWithWrongMetadataValue($metaDataControllers, $expectedException)
    {
        $this->expectException($expectedException);
        $metaData = [
            'id' => 'testModuleId',
            'controllers' => $metaDataControllers,
        ];

        $module = oxNew(Module::class);
        $module->setModuleData($metaData);

        $module->getControllers();
    }

    public function dataProviderTestGetControllersWithWrongMetadataValue()
    {
        $expectedException = \InvalidArgumentException::class;

        return [
          [
              'metaDataControllers' => false,
              'expectedException' => $expectedException,
          ],
          [
              'metaDataControllers' => '',
              'expectedException' => $expectedException,
          ],
          [
              'metaDataControllers' => 'string',
              'expectedException' => $expectedException,
          ],
          [
              'metaDataControllers' => 1,
              'expectedException' => $expectedException,
          ],
          [
              'metaDataControllers' => new \stdClass(),
              'expectedException' => $expectedException,
          ],
        ];
    }

    public function testGetSmartyPluginDirectories()
    {
        $directories = [
            'first'      => '\first',
            'and second' => 'second',
        ];
        $module = oxNew(Module::class);
        $module->setModuleData(['smartyPluginDirectories' => $directories]);

        $this->assertSame(
            $directories,
            $module->getSmartyPluginDirectories()
        );
    }

    /**
     * @param string $invalidValue
     *
     * @dataProvider invalidSmartyPluginDirectoriesValueProvider
     */
    public function testGetSmartyPluginDirectoriesWithInvalidValue($invalidValue)
    {
        $this->expectException(\InvalidArgumentException::class);
        $module = oxNew(Module::class);
        $module->setModuleData(['smartyPluginDirectories' => $invalidValue]);

        $module->getSmartyPluginDirectories();
    }

    public function invalidSmartyPluginDirectoriesValueProvider()
    {
        return [
            [false],
            ['string'],
            [''],
            [0],
        ];
    }

    /**
     * oxModule::hasMetadata() test case
     *
     * @return null
     */
    public function testHasMetadata()
    {
        $oModule = $this->getProxyClass('oxmodule');
        $oModule->setNonPublicVar('_blMetadata', false);
        $this->assertFalse($oModule->hasMetadata());

        $oModule->setNonPublicVar('_blMetadata', true);
        $this->assertTrue($oModule->hasMetadata());
    }

    /**
     * oxModule::isRegistered() test case
     *
     * @return null
     */
    public function testIsRegistered()
    {
        $oModule = $this->getProxyClass('oxmodule');
        $oModule->setNonPublicVar('_blRegistered', false);
        $this->assertFalse($oModule->isRegistered());

        $oModule->setNonPublicVar('_blRegistered', true);
        $this->assertTrue($oModule->isRegistered());
    }

    /**
     * oxModule::getTitle() test case
     *
     * @return null
     */
    public function testGetTitle()
    {
        $iLang = oxRegistry::getLang()->getTplLanguage();
        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getInfo']);
        $oModule->expects($this->once())->method('getInfo')->with($this->equalTo('title'), $this->equalTo($iLang))->will($this->returnValue('testTitle'));

        $this->assertEquals('testTitle', $oModule->getTitle());
    }

    /**
     * oxModule::getDescription() test case
     *
     * @return null
     */
    public function testGetDescription()
    {
        $iLang = oxRegistry::getLang()->getTplLanguage();
        $oModule = $this->getMock(\OxidEsales\Eshop\Core\Module\Module::class, ['getInfo']);
        $oModule->expects($this->once())->method('getInfo')->with($this->equalTo('description'), $this->equalTo($iLang))->will($this->returnValue('testDesc'));

        $this->assertEquals('testDesc', $oModule->getDescription());
    }

    public function testGetIdByPathWithProjectConfiguration()
    {
        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration
            ->setId('testModule')
            ->setPath('oe/testModule');

        $container = ContainerFactory::getInstance()->getContainer();
        $shopConfigurationDao = $container->get(ShopConfigurationDaoBridgeInterface::class);

        $shopConfiguration = $shopConfigurationDao->get();
        $shopConfiguration->addModuleConfiguration($moduleConfiguration);

        $shopConfigurationDao->save($shopConfiguration);

        $module = 'oe/testModule/mytest';

        $moduleClass = oxNew(Module::class);
        $this->assertEquals('testModule', $moduleClass->getIdByPath($module));
    }

    public function testGetIdByPathUnknownPath()
    {
        $aDisabledModules = ['test1'];
        $aModulePaths = ['ModuleName2' => 'oe/ModuleName2'];
        $this->getConfig()->setConfigParam('aDisabledModules', $aDisabledModules);
        $this->getConfig()->setConfigParam('aModulePaths', $aModulePaths);
        $sModule = 'ModuleName/myorder';

        $oModule = oxNew('oxModule');
        $oModule->getIdByPath($sModule);
        $this->assertEquals('ModuleName', $oModule->getIdByPath($sModule));
    }

    public function testGetIdByPathUnknownPathNotDir()
    {
        $aDisabledModules = ['test1'];
        $aModulePaths = ['ModuleName2' => 'oe/ModuleName2'];
        $this->getConfig()->setConfigParam('aDisabledModules', $aDisabledModules);
        $this->getConfig()->setConfigParam('aModulePaths', $aModulePaths);
        $sModule = 'myorder';

        $oModule = oxNew('oxModule');
        $oModule->getIdByPath($sModule);
        $this->assertEquals('myorder', $oModule->getIdByPath($sModule));
    }
}
