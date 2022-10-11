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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Smarty\Plugin;

use OxidEsales\EshopCommunity\Core\Smarty\Plugin\StringInputParser;
use PHPUnit\Framework\TestCase;

class StringInputParserTest extends TestCase
{
    /** @dataProvider arraysDataProvider */
    public function testParseArray(string $input, array $expected): void
    {
        $actual = (new StringInputParser())->parseArray($input);

        $this->assertSame($expected, $actual);
    }

    /** @dataProvider rangesDataProvider */
    public function testParseRange(string $input, array $expected): void
    {
        $actual = (new StringInputParser())->parseRange($input);

        $this->assertSame($expected, $actual);
    }

    public function arraysDataProvider(): array
    {
        return [
            [
                '[]',
                [],
            ],
            [
                'array()',
                [],
            ],
            [
                'array("abc", array())',
                [
                    'abc',
                    [],
                ]
            ],
            [
                'array(
                1,
                //comment
                /* Another comment array */
                []
                )',
                [
                    1,
                    [],
                ],
            ],
            [
                '[123]',
                [123],
            ],
            [
                'array("ab - c"=>123, [], array(1=>array("DEF\'")))',
                [
                    'ab - c' => 123,
                    [],
                    [1 => [
                        'DEF\'',
                        ],
                        ],
                    ],
            ],
            [
                "array('key_1_2_3' => ['array' => 'ARRAY', 'key' => '___', 'null' => null,'false' => false, 'true' => TRUE, 'ucFirstTrue' => True])",
                [
                    'key_1_2_3' => [
                        'array' => 'ARRAY',
                        'key' => '___',
                        'null' => null,
                        'false' => false,
                        'true' => true,
                        'ucFirstTrue' => true,
                    ]
                ],
            ],
            [
                '[
                    1, [],
                    2,
                    3,
                ]',
                [
                    1,
                    [],
                    2,
                    3,
                ],
            ],
        ];
    }

    public function rangesDataProvider(): array
    {
        return [
            [
                'range(1,5)',
                [1, 2, 3, 4, 5],
            ],
            [
                'RAnGE(50,   51)',
                [50, 51],
            ],
            [
                'range("A" , "C")',
                ['A', 'B', 'C'],
            ],
            [
                'range(1, 10, 3)',
                [1, 4, 7, 10],
            ],
            [
                'range(
                "A" ,
                 \'C\'
                 )',
                ['A', 'B', 'C'],
            ],
        ];
    }
}
