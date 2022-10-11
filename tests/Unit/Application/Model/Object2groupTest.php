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

use \oxField;
use \oxDb;

class Object2groupTest extends \OxidTestCase
{
    private $_oGroup = null;
    private $_sObjID = null;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $oNews = oxNew('oxnews');
        $oNews->oxnews__oxshortdesc = new oxField('Test', oxField::T_RAW);
        $oNews->Save();

        $this->_oGroup = oxNew('oxobject2group');
        $this->_oGroup->oxobject2group__oxobjectid = new oxField($oNews->getId(), oxField::T_RAW);
        $this->_oGroup->oxobject2group__oxgroupsid = new oxField("oxidnewcustomer", oxField::T_RAW);
        $this->_oGroup->Save();

        $this->_sObjID = $oNews->getId();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $oDB = oxDb::getDb();
        $sDelete = "delete from oxnews where oxid='" . $this->_sObjID . "'";
        $oDB->Execute($sDelete);

        $sDelete = "delete from oxobject2group where oxobjectid='" . $this->_sObjID . "'";
        $oDB->Execute($sDelete);

        $sDelete = "delete from oxobject2group where oxobjectid='1111'";
        $oDB->Execute($sDelete);

        parent::tearDown();
    }

    public function testSave()
    {
        $sSelect = "select 1 from oxobject2group where oxobjectid='{$this->_sObjID}'";

        $this->assertEquals('1', oxDb::getDb()->getOne($sSelect));
    }

    public function testSaveNew()
    {
        $this->_oGroup = oxNew('oxobject2group');
        $this->_oGroup->oxobject2group__oxobjectid = new oxField("1111", oxField::T_RAW);
        $this->_oGroup->oxobject2group__oxgroupsid = new oxField("oxidnewcustomer", oxField::T_RAW);

        $this->assertNotNull($this->_oGroup->Save());
    }

    public function testSaveIfAlreadyExists()
    {
        $oGroup = oxNew('oxobject2group');
        $oGroup->oxobject2group__oxobjectid = new oxField($this->_sObjID, oxField::T_RAW);
        $oGroup->oxobject2group__oxgroupsid = new oxField("oxidnewcustomer", oxField::T_RAW);
        $oGroup->Save();

        $oGroup = oxNew('oxobject2group');
        $oGroup->oxobject2group__oxobjectid = new oxField($this->_sObjID, oxField::T_RAW);
        $oGroup->oxobject2group__oxgroupsid = new oxField("oxidnewcustomer", oxField::T_RAW);

        $this->assertNull($oGroup->Save());
    }
}
