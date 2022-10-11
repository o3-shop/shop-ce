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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\EshopCommunity\Core\SortingValidator;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * @covers \OxidEsales\EshopCommunity\Core\SortingValidator
 */
class SortingValidatorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setAllowedSortingColumns();
    }

    public function testWhenSortOrderIsValid()
    {
        $sortingValidator = new SortingValidator();
        $sortingOrders = $sortingValidator->getSortingOrders();
        $this->assertTrue($sortingValidator->isValid('testColumn1', $sortingOrders[0]));
    }

    public function testWhenSortColumnIsValid()
    {
        $sortingValidator = new SortingValidator();
        $sortingOrders = $sortingValidator->getSortingOrders();
        $this->assertTrue($sortingValidator->isValid('testColumn1', $sortingOrders[0]));
    }

    public function invalidValuesDataProvider()
    {
        return [
            ['invalid_value'],
            [''],
            [null],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidValuesDataProvider
     */
    public function testWhenSortOrderInvalid($value)
    {
        $sortingValidator = new SortingValidator();
        $this->assertFalse($sortingValidator->isValid('testColumn1', $value));
    }

    /**
     * @param $value
     * @dataProvider invalidValuesDataProvider
     */
    public function testWhenSortColumnInvalid($value)
    {
        $sortingValidator = new SortingValidator();
        $sortingOrders = $sortingValidator->getSortingOrders();
        $this->assertFalse($sortingValidator->isValid($value, $sortingOrders[0]));
    }

    private function setAllowedSortingColumns()
    {
        $this->setConfigParam(
            'aSortCols',
            [
                'testColumn1',
                'testColumn2',
            ]
        );
    }
}
