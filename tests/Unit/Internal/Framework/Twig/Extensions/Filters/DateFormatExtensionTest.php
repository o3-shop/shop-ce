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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters\DateFormatExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\DateFormatHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class DateFormatExtensionTest
 *
 * Test overrides DIRECTORY_SEPARATOR const
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Extensions\Filters
 */
class DateFormatExtensionTest extends TestCase
{
    /**
     * @var DateFormatExtension
     */
    private $dateFormatExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $dateFormatHelper = new DateFormatHelper();
        $this->dateFormatExtension = new DateFormatExtension($dateFormatHelper);
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
            //dummy data
            ['', '', '', null],
            ['foo', '', '', null],
            ['', 'foo', '', null],
            ['', '', 'foo', false],
            ['foo', 'foo', '', 'foo'],
            ['foo', 'foo', 'foo', 'foo'],

            //provided input string
            [20181201101525, '%Y-%m-%d %H:%M:%S', '', '2018-12-01 10:15:25'],           //mysql format
            [1543850519, '%Y-%m-%d %H:%M:%S', '', '2018-12-03 16:21:59'],               //time()
            ['Dec 03 15:21:59 2018', '%Y-%m-%d %H:%M:%S', '', '2018-12-03 15:21:59'],   //string time

            //no input string provided, default date used
            ['', '%Y-%m-%d %H:%M:%S', 20181201101525, '2018-12-01 10:15:25'],           //mysql format
            ['', '%Y-%m-%d %H:%M:%S', 1543850519, '2018-12-03 16:21:59'],               //time()
            ['', '%Y-%m-%d %H:%M:%S', 'Dec 03 15:21:59 2018', '2018-12-03 15:21:59'],   //string time
        ];
    }

    /**
     * @param mixed  $string
     * @param string $format
     * @param string $default_date
     * @param string $expectedDate
     *
     * @dataProvider provider
     * @covers       \OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters\DateFormatExtension::dateFormat
     */
    public function testDateFormat($string, $format, $default_date, $expectedDate)
    {
        $actualDate = $this->dateFormatExtension->dateFormat($string, $format, $default_date);
        $this->assertEquals($expectedDate, $actualDate);
    }
}
