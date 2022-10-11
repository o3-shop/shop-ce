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

use \oxCompanyVatInValidator;
use \oxCompanyVatIn;

class CompanyVatInValidatorTest extends \OxidTestCase
{
    public function testGetCountry_Construct()
    {
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $this->assertSame($oCountry, $oValidator->getCountry());
    }

    public function testGetCountry_set()
    {
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oCountryOther = oxNew('oxCountry');
        $oValidator->setCountry($oCountryOther);

        $this->assertSame($oCountryOther, $oValidator->getCountry());
    }

    public function testGetError_notSet()
    {
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $this->assertSame('', $oValidator->getError());
    }

    public function testGetError_set()
    {
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);
        $oValidator->setError('Error');

        $this->assertSame('Error', $oValidator->getError());
    }

    public function testAddChecker()
    {
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oChecker = oxNew('oxCompanyVatInCountryChecker');
        $oValidator->addChecker($oChecker);

        $this->assertSame(1, count($oValidator->getCheckers()));
    }

    public function testValidate_noCheckers()
    {
        $oVatIn = new oxCompanyVatIn('LT1123');
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $this->assertFalse($oValidator->validate($oVatIn));
    }

    public function testValidate_onChecker_success()
    {
        $oVatIn = new oxCompanyVatIn('LT1123');
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oChecker = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker->expects($this->any())->method('validate')->will($this->returnValue(true));

        $oValidator->addChecker($oChecker);

        $this->assertTrue($oValidator->validate($oVatIn));
    }

    public function testValidate_onChecker_fail()
    {
        $oVatIn = new oxCompanyVatIn('LT1123');
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oChecker = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker->expects($this->any())->method('validate')->will($this->returnValue(false));
        $oChecker->expects($this->any())->method('getError')->will($this->returnValue('Error'));


        $oValidator->addChecker($oChecker);

        $this->assertFalse($oValidator->validate($oVatIn));
        $this->assertSame('Error', $oValidator->getError());
    }

    public function testValidate_2Checkers_success()
    {
        $oVatIn = new oxCompanyVatIn('LT1123');
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oChecker = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker->expects($this->any())->method('validate')->will($this->returnValue(true));

        $oValidator->addChecker($oChecker);

        $oChecker2 = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker2->expects($this->any())->method('validate')->will($this->returnValue(true));

        $oValidator->addChecker($oChecker2);

        $this->assertTrue($oValidator->validate($oVatIn));
    }

    public function testValidate_2Checkers_OneFail()
    {
        $oVatIn = new oxCompanyVatIn('LT1123');
        $oCountry = oxNew('oxCountry');
        $oValidator = new oxCompanyVatInValidator($oCountry);

        $oChecker = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker->expects($this->any())->method('validate')->will($this->returnValue(true));

        $oValidator->addChecker($oChecker);

        $oChecker2 = $this->getMock('oxCompanyVatInCountryChecker');
        $oChecker2->expects($this->any())->method('validate')->will($this->returnValue(false));
        $oChecker2->expects($this->any())->method('getError')->will($this->returnValue('Error'));

        $oValidator->addChecker($oChecker2);

        $this->assertFalse($oValidator->validate($oVatIn));
        $this->assertSame('Error', $oValidator->getError());
    }
}
