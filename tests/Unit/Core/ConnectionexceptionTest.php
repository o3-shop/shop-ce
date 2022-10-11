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
 * Class ConnectionexceptionTest
 *
 * @group database-adapter
 */
class ConnectionexceptionTest extends \OxidTestCase
{
    public function testSetGetAddress()
    {
        $sAddress = 'sServerAddress';
        $oTestObject = oxNew('oxConnectionException');
        $oTestObject->setAdress($sAddress);
        $this->assertEquals($sAddress, $oTestObject->getAdress());
    }

    public function testSetGetConnectionError()
    {
        $sConnectionError = 'sSomeConnectionError';
        $oTestObject = oxNew('oxConnectionException');
        $oTestObject->setConnectionError($sConnectionError);
        $this->assertEquals($sConnectionError, $oTestObject->getConnectionError());
    }

    // We check on class name and message only - rest is not checked yet
    public function testSetString()
    {
        $sMsg = 'Erik was here..';
        $oTestObject = oxNew('oxConnectionException', $sMsg);
        $sAddress = 'sServerAddress';
        $oTestObject->setAdress($sAddress);
        $sConnectionError = 'sSomeConnectionError';
        $oTestObject->setConnectionError($sConnectionError);
        $sStringOut = $oTestObject->getString();
        $this->assertStringContainsString($sMsg, $sStringOut); // Message
        $this->assertStringContainsString('ConnectionException', $sStringOut); // Exception class name
        $this->assertStringContainsString($sAddress, $sStringOut); // Server Address
        $this->assertStringContainsString($sConnectionError, $sStringOut); // Connection error
    }

    public function testGetValues()
    {
        $oTestObject = oxNew('oxConnectionException');
        $sAddress = 'sServerAddress';
        $oTestObject->setAdress($sAddress);
        $sConnectionError = 'sSomeConnectionError';
        $oTestObject->setConnectionError($sConnectionError);
        $aRes = $oTestObject->getValues();
        $this->assertArrayHasKey('adress', $aRes);
        $this->assertTrue($sAddress === $aRes['adress']);
        $this->assertArrayHasKey('connectionError', $aRes);
        $this->assertTrue($sConnectionError === $aRes['connectionError']);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxConnectionException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
