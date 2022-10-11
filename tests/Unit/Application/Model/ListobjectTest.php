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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use \oxListObject;
use \oxField;

/**
 * Testing oxshoplist class
 */
class ListobjectTest extends \OxidTestCase
{


    /**
     * Tests getId method
     */
    public function testgetId()
    {
        $oListObject = new oxListObject('table');
        $oListObject->assign(array('oxid' => 10));
        $this->assertEquals(10, $oListObject->getId());
    }

    /**
     * Tests getId method
     */
    public function testgetIdWhenNotSet()
    {
        $oListObject = new oxListObject('table');
        $this->assertEquals(null, $oListObject->getId());
    }

    /**
     * Checks that assign method assigns values properly
     */
    public function testAssign()
    {
        $oListObject = new oxListObject('table');
        $oListObject->assign(array('oxid' => 10));
        $this->assertEquals(new oxField(10), $oListObject->table__oxid);
    }

    /**
     * Checks that assign method assigns values properly
     */
    public function testAssignTwo()
    {
        $oListObject = new oxListObject('table');
        $oListObject->assign(array('oxid' => 10));
        $oListObject->assign(array('oxname' => 'title'));
        $this->assertEquals(10, $oListObject->table__oxid->value);
        $this->assertEquals('title', $oListObject->table__oxname->value);
    }

    /**
     * Checking if assigning with incorrect data works.
     */
    public function testAssignIncorrect()
    {
        $oListObject = new oxListObject('table');
        $oListObject->assign('oxid');
        $this->assertEquals(0, count(get_object_vars($oListObject)));
    }
}
