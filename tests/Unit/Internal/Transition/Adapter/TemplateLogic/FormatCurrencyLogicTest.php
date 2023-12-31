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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\FormatCurrencyLogic;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Class FormatCurrencyLogicTest
 */
class FormatCurrencyLogicTest extends UnitTestCase
{

    /** @var FormatCurrencyLogic */
    private $numberFormatLogic;

    protected function setUp(): void
    {
        $this->numberFormatLogic = new FormatCurrencyLogic();
        parent::setUp();
    }

    /**
     * @param string     $format
     * @param string|int $value
     * @param string     $expected
     *
     * @dataProvider numberFormatProvider
     */
    public function testNumberFormat($format, $value, $expected)
    {
        $this->assertEquals($expected, $this->numberFormatLogic->numberFormat($format, $value));
    }

    /**
     * @return array
     */
    public function numberFormatProvider(): array
    {
        return [
            ["EUR@ 1.00@ ,@ .@ EUR@ 2", 25000, '25.000,00'],
            ["EUR@ 1.00@ ,@ .@ EUR@ 2", 25000.1584, '25.000,16'],
            ["EUR@ 1.00@ ,@ .@ EUR@ 3", 25000.1584, '25.000,158'],
            ["EUR@ 1.00@ ,@ .@ EUR@ 0", 25000000.5584, '25.000.001'],
            ["EUR@ 1.00@ .@ ,@ EUR@ 2", 25000000.5584, '25,000,000.56'],
        ];
    }
}
