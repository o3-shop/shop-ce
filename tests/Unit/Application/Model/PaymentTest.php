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

use \oxPayment;
use \oxField;
use \oxDb;
use \oxRegistry;

class testPayment extends oxPayment
{
    public function unsetGroup($id)
    {
        unset($this->_groups[$id]);
    }
}

class PaymentTest extends \OxidTestCase
{
    /**
     * Tear down the fixture.
     *
     * @return null
     */
    public function tearDown(): void
    {
        $this->cleanUpTable('oxobject2group', 'oxgroupsid');
        parent::tearDown();
    }

    /**
     * Get default dynamic data
     */
    protected function getDynValues()
    {
        $aDynvalue['lsbankname'] = 'Bank name';
        $aDynvalue['lsblz'] = '12345678';
        $aDynvalue['lsktonr'] = '123456';
        $aDynvalue['lsktoinhaber'] = 'Hans Mustermann';

        return $aDynvalue;
    }

    public function testGetDynValuesIfAlwaysArrayIsReturned()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->load("oxiddebitnote");

        $this->assertTrue(is_array($oPayment->getDynValues()));

        $oPayment = oxNew('oxPayment');
        $oPayment->oxpayments__oxvaldesc = new oxField('some unknown format value');

        $this->assertTrue(is_array($oPayment->getDynValues()));

