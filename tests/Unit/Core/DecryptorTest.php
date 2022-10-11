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
 * Class Unit_Core_oxEncryptorTest
 */
class DecryptorTest extends \OxidTestCase
{
    public function providerDecodingOfStringWithCorrectKey()
    {
        return array(
            // string encrypted with empty key
            array('ox_MCcrOiwrDCstNjE4Njs!', '', 'testString'),
            // string encrypted with numeric key
            array('ox_MEkrVCxFDEUtWDFWNlU!', 1, 'testString'),
            // string encrypted with not empty key
            array('ox_MAwRFgc/Ng0tHQsUHS8!', 'testKey', 'testString'),
            // empty string encrypted with not empty key
            array('ox_MAwMFw!!', 'testKey', ''),
        );
    }

    /**
     * @dataProvider providerDecodingOfStringWithCorrectKey
     */
    public function testDecodingOfStringWithCorrectKey($sEncodedString, $sKey, $sString)
    {
        $oDecryptor = oxNew('oxDecryptor');

        $this->assertSame($sString, $oDecryptor->decrypt($sEncodedString, $sKey));
    }

    public function testDecodingOfStringWithIncorrectKey()
    {
        $oDecryptor = oxNew('oxDecryptor');

        $this->assertNotSame('testString', $oDecryptor->decrypt('ox_Gx0HETgRKgAXGhosDB0!', 'incorrectKey'));
    }
}
