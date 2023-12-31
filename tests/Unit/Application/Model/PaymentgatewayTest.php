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

use \OxidEsales\Eshop\Application\Model\Order;
use \oxpaymentgateway;
use \oxField;
use \oxDb;

class mod_oxpaymentgateway extends oxpaymentgateway
{
    public function getPaymentInfo()
    {
        return $this->_oPaymentInfo;
    }

    public function setActive()
    {
        $this->_blActive = true;
    }

    public function setError($iNr, $sMsg)
    {
        $this->_iLastErrorNo = $iNr;
        $this->_sLastError = $sMsg;
    }
}

class PaymentGatewayTest extends \OxidTestCase
{
    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $sDelete = "Delete from oxuserpayments where oxuserid = 'test'";
        oxDb::getDb()->Execute($sDelete);

        parent::tearDown();
    }

    public function testSetPaymentParams()
    {
        $oUserpayment = oxNew("oxuserpayment");
        $oUserpayment->oxuserpayments__oxuserid = new oxField("test", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxpaymentsid = new oxField("test", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxvalue = new oxField("test", oxField::T_RAW);
        $oUserpayment->Save();
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setPaymentParams($oUserpayment);
        $oUP = $oPaymentGateway->getPaymentInfo();
        $this->assertEquals($oUP->oxuserpayments__oxvalue->value, $oUserpayment->oxuserpayments__oxvalue->value);
    }

    public function testExecuteNotActivePayment()
    {
        $oOrder = oxNew(Order::class);
        $oPaymentGateway = oxNew('oxPaymentGateway');
        $blResult = $oPaymentGateway->executePayment(2, $oOrder);

        $this->assertEquals($blResult, true);
    }

    public function testExecutePaymentWithoutPaymentInfo()
    {
        $oOrder = oxNew(Order::class);
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setActive();
        $blResult = $oPaymentGateway->executePayment(2, $oOrder);
        $this->assertEquals($blResult, false);
    }

    public function testExecutePayment()
    {
        $oOrder = oxNew(Order::class);
        $oUserpayment = oxNew("oxuserpayment");
        $oUserpayment->oxuserpayments__oxuserid = new oxField("test", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxpaymentsid = new oxField("test", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxvalue = new oxField("test", oxField::T_RAW);
        $oUserpayment->Save();
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setActive();
        $oPaymentGateway->setPaymentParams($oUserpayment);
        $blResult = $oPaymentGateway->executePayment(2, $oOrder);
        $this->assertEquals($blResult, false);
    }

    public function testExecutePaymentWithEmptyPaymentId()
    {
        $oOrder = oxNew(Order::class);
        $oUserpayment = oxNew("oxuserpayment");
        $oUserpayment->oxuserpayments__oxuserid = new oxField("test", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxpaymentsid = new oxField("oxempty", oxField::T_RAW);
        $oUserpayment->oxuserpayments__oxvalue = new oxField("test", oxField::T_RAW);
        $oUserpayment->Save();
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setActive();
        $oPaymentGateway->setPaymentParams($oUserpayment);
        $blResult = $oPaymentGateway->executePayment(2, $oOrder);
        $this->assertEquals($blResult, true);
    }

    public function testGetLastErrorNo()
    {
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setError(null, null);
        $blResult = $oPaymentGateway->getLastErrorNo();
        $this->assertEquals($blResult, null);
    }

    public function testGetLastSetErrorNo()
    {
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setError(22, "Test Error");
        $blResult = $oPaymentGateway->getLastErrorNo();
        $this->assertEquals($blResult, 22);
    }

    public function testGetLastError()
    {
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setError(null, null);
        $blResult = $oPaymentGateway->getLastError();
        $this->assertEquals($blResult, null);
    }

    public function testGetLastSetError()
    {
        $oPaymentGateway = new mod_oxpaymentgateway();
        $oPaymentGateway->setError(22, "Test Error");
        $blResult = $oPaymentGateway->getLastError();
        $this->assertEquals($blResult, "Test Error");
    }
}
