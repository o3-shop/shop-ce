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

use \oxField;
use \oxRegistry;

class FieldTest extends \OxidTestCase
{
    public function test_construct()
    {
        $oField = new oxField('ssss<');
        $this->assertEquals('ssss<', $oField->rawValue);
        $this->assertEquals('ssss&lt;', $oField->value);
        $oField = new oxField('ssss<', oxField::T_RAW);
        $this->assertEquals('ssss<', $oField->rawValue);
        $this->assertEquals('ssss<', $oField->value);
    }

    public function test_isset()
    {
        $oField = new oxField('test');
        $this->assertTrue($oField->__isset('rawValue'));
        $this->assertTrue($oField->__isset('value'));
        $this->assertFalse($oField->__isset('unknown'));
        $this->assertTrue(isset($oField->rawValue));
        $this->assertTrue(isset($oField->value));
        $this->assertFalse(isset($oField->unknown));
    }

    public function test__getValue_setValue()
    {
        $oField = new oxField('ssss<');
        $this->assertEquals('ssss<', $oField->rawValue);
        $this->assertEquals('ssss&lt;', $oField->value);
        $oField->setValue('ssss<', oxField::T_RAW);
        $this->assertEquals('ssss<', $oField->rawValue);
        $this->assertEquals('ssss<', $oField->value);
        $this->assertNull($oField->aaa);
    }

    public function testConvertToFormattedDbDate()
    {
        $oField = new oxField();
        $oField->setValue('2008/05/05 10:2:2');
        $oField->convertToFormattedDbDate();

        $sRawValue = '05.05.2008 10:02:02';
        $sValue = '05.05.2008 10:02:02';
        if (oxRegistry::getLang()->getBaseLanguage() == 1) {
            $sRawValue = '2008-05-05 10:02:02';
            $sValue = '2008-05-05 10:02:02';
        }

        $this->assertEquals($sRawValue, $oField->rawValue);
        $this->assertEquals($sValue, $oField->value);
    }

    public function testConvertToPseudoHtml()
    {
        $oField = new oxField();
        $oField->setValue("ssss<\n>");
        $oField->convertToPseudoHtml();
        $this->assertEquals("ssss&lt;<br />\n&gt;", $oField->rawValue);
        $this->assertEquals("ssss&lt;<br />\n&gt;", $oField->value);
    }

    public function testSetValue_resetPrev()
    {
        $oField = new oxField();

        $oField->setValue("ssss<\n>");
        $this->assertEquals("ssss&lt;\n&gt;", $oField->value);
        $oField->setValue("ssss<");
        $this->assertEquals("ssss&lt;", $oField->value);
    }

    public function testGetRawValue()
    {
        $oField = new oxField();

        $oField->setValue("ssss<\n>");
        $this->assertEquals("ssss&lt;\n&gt;", $oField->value);
        $this->assertEquals("ssss<\n>", $oField->getRawValue());
    }

    public function testGetRawValueIfSetAsRaw()
    {
        $oField = new oxField();

        $oField->setValue("ssss<\n>", oxField::T_RAW);
        $this->assertEquals("ssss<\n>", $oField->value);
        $this->assertEquals("ssss<\n>", $oField->getRawValue());
    }

    public function testToString()
    {
        $oField = new oxField(451);
        $this->assertSame("451", (string) $oField);
    }
}
