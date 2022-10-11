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

class SystemComponentExceptionTest extends \OxidTestCase
{
    public function testSetGetComponent()
    {
        $sComponent = "a Component";
        $oTestObject = oxNew('oxSystemComponentException');
        $this->assertStringContainsString('SystemComponentException', get_class($oTestObject));
        $oTestObject->setComponent($sComponent);
        $this->assertEquals($sComponent, $oTestObject->getComponent());
    }

    // We check on class name (exception class) and message only - rest is not checked yet
    public function testGetString()
    {
        $sMsg = 'Erik was here..';
        $sComponent = "a Component";
        $oTestObject = oxNew('oxSystemComponentException', $sMsg);
        $oTestObject->setComponent($sComponent);
        $sStringOut = $oTestObject->getString(); // (string)$oTestObject; is not PHP 5.2 compatible (__toString() for string convertion is PHP >= 5.2
        $this->assertStringContainsString($sMsg, $sStringOut);
        $this->assertStringContainsString('SystemComponentException', $sStringOut);
        $this->assertStringContainsString($sComponent, $sStringOut);
    }

    public function testGetValues()
    {
        $oTestObject = oxNew('oxSystemComponentException');
        $sComponent = "a Component";
        $oTestObject->setComponent($sComponent);
        $aRes = $oTestObject->getValues();
        $this->assertArrayHasKey('component', $aRes);
        $this->assertTrue($sComponent === $aRes['component']);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxSystemComponentException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
