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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup\Controller;

use OxidEsales\EshopCommunity\Setup\Controller\ModuleStateMapGenerator;
use \PHPUnit\Framework\TestCase;

class ModuleStateMapGeneratorTest extends TestCase
{
    public function testCanCreateSUT()
    {
        $this->getSUT([]);
    }

    public function testCanProduceEmptyModuleStateMap()
    {
        $expectedModuleStateMap = [];

        $sut = $this->getSUT([]);
        $actualModuleStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualModuleStateMap);
    }

    public function testCanProduceValidModuleStateMap()
    {
        $expectedModuleStateMap = [
            'group_a' => [
                [
                    'module' => 'module_a',
                    'state' => 0,
                ],
                [
                    'module' => 'module_b',
                    'state' => 1,
                ],
            ],
            'group_b' => [
                [
                    'module' => 'module_c',
                    'state' => 1,
                ],
                [
                    'module' => 'module_d',
                    'state' => 2,
                ],
            ],
        ];

        $sut = $this->getSUT($this->getSystemRequirementsInfo());
        $actualModuleStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualModuleStateMap);
    }

    public function testCanUseModuleStateHtmlClassConvertFunction()
    {
        $expectedModuleStateMap = [
            'group_a' => [
                [
                    'module' => 'module_a',
                    'state' => 0,
                    'class' => 'class_a'
                ],
                [
                    'module' => 'module_b',
                    'state' => 1,
                    'class' => 'class_b'
                ],
            ],
            'group_b' => [
                [
                    'module' => 'module_c',
                    'state' => 1,
                    'class' => 'class_b'
                ],
                [
                    'module' => 'module_d',
                    'state' => 2,
                    'class' => 'class_c'
                ],
            ],
        ];

        $sut = $this->getSUT($this->getSystemRequirementsInfo());
        $sut->setModuleStateHtmlClassConvertFunction($this->getModuleStateHtmlClassConvertFunction());
        $actualStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualStateMap);
    }

    public function testCanUseModuleNameTranslateFunction()
    {
        $expectedModuleStateMap = [
            'group_a' => [
                [
                    'module' => 'module_a',
                    'state' => 0,
                    'modulename' => 'translated_a'
                ],
                [
                    'module' => 'module_b',
                    'state' => 1,
                    'modulename' => 'translated_b'
                ],
            ],
            'group_b' => [
                [
                    'module' => 'module_c',
                    'state' => 1,
                    'modulename' => 'translated_c'
                ],
                [
                    'module' => 'module_d',
                    'state' => 2,
                    'modulename' => 'translated_d'
                ],
            ],
        ];

        $sut = $this->getSUT($this->getSystemRequirementsInfo());
        $sut->setModuleNameTranslateFunction($this->getModuleNameTranslateFunction());
        $actualStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualStateMap);
    }

    public function testCanUseModuleGroupNameTranslateFunction()
    {
        $expectedModuleStateMap = [
            'translated_a' => [
                [
                    'module' => 'module_a',
                    'state' => 0,
                ],
                [
                    'module' => 'module_b',
                    'state' => 1,
                ],
            ],
            'translated_b' => [
                [
                    'module' => 'module_c',
                    'state' => 1,
                ],
                [
                    'module' => 'module_d',
                    'state' => 2,
                ],
            ],
        ];

        $sut = $this->getSUT($this->getSystemRequirementsInfo());
        $sut->setModuleGroupNameTranslateFunction($this->getModuleGroupNameTranslateFunction());
        $actualStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualStateMap);
    }

    public function testCanUseAllCustomFunctions()
    {
        $expectedModuleStateMap = [
            'translated_a' => [
                [
                    'module' => 'module_a',
                    'state' => 0,
                    'class' => 'class_a',
                    'modulename' => 'translated_a',
                ],
                [
                    'module' => 'module_b',
                    'state' => 1,
                    'class' => 'class_b',
                    'modulename' => 'translated_b',

                ],
            ],
            'translated_b' => [
                [
                    'module' => 'module_c',
                    'state' => 1,
                    'class' => 'class_b',
                    'modulename' => 'translated_c',
                ],
                [
                    'module' => 'module_d',
                    'state' => 2,
                    'class' => 'class_c',
                    'modulename' => 'translated_d',
                ],
            ],
        ];

        $sut = $this->getSUT($this->getSystemRequirementsInfo());
        $sut->setModuleStateHtmlClassConvertFunction($this->getModuleStateHtmlClassConvertFunction());
        $sut->setModuleNameTranslateFunction($this->getModuleNameTranslateFunction());
        $sut->setModuleGroupNameTranslateFunction($this->getModuleGroupNameTranslateFunction());
        $actualStateMap = $sut->getModuleStateMap();

        $this->assertSame($expectedModuleStateMap, $actualStateMap);
    }

    /**
     * @dataProvider invalidFilterFunctionDataProvider
     */
    public function testExceptionRaisedWithInvalidStateClassFilter($function)
    {
        $this->expectException(\Exception::class);

        $sut = $this->getSUT([]);
        $sut->setModuleStateHtmlClassConvertFunction($function);
    }

    /**
     * @dataProvider invalidFilterFunctionDataProvider
     */
    public function testExceptionRaisedWithInvalidModuleNameTranslateFilter($function)
    {
        $this->expectException(\Exception::class);

        $sut = $this->getSUT([]);
        $sut->setModuleNameTranslateFunction($function);
    }

    /**
     * @dataProvider invalidFilterFunctionDataProvider
     */
    public function testExceptionRaisedWithInvalidModuleGroupNameTranslateFilter($function)
    {
        $this->expectException(\Exception::class);

        $sut = $this->getSUT([]);
        $sut->setModuleGroupNameTranslateFunction($function);
    }

    public function invalidFilterFunctionDataProvider()
    {
        return [
            [1],
            ['invalid'],
            [false],
            [5.5],
        ];
    }

    /**
     * @param array $systemRequirementsInfo
     * @return ModuleStateMapGenerator
     */
    private function getSUT($systemRequirementsInfo)
    {
        return new ModuleStateMapGenerator($systemRequirementsInfo);
    }

    private function getSystemRequirementsInfo()
    {
        return [
            'group_a' => [
                'module_a' => 0,
                'module_b' => 1,
            ],
            'group_b' => [
                'module_c' => 1,
                'module_d' => 2,
            ]
        ];
    }

    /**
     * @return \Closure
     */
    private function getModuleStateHtmlClassConvertFunction()
    {
        return function ($state) {
            switch ($state) {
                case 0:
                    return 'class_a';
                case 1:
                    return 'class_b';
                case 2:
                    return 'class_c';
            };

            return 'default';
        };
    }

    /**
     * @return \Closure
     */
    private function getModuleNameTranslateFunction()
    {
        return function ($moduleName) {
            switch ($moduleName) {
                case 'module_a':
                    return 'translated_a';
                case 'module_b':
                    return 'translated_b';
                case 'module_c':
                    return 'translated_c';
                case 'module_d':
                    return 'translated_d';
            };

            return 'default';
        };
    }

    /**
     * @return \Closure
     */
    private function getModuleGroupNameTranslateFunction()
    {
        return function ($moduleGroupName) {
            return $moduleGroupName === 'group_a' ? 'translated_a' : 'translated_b';
        };
    }
}
