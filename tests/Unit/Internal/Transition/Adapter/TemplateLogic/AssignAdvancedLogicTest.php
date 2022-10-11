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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AssignAdvancedLogic;
use \PHPUnit\Framework\TestCase;

class AssignAdvancedLogicTest extends TestCase
{

    /**
     * @var AssignAdvancedLogic
     */
    private $assignAdvancedLogic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignAdvancedLogic = new AssignAdvancedLogic();
    }

    public function testFormatValueString(): void
    {
        $formattedValue = $this->assignAdvancedLogic->formatValue('foo');
        $this->assertEquals('foo', $formattedValue);
    }

    public function testFormatValueArray(): void
    {
        $formattedValue = $this->assignAdvancedLogic->formatValue('array("foo" => "bar")');
        $this->assertEquals(['foo' => 'bar'], $formattedValue);
    }

    public function testFormatValueRange(): void
    {
        $formattedValue = $this->assignAdvancedLogic->formatValue('range(1,3)');
        $this->assertEquals([1, 2, 3], $formattedValue);
    }
}
