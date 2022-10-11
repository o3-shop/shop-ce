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

class CounterTest extends \OxidTestCase
{
    protected function tearDown(): void
    {
        \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute('delete from oxcounters');

        parent::tearDown();
    }

    /**
     * oxCounter:::getNext() test case
     *
     * @return null
     */
    public function testGetNext()
    {
        $oCounter = oxNew('oxCounter');

        $iNext1 = $oCounter->getNext("test1");
        $this->assertEquals(++$iNext1, $oCounter->getNext("test1"));
        $this->assertEquals(++$iNext1, $oCounter->getNext("test1"));
        $this->assertEquals(++$iNext1, $oCounter->getNext("test1"));

        $iNext2 = $oCounter->getNext("test2");
        $this->assertNotEquals($iNext2, $iNext1);
        $this->assertEquals(++$iNext2, $oCounter->getNext("test2"));
        $this->assertEquals(++$iNext2, $oCounter->getNext("test2"));
        $this->assertEquals(++$iNext2, $oCounter->getNext("test2"));
    }

    /**
     * oxCounter:::update() test case
     *
     * @return null
     */
    public function testUpdate()
    {
        $oCounter = oxNew('oxCounter');

        $this->assertEquals(1, $oCounter->getNext("test4"));
        $oCounter->update("test3", 3);
        $this->assertEquals(4, $oCounter->getNext("test3"));
        $oCounter->update("test3", 2);
        $this->assertEquals(5, $oCounter->getNext("test3"));
        $this->assertEquals(2, $oCounter->getNext("test4"));
    }
}
