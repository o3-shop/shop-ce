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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\TestingLibrary\UnitTestCase;

class ExceptionTest extends UnitTestCase
{

    // 1. testing constructor works .. ok, its a pseudo test ;-)
    public function testConstruct()
    {
        $testObject = oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class);
        $this->assertInstanceOf(\OxidEsales\Eshop\Core\Exception\StandardException::class, $testObject);
    }

    // 2. testing constructor with message.
    public function testConstructWithMessage()
    {
        $messsage = 'Erik was here..';
        $testObject = oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class, $messsage);
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\StandardException::class, get_class($testObject));
        $this->assertTrue($testObject->getMessage() === $messsage);
    }

    // Test log file output
    public function testDebugOut()
    {
        $message = 'Erik was here..';
        $testObject = oxNew(StandardException::class, $message);

        try {
            $testObject->debugOut(); // actuall test
        } catch (Exception $e) {
            // Lets try to delete an eventual left over file
            unlink(OX_LOG_FILE);
            $this->fail();

            return;
        }
        $file = file_get_contents(OX_LOG_FILE);
        file_put_contents(OX_LOG_FILE, '');

        $this->assertStringContainsString($message, $file);
    }

    // Test set & get message
    public function testSetMessage()
    {
        $message = 'Erik was here..';
        $testObject = oxNew('oxException');
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\StandardException::class, get_class($testObject));
        $testObject->setMessage($message);
        $this->assertTrue($testObject->getMessage() === $message);
    }

    public function testSetIsRenderer()
    {
        $testObject = oxNew('oxException');
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\StandardException::class, get_class($testObject));
        $testObject->setRenderer();
        $this->assertTrue($testObject->isRenderer());
    }

    public function testSetIsNotCaught()
    {
        $testObject = oxNew('oxException');
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\StandardException::class, get_class($testObject));
        $testObject->setNotCaught();
        $this->assertTrue($testObject->isNotCaught());
    }

    public function testGetString(): void
    {
        $message = uniqid('some-message-', true);
        $testObject = oxNew('oxException', $message);
        $this->assertEquals(StandardException::class, \get_class($testObject));
        $testObject->setRenderer();
        $testObject->setNotCaught();
        $out = $testObject->getString();
        $this->assertStringContainsString($message, $out);
        $this->assertStringContainsString(__FUNCTION__, $out);
    }

    public function testGetValues()
    {
        $testObject = oxNew('oxException');
        $result = $testObject->getValues();
        $this->assertEquals(0, count($result));
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
