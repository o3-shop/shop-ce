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

use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use oxTestModules;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group module
 * @package Unit\Core
 */
class ModuleChainsGeneratorTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    public function testGetActiveModuleChain()
    {
        $aModuleChain = array("oe/moduleName2/myorder");

        /** @var ModuleVariablesLocator|MockObject $oUtilsObject */
        $moduleVariablesLocator = $this->getMock(\OxidEsales\Eshop\Core\Module\ModuleVariablesLocator::class, array('getModuleVariable'), array(), '', false);
        $valueMap = array(
            array('aDisabledModules', array('moduleName')),
            array('aModulePaths', array("moduleName2" => "oe/moduleName2", "moduleName" => "oe/moduleName")),
        );
        $moduleVariablesLocator->expects($this->any())->method('getModuleVariable')->will($this->returnValueMap($valueMap));

        $moduleChainsGenerator = oxNew('oxModuleChainsGenerator', $moduleVariablesLocator);

        $this->assertEquals($aModuleChain, $moduleChainsGenerator->filterInactiveExtensions($aModuleChain));
    }

    /**
     *
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::onModuleExtensionCreationError
     */
    public function testOnModuleExtensionCreationError()
    {
        $moduleChainsGeneratorMock = $this->generateModuleChainsGeneratorWithNonExistingFileConfiguration();

        $actualClassName = $moduleChainsGeneratorMock->createClassChain('content');

        $this->assertEquals('content', $actualClassName);
        $this->assertLoggedException(SystemComponentException::class);
    }

    /**
     *
     * @return \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator
     */
    private function generateModuleChainsGeneratorWithNonExistingFileConfiguration()
    {
        /** @var ModuleVariablesLocator|MockObject $oUtilsObject */
        $moduleVariablesLocatorMock = $this->getMock(
            \OxidEsales\Eshop\Core\Module\ModuleVariablesLocator::class,
            ['getModuleVariable'],
            [],
            '',
            false
        );
        $valueMap = [
            ['aModules', ['content' => 'notExistingClass']],
            ['aDisabledModules', []]
        ];
        $moduleVariablesLocatorMock
            ->expects($this->any())
            ->method('getModuleVariable')
            ->will($this->returnValueMap($valueMap));

        $moduleChainsGeneratorMock = $this->getMock(
            \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::class,
            ['getConfigDebugMode', 'isUnitTest'],
            [$moduleVariablesLocatorMock]
        );

        /**
         * It is fake not to be a unit test in order to execute the error handling, which is not done for the rest of
         * the tests.
         */
        $moduleChainsGeneratorMock
            ->expects($this->any())
            ->method('isUnitTest')
            ->will($this->returnValue(false));

        return $moduleChainsGeneratorMock;
    }
}
