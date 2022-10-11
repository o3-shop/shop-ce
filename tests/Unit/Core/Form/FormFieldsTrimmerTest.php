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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Form;

use OxidEsales\TestingLibrary\UnitTestCase;

use OxidEsales\Eshop\Core\Form\FormFields;
use OxidEsales\Eshop\Core\Form\FormFieldsTrimmer;

/**
 * Class FormFieldsTrimmerTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Core\Form
 */
class FormFieldsTrimmerTest extends UnitTestCase
{
    public function testTrimming()
    {
        $untrimmedFields = oxNew(FormFields::class, [
            'zip'   => '  79098 ',
            'city'  => 'Freiburg im Breisgau ',
            [
                'year'  => ' 1986',
                'month' => '04 ',
            ],
        ]);

        $trimmedFields = new \ArrayIterator([
            'zip'   => '79098',
            'city'  => 'Freiburg im Breisgau',
            [
                'year'  => '1986',
                'month' => '04',
            ],
        ]);

        $trimmer = oxNew(FormFieldsTrimmer::class);
        $fieldsAfterTrimming = $trimmer->trim($untrimmedFields);

        $this->assertEquals(
            $trimmedFields,
            $fieldsAfterTrimming
        );
    }

    public function testMustTrimStringFieldsOnly()
    {
        $untrimmedFields = oxNew(FormFields::class, [
            'string'    => ' to trim',
            'bool'      => true,
            'int'       => 5,
        ]);

        $trimmedFields = [
            'string'    => 'to trim',
            'bool'      => true,
            'int'       => 5,
        ];

        $trimmer = oxNew(FormFieldsTrimmer::class);
        $fieldsAfterTrimming = (array) $trimmer->trim($untrimmedFields);

        $this->assertSame(
            $trimmedFields,
            $fieldsAfterTrimming
        );
    }
}
