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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Extensions\Filters;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters\SmartWordwrapExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\SmartWordwrapLogic;
use PHPUnit\Framework\TestCase;

class SmartWordwrapExtensionTest extends TestCase
{
    public function provider()
    {
        return [
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas risus ipsum, ornare id scelerisque non, porta nec nulla.',
                ],
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas risus ipsum,
ornare id scelerisque non, porta nec nulla.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 20,
                ],
                'Lorem ipsum dolor
sit amet,
consectetur
adipiscing elit.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 20,
                    'break' => '<br/>',
                ],
                'Lorem ipsum dolor<br/>sit amet,<br/>consectetur<br/>adipiscing elit.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 10,
                    'cutRows' => 7,
                ],
                'Lorem
ipsum
dolor sit
amet,
consectetu
r
adipisc...',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 20,
                    'tolerance' => -30,
                ],
                'Lorem ipsum dolor
sit amet,
consectetur
adipiscing elit.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 20,
                    'tolerance' => 30,
                ],
                'Lorem ipsum dolor
sit amet,
consectetur
adipiscing elit.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 20,
                    'tolerance' => 150,
                ],
                'Lorem ipsum dolor
sit amet,
consectetur
adipiscing elit.',
            ],
            [
                [
                    'string' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'length' => 10,
                    'cutRows' => 7,
                    'etc' => '[...]',
                ],
                'Lorem
ipsum
dolor sit
amet,
consectetu
r
adipi[...]',
            ],
        ];
    }

    /**
     * @param array $params
     * @param string $expectedString
     *
     * @covers       \OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters\SmartWordwrapExtension::smartWordWrap
     * @dataProvider provider
     */
    public function testSmartWordWrap($params, $expectedString)
    {
        $smartWordWrapLogic = new SmartWordwrapLogic();
        $smartWordWrapExtension = new SmartWordwrapExtension($smartWordWrapLogic);
        $string = $params['string'];
        $length = isset($params['length']) ? $params['length'] : 80;
        $break = isset($params['break']) ? $params['break'] : "\n";
        $cutRows = isset($params['cutRows']) ? $params['cutRows'] : 0;
        $tolerance = isset($params['tolerance']) ? $params['tolerance'] : 0;
        $etc = isset($params['etc']) ? $params['etc'] : '...';

        $actualString = $smartWordWrapExtension->smartWordwrap($string, $length, $break, $cutRows, $tolerance, $etc);
        $this->assertEquals($expectedString, $actualString);
    }
}
