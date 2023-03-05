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

class ObjectexceptionTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    public function testSetGetObject()
    {
        $testObject = oxNew(\OxidEsales\Eshop\Core\Exception\ObjectException::class);
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\ObjectException::class, get_class($testObject));
        $object = new \stdClass();
        $object->sAtrib = "Atribute";
        $testObject->setObject($object);
        $this->assertEquals("Atribute", $testObject->getObject()->sAtrib);
    }

    // We check on class name (exception class) and message only - rest is not checked yet
    public function testGetString()
    {
        $message = 'Erik was here..';
        $testObject = oxNew('oxObjectException', $message);
        $this->assertEquals('OxidEsales\Eshop\Core\Exception\ObjectException', get_class($testObject));
        $object = new \stdClass();
        $testObject->setObject($object);
        $sStringOut = $testObject->getString(); // (string)$oTestObject; is not PHP 5.2 compatible (__toString() for string convertion is PHP >= 5.2
        $this->assertStringContainsString($message, $sStringOut);
        $this->assertStringContainsString('ObjectException', $sStringOut);
        $this->assertStringContainsString(get_class($object), $sStringOut);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxObjectException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
