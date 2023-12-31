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

class UserCounterTest extends \OxidTestCase
{
    public function testCountingAdmins()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_testMallAdmin1', true, 'malladmin');
        $this->_createUserWithRights('_testMallAdmin2', true, 'malladmin');
        $this->_createUserWithRights('_tesAdmin1', true, '1');
        $this->_createUserWithRights('_tesAdmin2', true, '2');
        $this->_createUserWithRights('_tesUser', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(4, $oCounter->getAdminCount());
    }

    public function testCountingAdminsWhenInActiveAdminsExist()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_testMallAdmin1', true, 'malladmin');
        $this->_createUserWithRights('_testMallAdmin2', false, 'malladmin');
        $this->_createUserWithRights('_tesAdmin1', true, '1');
        $this->_createUserWithRights('_tesAdmin2', false, '2');
        $this->_createUserWithRights('_tesUser', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(4, $oCounter->getAdminCount());
    }

    public function testCountingAdminsWhenNoAdminsExist()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_tesUser1', true, 'user');
        $this->_createUserWithRights('_tesUser2', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(0, $oCounter->getAdminCount());
    }

    public function testCountingActiveAdmins()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_testMallAdmin1', true, 'malladmin');
        $this->_createUserWithRights('_testMallAdmin2', true, 'malladmin');
        $this->_createUserWithRights('_tesAdmin1', true, '1');
        $this->_createUserWithRights('_tesAdmin2', true, '2');
        $this->_createUserWithRights('_tesUser', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(4, $oCounter->getActiveAdminCount());
    }

    public function testCountingActiveAdminsWhenInActiveAdminsExist()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_testMallAdmin1', true, 'malladmin');
        $this->_createUserWithRights('_testMallAdmin2', false, 'malladmin');
        $this->_createUserWithRights('_tesAdmin1', true, '1');
        $this->_createUserWithRights('_tesAdmin2', false, '2');
        $this->_createUserWithRights('_tesUser', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(2, $oCounter->getActiveAdminCount());
    }

    public function testCountingActiveAdminsWhenNoAdminsExist()
    {
        $this->getDb()->execute("delete from `oxuser`");

        $this->_createUserWithRights('_tesUser1', true, 'user');
        $this->_createUserWithRights('_tesUser2', true, 'user');

        $oCounter = oxNew('oxUserCounter');
        $this->assertEquals(0, $oCounter->getActiveAdminCount());
    }

    /**
     * @param string $sId
     * @param string $sActive
     * @param string $sRights
     */
    protected function _createUserWithRights($sId, $sActive, $sRights)
    {
        $sQ = "insert into `oxuser` (oxid, oxusername, oxactive, oxrights) values ('$sId', '$sId', '$sActive', '$sRights')";
        $this->getDb()->execute($sQ);
    }
}
