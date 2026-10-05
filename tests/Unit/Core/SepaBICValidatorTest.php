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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

/**
 * oxSepaValidator test class
 *
 * Can validate:
 *  - IBAN (International Business Account Number)
 *  - IBAN Registry (all IBAN lengths by country)
 *  - BIC (Bank International Code)
 */
class SepaBICValidatorTest extends \OxidTestCase
{
    /**
     * BIC validation data provider
     *
     * @return array
     */
    public function providerIsValid_validBIC_true()
    {
        return [
            ['ASPKAT2L'],
            ['AAAACCXX'],
            ['AAAACC22'],
            ['AAAACCXXHHH'],
            ['AAAACC33555'],
            ['AAAACCXX555'],
            [' AAAACCXX'],
            ['AAAACCXX '],
            ["\tAAAACCXX"],
            ["AAAACCXX\n"],
            ["AAAACCXX\n\r"],
            // Fix for bug entry 0005564: oxSepaValidator::isValidBIC($sBIC) only verifies substring of BIC
            ['COBADEHD055'],
        ];
    }

    /**
     * Test case to check BIC validation
     *
     * @dataProvider providerIsValid_validBIC_true
     */
    public function testIsValid_validBIC_true($sBIC)
    {
        $oSepaBICValidator = oxNew('oxSepaBICValidator');

        $this->assertTrue($oSepaBICValidator->isValid($sBIC), 'BIC must be valid');
    }

    /**
     * BIC validation data provider
     *
     * @return array
     */
    public function providerIsValid_invalidBIC_false()
    {
        return [
            ['AAAACCX'],
            ['AAAACCXXX'],
            ['AAAACCXXXX'],
            ['AAAACC2233'],
            ['AAAACC2233*'],
            ['AAAACC224444X'],
            ['AAAACC224444XX'],
            ['AAA1CC22'],
            ['1AAAACXX'],
            ['A1AAACXX'],
            ['AA1AACXX'],
            ['AAA1ACXX'],
            ['AAAA1CXX'],
            ['AAAAC1XX'],
            ['AAAAC122'],
            ['ASPK AT 2L'],
            ["ASPK\tAT\t2L"],
            ['123 ASPKAT2L'],
            ['_ASPKAT2L'],
            ['ASPKAT2'],
            ['ASP_AT2L'],
            ['ASPK*T2L'],
            ['ASPKA-2L'],
            ['AAAßCCXX'],
            ['AAAACßXX'],
            ['AAAACCXö'],
            // Fix for bug entry 0005564: oxSepaValidator::isValidBIC($sBIC) only verifies substring of BIC
            ['123COBADEHD055ABC'],
        ];
    }

    /**
     * Test case to check BIC validation
     *
     * @dataProvider providerIsValid_invalidBIC_false
     */
    public function testIsValid_invalidBIC_false($sBIC)
    {
        $oSepaBICValidator = oxNew('oxSepaBICValidator');

        $this->assertFalse($oSepaBICValidator->isValid($sBIC), 'BIC must be not valid');
    }
}
