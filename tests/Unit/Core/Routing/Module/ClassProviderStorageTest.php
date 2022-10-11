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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Routing\Module;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\EshopCommunity\Core\Routing\Module\ClassProviderStorage;

/**
 * Test the module controller provider cache.
 *
 * @package Unit\Core\Routing\Module
 */
class ControllerProviderCacheTest extends UnitTestCase
{
    /**
     * Standard setup method, called before every method.
     *
     * Calls parent method first.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $controllerProviderCache = oxNew(ClassProviderStorage::class);
        $controllerProviderCache->set(null);
    }

    /**
     * Test, that the creation works properly.
     *
     * @return ClassProviderStorage A fresh controller provider cache.
     */
    public function testCreation()
    {
        $controllerProviderCache = oxNew(ClassProviderStorage::class);

        $this->assertTrue(is_a($controllerProviderCache, ClassProviderStorage::class));

        return $controllerProviderCache;
    }

    /**
     * Test, that the cache leads to null, if we don't set anything before.
     */
    public function testGetWithoutSetValueBefore()
    {
        $cache = $this->testCreation();

        $result = $cache->get();

        $this->assertEmpty($result);

        return $cache;
    }

    /**
     * Test, that the cache leads to the before set value.
     */
    public function testSetLowercasesModuleIdAndControllerKey()
    {
        $value = [
            'ModuleA' => [
                'MyControllerKeyOne' => '\MyNamespace\MyClassOne',
                'MyControllerKeyTwo' => '\MyNamespace\MyClassTwo'
            ],
            'ModuleB' => [
                'MyControllerKeyThree' => '\MyNamespace\MyClassThree',
                'MyControllerKeyFour' => '\MyNamespace\MyClassFour'
            ]
        ];
        $expectedValue = [
            'modulea' => [
                'mycontrollerkeyone' => '\MyNamespace\MyClassOne',
                'mycontrollerkeytwo' => '\MyNamespace\MyClassTwo'
            ],
            'moduleb' => [
                'mycontrollerkeythree' => '\MyNamespace\MyClassThree',
                'mycontrollerkeyfour' => '\MyNamespace\MyClassFour'
            ]
        ];

        $cache = $this->testCreation();
        $cache->set($value);

        $this->assertEquals($cache->get(), $expectedValue);

        return $cache;
    }
}
