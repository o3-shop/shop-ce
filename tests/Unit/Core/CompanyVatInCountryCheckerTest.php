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

use \oxCompanyVatIn;
use oxCompanyVatInCountryChecker;
use \oxField;

class CompanyVatInCountryCheckerTest extends \OxidTestCase
{
    public function testGetCountry_set()
    {
        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oCountry = oxNew('oxCountry');

        $oChecker->setCountry($oCountry);
        $this->assertSame($oCountry, $oChecker->getCountry());
    }

    public function testGetCountry_empty()
    {
        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $this->assertNull($oChecker->getCountry());
    }

    public function testValidate_countryNotSet()
    {
        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oVatIn = new oxCompanyVatIn('DE1234');

        $this->assertFalse($oChecker->validate($oVatIn));
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate($sCountryCode, $sVatIn, $blExpectValidationResult)
    {
        $oVatIn = new oxCompanyVatIn($sVatIn);
        $oCountry = oxNew('oxCountry');
        $oCountry->oxcountry__oxvatinprefix = new oxField($sCountryCode);

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oChecker->setCountry($oCountry);

        $this->assertSame($blExpectValidationResult, $oChecker->validate($oVatIn));
    }

    public function validateDataProvider()
    {
        return array(
            array('LT', 'LT12345', true),
            array('LT', '', false),
            array('LT', '11', false),
            array('LT', 'ab', false),
            array('DE', 'LT12345', false),
        );
    }

    /**
     * Test for bug #4212
     */
    public function testValidateGreece()
    {
        $oVatIn = new oxCompanyVatIn('EL123');
        $oCountry = oxNew('oxCountry');
        $oCountry->load('a7c40f633114e8fc6.25257477');

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oChecker->setCountry($oCountry);
        $this->assertTrue($oChecker->validate($oVatIn));
    }

    public function testValidate_notValid_errorMessage()
    {
        $oVatIn = new oxCompanyVatIn('LT12345');
        $oCountry = oxNew('oxCountry');
        $oCountry->oxcountry__oxvatinprefix = new oxField('DE');

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oChecker->setCountry($oCountry);
        $oChecker->validate($oVatIn);

        $this->assertSame(oxCompanyVatInCountryChecker::ERROR_ID_NOT_VALID, $oChecker->getError());
    }

    public function testValidate_notValidWrongDataType_errorMessage()
    {
        $oVatIn = new oxCompanyVatIn('LT12345');
        $oCountry = oxNew('oxCountry');
        $oCountry->oxcountry__oxvatinprefix = new oxField(1);

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oChecker->setCountry($oCountry);
        $oChecker->validate($oVatIn);

        $this->assertSame(oxCompanyVatInCountryChecker::ERROR_ID_NOT_VALID, $oChecker->getError());
    }

    public function testValidate_valid_noErrorMessage()
    {
        $oVatIn = new oxCompanyVatIn('LT12345');
        $oCountry = oxNew('oxCountry');
        $oCountry->oxcountry__oxvatinprefix = new oxField('LT');

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oChecker->setCountry($oCountry);
        $oChecker->validate($oVatIn);

        $this->assertSame('', $oChecker->getError());
    }
}
