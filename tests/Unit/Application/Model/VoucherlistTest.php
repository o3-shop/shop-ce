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
use \oxRegistry;

/**
 * Testing oxvoucherserie class
 */
class VoucherlistTest extends \OxidTestCase
{
    const MAX_LOOP_AMOUNT = 4;

    protected $_sOxid = null;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();

        // simulating some voucherserie
        $this->_sOxid = uniqid('test');

        // creating 100 test vouchers
        for ($i = 0; $i < self::MAX_LOOP_AMOUNT; $i++) {
            $oNewVoucher = oxNew('oxvoucher');
            $oNewVoucher->oxvouchers__oxvoucherserieid = new oxField($this->_sOxid, oxField::T_RAW);
            $oNewVoucher->oxvouchers__oxvouchernr = new oxField(uniqid('voucherNr' . $i), oxField::T_RAW);
            $oNewVoucher->oxvouchers__oxvoucherserieid = new oxField($this->_sOxid, oxField::T_RAW);

            $oNewVoucher->Save();
        }
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $myDB = oxDb::getDB();

        // removing vouchers
        $sQ = 'delete from oxvouchers where oxvouchers.oxvoucherserieid = "' . $this->_sOxid . '"';
        $myDB->Execute($sQ);

        parent::tearDown();
    }

    public function testLoadVoucherList()
    {
        $myUtils = oxRegistry::getUtils();

        $oVouchers = oxNew('oxvoucherlist');
        $oVouchers->selectString('select * from oxvouchers where oxvouchers.oxvoucherserieid = "' . $this->_sOxid . '"');

        $this->assertEquals(self::MAX_LOOP_AMOUNT, $oVouchers->count());
    }
}
