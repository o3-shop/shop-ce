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
use \oxRegistry;

class RemarkTest extends \OxidTestCase
{
    private $_oRemark = null;

    protected $_iNow = null;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_iNow = time();
        oxAddClassModule('modOxUtilsDate', 'oxUtilsDate');
        \OxidEsales\Eshop\Core\Registry::getUtilsDate()->UNITSetTime($this->_iNow);

        $this->_oRemark = oxNew('oxremark');
        $this->_oRemark->oxremark__oxtext = new oxField('Test', oxField::T_RAW);
        $this->_oRemark->save();
        $this->_oRemark->load($this->_oRemark->getId());
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->_oRemark->delete();

        parent::tearDown();
    }

    public function testLoad()
    {
        $oRemark = oxNew('oxremark');
        $oRemark->load($this->_oRemark->oxremark__oxid->value);

        $sSendDate = 'd.m.Y H:i:s';
        if (oxRegistry::getLang()->getBaseLanguage() == 1) {
            $sSendDate = 'Y-m-d H:i:s';
        }

        $this->assertEquals(date($sSendDate, $this->_iNow), $oRemark->oxremark__oxcreate->value);
    }

    public function testUpdate()
    {
        $oRemark = oxNew('oxremark');
        $oRemark->load($this->_oRemark->getId());

        $oRemark->oxremark__oxtext = new oxField("Test_remark", oxField::T_RAW);
        $oRemark->oxremark__oxparentid = new oxField("oxdefaultadmin", oxField::T_RAW);
        $oRemark->save();

        $this->assertEquals($oRemark->oxremark__oxtext->value, 'Test_remark');
        $this->assertEquals($oRemark->oxremark__oxcreate->value, $this->_oRemark->oxremark__oxcreate->value);
    }

    public function testInsert()
    {
        $iNow = time();

        oxAddClassModule('modOxUtilsDate', 'oxUtilsDate');
        \OxidEsales\Eshop\Core\Registry::getUtilsDate()->UNITSetTime($iNow);

        $oRemark = oxNew('oxremark');
        $oRemark->load($this->_oRemark->oxremark__oxid->value);
        $oRemark->delete();

        $oRemark = oxNew('oxremark');
        $oRemark->setId($this->_oRemark->oxremark__oxid->value);
        $oRemark->save();

        $this->assertEquals(date('Y-m-d H:i:s', $iNow), $oRemark->oxremark__oxcreate->value);
    }
}