        $oPayment = oxNew('oxPayment');
        $this->assertTrue(is_array($oPayment->getDynValues()));
    }

    /**
     * Test assign data to object
     */
    public function testGetGroups()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->load('oxiddebitnote');

        $aArray = array('oxidsmallcust',
                        'oxidnewcustomer',
                        'o3newsletter',
                        'oxidadmin');

        $this->assertEqualsCanonicalizing(
            $aArray,
            $oPayment->getGroups()->arrayKeys(),
            "Groups are not as expected."
        );
    }

    /**
     * Testing dyn values getter/setter
     */
    public function testGetSetDynValues()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->setDynValues(array('field0' => 'val0'));
        $oPayment->setDynValue('field1', 'val1');

        $this->assertEquals(array('field0' => 'val0', 'field1' => 'val1'), $oPayment->getDynValues());
    }

    /**
     * Testing dyn values getter/setter
     */
    public function testGetDynValues()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->load("oxiddebitnote");

        $this->assertEquals(oxRegistry::getUtils()->assignValuesFromText($oPayment->oxpayments__oxvaldesc->value), $oPayment->getDynValues());
    }

    /**
     * Test getting payment value
     */
    public function testGetPaymentValue()
    {
        $oPayment = oxNew('oxPayment');

        $oPayment->load('oxidpayadvance');
        $dBasePrice = 100.0;
        $this->assertEquals(0, $oPayment->getPaymentValue($dBasePrice));

        $oPayment->load('oxidcashondel');
        $this->assertEquals(7.5, $oPayment->getPaymentValue($dBasePrice));

        $oPayment->oxpayments__oxaddsum = new oxField(-105, oxField::T_RAW);
        $this->assertEquals(100, $oPayment->getPaymentValue($dBasePrice));
    }

    /**
     * Test getting payment value in special currency
     */
    public function testGetPaymentValueSpecCurrency()
    {
        $this->getConfig()->setActShopCurrency(2);
        $oPayment = oxNew('oxPayment');

        $oPayment->load('oxidpayadvance');
        $dBasePrice = 100.0;
        $this->assertEquals(0, $oPayment->getPaymentValue($dBasePrice));

        $oPayment->load('oxidcashondel');
        $this->assertEquals(10.7445, $oPayment->getPaymentValue($dBasePrice));

        $oPayment->oxpayments__oxaddsum = new oxField(-105, oxField::T_RAW);
        $this->assertEquals(100, $oPayment->getPaymentValue($dBasePrice));
    }

    /**
     * Test get payment countries
     */
    public function testGetCountries()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->load('oxiddebitnote');
        $this->assertEquals(3, count($oPayment->getCountries()), "Failed getting countries list");
    }

    /**
     * Test payment delete from db
     */
    public function testDelete()
    {
        $oPayment = oxNew('oxPayment');

        $oDB = oxDb::getDb();
        $sQ = "insert into oxpayments (oxid, oxactive, oxaddsum, oxaddsumtype) values ('oxpaymenttest', 1, '5', 'abs')";
        $oDB->execute($sQ);

        $sQ = "insert into oxobject2payment (oxid, oxpaymentid, oxobjectid, oxtype) values ('oxob2p_testid', 'oxpaymenttest', 'testid', 'oxdelset')";
        $oDB->execute($sQ);

        $oPayment->load('oxpaymenttest');
        $oPayment->delete('oxpaymenttest');

        $sQ = "select count(oxid) from oxpayments where oxid = 'oxpaymenttest' ";
        $this->assertEquals(0, $oDB->getOne($sQ), "Failed deleting payment items from oxpayments table");

        $sQ = "select count(oxid) from oxobject2payment where oxid = 'oxob2p_testid' ";
        $this->assertEquals(0, $oDB->getOne($sQ), "Failed deleting items from oxobject2payment table");
    }

    public function testDeleteNotSetObject()
    {
        $oPayment = oxNew('oxPayment');

        $oDB = oxDb::getDb();
        $sQ = "insert into oxpayments (oxid, oxactive, oxaddsum, oxaddsumtype) values ('oxpaymenttest', 1, '5', 'abs')";
        $oDB->execute($sQ);

        $sQ = "insert into oxobject2payment (oxid, oxpaymentid, oxobjectid, oxtype) values ('oxob2p_testid', 'oxpaymenttest', 'testid', 'oxdelset')";
        $oDB->execute($sQ);

        $oPayment->delete();

        $sQ = "select count(oxid) from oxpayments where oxid = 'oxpaymenttest' ";
        $this->assertEquals(1, $oDB->getOne($sQ));

        $sQ = "select count(oxid) from oxobject2payment where oxid = 'oxob2p_testid' ";
        $this->assertEquals(1, $oDB->getOne($sQ));
    }

    /**
     * Test payment validation for empty payment
     * other payments for user country exist
     */
    public function testIsValidPaymentEmptyPaymentButThereAreCountries()
    {
        $oPayment = oxNew('oxpayment');
        $oPayment->oxpayments__oxid = new oxField('oxempty', oxField::T_RAW);
        $oPayment->oxpayments__oxactive = new oxField(1);

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $blRes = $oPayment->isValidPayment(array(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard');
        $this->assertFalse($blRes);
        $this->assertEquals(-3, $oPayment->getPaymentErrorNumber());
    }

    /**
     * Test payment validation for empty payment
     * no other payments for user country exist
     */
    public function testIsValidPaymentEmptyPaymentAndThereAreNoCountries()
    {
        $oPayment = oxNew('oxpayment');
        $oPayment->oxpayments__oxid = new oxField('oxempty', oxField::T_RAW);
        $oPayment->oxpayments__oxactive = new oxField(1);

        $oUser = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, array('getActiveCountry'));
        $oUser->expects($this->once())->method('getActiveCountry')->will($this->returnValue('otherCountry'));
        $oUser->Load('oxdefaultadmin');

        $blRes = $oPayment->isValidPayment(array(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard');
        $this->assertTrue($blRes);
    }

    public function testIsValidPaymentBlOtherCountryOrderIfFalse()
    {
        $this->getConfig()->setConfigParam('blOtherCountryOrder', false);

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $oPayment = oxNew('oxpayment');
        $oPayment->oxpayments__oxid = new oxField('oxempty');
        $oPayment->oxpayments__oxactive = new oxField(1);
        $this->assertFalse($oPayment->isValidPayment(array(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard'));
        $this->assertEquals(-2, $oPayment->getPaymentErrorNumber());
    }

    public function testIsValidPaymentOxemptyIsInActive()
    {
        $oPayment = oxNew('oxpayment');
        $oPayment->oxpayments__oxid = new oxField('oxempty');
        $oPayment->oxpayments__oxactive = new oxField(0);

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $this->assertFalse($oPayment->isValidPayment(array(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard'));
        $this->assertEquals(-2, $oPayment->getPaymentErrorNumber());
    }

    /**
     * Test payment debit note validation
     */
    public function testIsValidPaymentDebitNoteChecking()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $aDynvalue = $this->getDynValues();
        $aDynvalue['kknumber'] = ''; //wrong number
        $blRes = $oPayment->isValidPayment($aDynvalue, $this->getConfig()->getBaseShopId(), null, 0.0, 'oxidstandard');
        $this->assertFalse($blRes);
    }

    /**
     * Test payment validation
     */
    public function testIsValidPaymentWithNotValidShippingSetId()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'nosuchvalue');
        $this->assertFalse($blRes);
    }

    /**
     * Test payment validation without passing shipping set id
     */
    public function testIsValidPaymentWithoutShippingSetId()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 5.0, null);
        $this->assertFalse($blRes);
    }


    /**
     * Test payment validation with boni
     */
    public function testIsValidPayment_FromBoni()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        $oUser->oxuser__oxboni = new oxField($oPayment->oxpayments__oxfromboni->value - 1, oxField::T_RAW);
        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard');
        $this->assertFalse($blRes);
    }

    /**
     * Test payment validation with amount
     */
    public function testIsValidPayment_FromAmount()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        //oxpayments__oxfromamount = 0, so passing lower value price
        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, -1, 'oxidstandard');
        $this->assertFalse($blRes);
    }

    /**
     * Test payment validation with amount
     */
    public function testIsValidPayment_ToAmount()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');

        //oxpayments__oxtoamount is 1000000, so passing price that is greater
        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 1000001, 'oxidstandard');
        $this->assertFalse($blRes);
    }

    /**
     * Test payment validation with groups
     */
    public function testIsValidPaymentInGroup()
    {
        $oPayment = oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Application\Model\testPayment::class);
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');
        $oUser->addToGroup('_testGroupId');

        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 0.0, 'oxidstandard');
        $this->assertTrue($blRes);
    }

    /**
     * Test payment validation - validation good
     */
    public function testIsValidPayment()
    {
        $oPayment = oxNew('oxPayment');
        $oPayment->Load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->Load('oxdefaultadmin');
        $oUser->oxuser__oxboni = new oxField($oPayment->oxpayments__oxfromboni->value + 1, oxField::T_RAW);

        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 5.0, 'oxidstandard');
        $this->assertTrue($blRes);
    }

    /**
     * Test payment validation sets correct error codes
     */
    public function testIsValidPayment_settingErrorNumber()
    {
        $oPayment = $this->getProxyClass("oxPayment");
        $oPayment->load('oxiddebitnote');

        $oUser = oxNew('oxuser');
        $oUser->load('oxdefaultadmin');

        //oxpayments__oxtoamount is 1000000, so passing price that is greater
        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 1000001, 'oxidstandard');
        $this->assertEquals(-3, $oPayment->getNonPublicVar('_iPaymentError'));

        //no shipping set id
        $blRes = $oPayment->isValidPayment($this->getDynValues(), $this->getConfig()->getBaseShopId(), $oUser, 1000001, null);
        $this->assertEquals(-2, $oPayment->getNonPublicVar('_iPaymentError'));

        //not valid input
        $blRes = $oPayment->isValidPayment(null, $this->getConfig()->getBaseShopId(), $oUser, 1000001, null);
        $this->assertEquals(1, $oPayment->getNonPublicVar('_iPaymentError'));
    }

    /**
     * Test payment error number  getter
     */
    public function testGetPaymentErrorNumber()
    {
        $oPayment = $this->getProxyClass("oxPayment");
        $oPayment->setNonPublicVar('_iPaymentError', 2);
        $this->assertEquals(2, $oPayment->getPaymentErrorNumber());
    }

    /**
     * Test payment config setter
     */
    public function testSetPaymentVatOnTop()
    {
        $oPayment = $this->getProxyClass("oxPayment");
        $oPayment->setPaymentVatOnTop(true);
        $this->assertTrue($oPayment->getNonPublicVar("_blPaymentVatOnTop"));
    }
}
