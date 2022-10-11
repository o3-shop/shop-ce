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

class FileexceptionTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    private $testObject = null;
    private $message = 'Erik was here..';
    private $fileName = 'a file name';
    private $fileError = 'a error text';

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testObject = oxNew(\OxidEsales\Eshop\Core\Exception\FileException::class, $this->message);
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\FileException::class, get_class($this->testObject));
        $this->testObject->setFileName($this->fileName);
        $this->testObject->setFileError($this->fileError);
    }

    public function testSetGetFileName()
    {
        $this->assertEquals($this->fileName, $this->testObject->getFileName());
    }

    public function testSetGetFileError()
    {
        $this->assertEquals($this->fileError, $this->testObject->getFileError());
    }

    // We check on class name and message only - rest is not checked yet
    public function testGetString()
    {
        $stringOut = $this->testObject->getString();
        $this->assertStringContainsString($this->message, $stringOut); // Message
        $this->assertStringContainsString('FileException', $stringOut); // Exception class name
        $this->assertStringContainsString($this->fileName, $stringOut); // File name
        $this->assertStringContainsString($this->fileError, $stringOut); // File error
    }

    public function testGetValues()
    {
        $result = $this->testObject->getValues();
        $this->assertArrayHasKey('fileName', $result);
        $this->assertTrue($this->fileName === $result['fileName']);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxFileException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
