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

use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\FormatDateLogic;
use PHPUnit\Framework\TestCase;

/**
 * Class FormatDateLogicTest
 */
class FormatDateLogicTest extends TestCase
{

    /** @var FormatDateLogic */
    private $formDateLogic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formDateLogic = new FormatDateLogic();
    }

    public function testFormdateWithDatetime(): void
    {
        $input = '01.08.2007 11.56.25';
        $expected = "2007-08-01 11:56:25";

        $this->assertEquals($expected, $this->formDateLogic->formdate($input, 'datetime', true));
    }

    public function testFormdateWithTimestamp(): void
    {
        $input = '20070801115625';
        $expected = "2007-08-01 11:56:25";

        $this->assertEquals($expected, $this->formDateLogic->formdate($input, 'timestamp', true));
    }

    public function testFormdateWithDate(): void
    {
        $input = '2007-08-01 11:56:25';
        $expected = "2007-08-01";

        $this->assertEquals($expected, $this->formDateLogic->formdate($input, 'date', true));
    }

    public function testFormdateUsingObject(): void
    {
        $expected = "2007-08-01 11:56:25";

        $field = new Field();
        $field->fldmax_length = "0";
        $field->fldtype = 'datetime';
        $field->setValue('01.08.2007 11.56.25');

        $this->assertEquals($expected, $this->formDateLogic->formdate($field, 'datetime'));
    }
}
