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
namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Routing;

use OxidEsales\EshopCommunity\Core\Routing\Module\ClassProviderStorage;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\EshopCommunity\Core\Routing\ModuleControllerMapProvider;
use OxidEsales\Eshop\Core\Module\ModuleVariablesLocator;

/**
 * Test the module ControllerProvider.
 *
 * @package Unit\Core\Routing\Module
 */
class ModuleControllerMapProviderTest extends UnitTestCase
{

    /**
     * Set up fixture
     */
    protected function setUp(): void
    {
        parent::setUp();

        ModuleVariablesLocator::resetModuleVariables();
    }

    /**
     * The data provider for the method testGetControllerMapWithModules.
     *
     * @return array
     */
    public function dataProviderTestPossibleCombinationsOfActiveModules()
    {
        return [
            // no module active
            [
                [],
                []
            ],

            // 2 modules active
            [
                [
                    'module1' => [
                        'module1controller1' => 'a',
                        'module1controller2' => 'b'
                    ],
                    'module2' => [
                        'module2controller1' => 'c',
                        'module2controller2' => 'd'
                    ]
                ],
                [
                    'module1controller1' => 'a',
                    'module1controller2' => 'b',
                    'module2controller1' => 'c',
                    'module2controller2' => 'd'
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderTestPossibleCombinationsOfActiveModules
     *
     * @param array $controllerKeysFromStorage The controller key mapping we get by the storage
     * @param array $expectedControllerKeys    The controller key mapping we expect to be returned
     *
     */
    public function testGetControllerMapWithModules($controllerKeysFromStorage, $expectedControllerKeys)
    {
        $this->getConfig()->saveShopConfVar('aarr', ClassProviderStorage::STORAGE_KEY, $controllerKeysFromStorage);
        $this->assertModuleControllersNotCached();

        /** @var \OxidEsales\EshopCommunity\Core\Routing\ModuleControllerMapProvider|\PHPUnit\Framework\MockObject\MockObject $moduleControllerMapProviderMock */
        $moduleControllerMapProviderMock = oxNew(ModuleControllerMapProvider::class);

        $this->assertSame($expectedControllerKeys, $moduleControllerMapProviderMock->getControllerMap());
        $this->assertModuleControllersCached($controllerKeysFromStorage);
    }

    /**
     * Assert that module controller data is cached in filesystem.
     *
     * @param array $expectedControllerKeys
     */
    protected function assertModuleControllersCached($expectedControllerKeys)
    {
        $subShopSpecificCache = $this->getFileCache();
        $this->assertEquals($expectedControllerKeys, $subShopSpecificCache->getFromCache(ClassProviderStorage::STORAGE_KEY));
    }

    /**
     * Assert that module controller data is not cached in filesystem.
     */
    protected function assertModuleControllersNotCached()
    {
        $subShopSpecificCache = $this->getFileCache();
        $this->assertNull($subShopSpecificCache->getFromCache(ClassProviderStorage::STORAGE_KEY));
    }

    /**
     * Get a file cache object
     */
    private function getFileCache()
    {
        $shopId = $this->getTestConfig()->getShopId();

        $shopIdCalculatorMock = $this->getMock('\OxidEsales\EshopCommunity\Core\ShopIdCalculator', array('getShopId'), array(), '', false);
        $shopIdCalculatorMock->expects($this->any())->method('getShopId')->will($this->returnValue($shopId));

        $subShopSpecificCache = oxNew('\OxidEsales\EshopCommunity\Core\SubShopSpecificFileCache', $shopIdCalculatorMock);

        return $subShopSpecificCache;
    }
}
