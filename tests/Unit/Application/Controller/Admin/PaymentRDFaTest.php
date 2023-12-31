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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use oxDb;
use oxField;
use OxidEsales\Eshop\Application\Controller\Admin\PaymentRdfa;
use OxidEsales\Eshop\Application\Model\Payment;
use stdClass;

final class PaymentRDFaTest extends \OxidTestCase
{
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxobject2payment');

        parent::tearDown();
    }

    /**
     * Payment_RDFa::save() delete old records test case
     *
     * @return null
     */
    public function testSave_deleteOldRecords()
    {
        $sTestID = '_test_recid';
        $this->setRequestParameter('oxid', $sTestID);

        $oMapping = oxNew('oxBase');
        $oMapping->init('oxobject2payment');
        $oMapping->oxobject2payment__oxpaymentid = new oxField($sTestID);
        $oMapping->oxobject2payment__oxobjectid = new oxField('test_del_objID');
        $oMapping->oxobject2payment__oxtype = new oxField('rdfapayment');
        $oMapping->save();

        $oDB = oxDb::getDb();

        $iExists = $oDB->GetOne(
            'SELECT 1 FROM oxobject2payment WHERE oxpaymentid = ? AND oxtype = ?',
            array($sTestID, 'rdfapayment')
        );
        $this->assertFalse(empty($iExists));

        $oView = oxNew('Payment_RDFa');
        $oView->save();

        $iExists = $oDB->GetOne(
            'SELECT 1 FROM oxobject2payment WHERE oxpaymentid = ? AND oxtype = ?',
            array($sTestID, 'rdfapayment')
        );
        $this->assertTrue(empty($iExists));
    }

    /**
     * Payment_RDFa::save() create records test case
     *
     * @return null
     */
    public function testSave_createRecords()
    {
        $sTestID = '_test_recid';
        $aObjIDs = array('_test_obj1', '_test_obj2');
        $this->setRequestParameter('oxid', $sTestID);
        $this->setRequestParameter('ardfapayments', $aObjIDs);
        $this->setRequestParameter(
            'editval',
            array(
                 'oxobject2payment__oxpaymentid' => $sTestID,
                 'oxobject2payment__oxtype'      => 'rdfapayment',
            )
        );

        $oDB = oxDb::getDb();

        $oView = oxNew('Payment_RDFa');
        $oView->save();

        $aCurrObjIDs = $oDB->GetCol(
            'SELECT oxobjectid FROM oxobject2payment WHERE oxpaymentid = ? AND oxtype = ?',
            array($sTestID, 'rdfapayment')
        );
        sort($aObjIDs);
        sort($aCurrObjIDs);
        $this->assertSame($aObjIDs, $aCurrObjIDs);
    }

    /**
     * Payment_RDFa::getAllRDFaPayments() test case
     *
     * @return null
     */
    public function testGetAllRDFaPayments()
    {
        $aAssignedRDFaPayments = array('GoogleCheckout');
        $aExpResp = array();

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\PaymentRdfa::class, array('getAssignedRDFaPayments'));
        $oView->expects($this->once())->method('getAssignedRDFaPayments')->will($this->returnValue($aAssignedRDFaPayments));
        $aCurrResp = $oView->getAllRDFaPayments();

        $this->assertTrue(is_array($aCurrResp), 'Array should be returned');
        $this->assertTrue(count($aCurrResp) > 0, 'Empty array returned');
        $this->assertTrue(current($aCurrResp) instanceof stdClass, 'Array elements should be of type stdClass');

        $blFound = false;
        foreach ($aCurrResp as $oItem) {
            foreach ($aAssignedRDFaPayments as $sAssignedName) {
                if (strcasecmp($oItem->name, $sAssignedName) === 0) {
                    if ($oItem->checked !== true) {
                        $this->fail('Item "' . $sAssignedName . '" should be set as active');
                    }
                } else {
                    if ($oItem->checked === true) {
                        $this->fail('Item "' . $sAssignedName . '" should not be set as active');
                    }
                }
            }
        }
    }

    /**
     * Payment_RDFa::getAssignedRDFaPayments() test case
     *
     * @return null
     */
    public function testGetAssignedRDFaPayments()
    {
        $sTestID = '_test_recid';
        $aObjIDs = array('_test_obj1', '_test_obj2');
        $this->setRequestParameter('oxid', $sTestID);
        $oView = oxNew('Payment_RDFa');

        $oDB = oxDb::getDb();
        $oDB->Execute('DELETE FROM oxobject2payment WHERE oxpaymentid = ? AND oxtype = ?', array($sTestID, 'rdfapayment'));
        $this->assertSame(array(), $oView->getAssignedRDFaPayments(), 'Should be empty array');

        foreach ($aObjIDs as $sObjID) {
            $oMapping = oxNew('oxBase');
            $oMapping->init('oxobject2payment');
            $oMapping->oxobject2payment__oxpaymentid = new oxField($sTestID);
            $oMapping->oxobject2payment__oxobjectid = new oxField($sObjID);
            $oMapping->oxobject2payment__oxtype = new oxField('rdfapayment');
            $oMapping->save();
        }

        $aResp = $oView->getAssignedRDFaPayments();
        sort($aObjIDs);
        sort($aResp);
        $this->assertSame($aObjIDs, $aResp);
    }

    public function testRenderWillReturnNonEmptyString(): void
    {
        $result = oxNew(PaymentRdfa::class)->render();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRenderWithEmptyObjectIdWillNotLoadPayment(): void
    {
        $this->setRequestParameter('oxid', null);
        /** @var PaymentRdfa $paymentRdfa */
        $paymentRdfa = oxNew(PaymentRdfa::class);

        $paymentRdfa->render();

        $this->assertEmpty($paymentRdfa->getViewData()['edit']);
    }

    public function testRenderWithValidIdWillLoadPayment(): void
    {
        $this->setRequestParameter('oxid', 123);
        /** @var PaymentRdfa $paymentRdfa */
        $paymentRdfa = oxNew(PaymentRdfa::class);

        $paymentRdfa->render();

        $this->assertInstanceOf(Payment::class, $paymentRdfa->getViewData()['edit']);
    }
}
