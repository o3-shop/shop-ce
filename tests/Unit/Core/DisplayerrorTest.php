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

class DisplayerrorTest extends \OxidTestCase
{
    /** @var oxDisplayError */
    private $_oDisplayError;

    /**
     * Initialize default display error object.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_oDisplayError = oxNew('oxDisplayError');
    }

    /**
     * Tests the set and getter for message
     */
    public function testGetOxMessage()
    {
        $this->_oDisplayError->setMessage("Test ");
        $this->assertEquals("Test ", $this->_oDisplayError->getOxMessage());
    }

    /**
     * Test if the error class is always null
     */
    public function testGetErrorClassType()
    {
        $this->assertNull($this->_oDisplayError->getErrorClassType());
    }

    /**
     *tests if the value is always empty
     */
    public function testGetValue()
    {
        $this->assertEquals($this->_oDisplayError->getValue("whatever"), "");
    }

    public function testFormatingMessage()
    {
        $this->_oDisplayError->setMessage("Test %s string with %d values");
        $this->_oDisplayError->setFormatParameters(array('formatting', 2));
        $this->assertEquals("Test formatting string with 2 values", $this->_oDisplayError->getOxMessage());
    }
}
