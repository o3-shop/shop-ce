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

class LanguageexceptionTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    private $testObject = null;
    private $message = 'Erik was here..';
    private $languageConstant = 'a language';

    /**
     * Initialize the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testObject = oxNew(\OxidEsales\Eshop\Core\Exception\LanguageException::class, $this->message);
        $this->assertEquals(\OxidEsales\Eshop\Core\Exception\LanguageException::class, get_class($this->testObject));
        $this->testObject->setLangConstant($this->languageConstant);
    }

    public function testSetGetLangConstant()
    {
        $this->assertEquals($this->languageConstant, $this->testObject->getLangConstant());
    }

    // We check on class name and message only - rest is not checked yet
    public function testGetString()
    {
        $sStringOut = $this->testObject->getString();
        $this->assertStringContainsString($this->message, $sStringOut); // Message
        $this->assertStringContainsString('LanguageException', $sStringOut); // Exception class name
        $this->assertStringContainsString($this->languageConstant, $sStringOut); // Language constant
    }

    public function testGetValues()
    {
        $result = $this->testObject->getValues();
        $this->assertArrayHasKey('langConstant', $result);
        $this->assertTrue($this->languageConstant === $result['langConstant']);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxLanguageException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
