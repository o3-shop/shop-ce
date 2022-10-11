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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\DateFormatHelper;
use PHPUnit\Framework\TestCase;

class DateFormatHelperTest extends TestCase
{

    /**
     * @return array
     */
    public function provider()
    {
        return [
            ['%D %h %n %r %R %t %T', 1543850519, "%m/%d/%y %b 
 %I:%M:%S %p %H:%M 	 %H:%M:%S"],
            ['%T %t %R %r %n %h %D', 1543850519, "%H:%M:%S 	 %H:%M %I:%M:%S %p 
 %b %m/%d/%y"],
            ['%e', 691200, " 9"],
            ['%l', 46800, " 2"],
            ['foo', '', "foo"],
        ];
    }

    /**
     * @param string $format
     * @param int    $timestamp
     * @param string $expectedFormat
     *
     * @dataProvider provider
     * @covers       \OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\DateFormatHelper::fixWindowsTimeFormat
     */
    public function testFixWindowsTimeFormat($format, $timestamp, $expectedFormat)
    {
        $dateFormatHelper = new DateFormatHelper();
        $actualFormat = $dateFormatHelper->fixWindowsTimeFormat($format, $timestamp);
        $this->assertEquals($expectedFormat, $actualFormat);
    }
}
