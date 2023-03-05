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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use oxRegistry;
use oxDb;
use stdClass;
use oxField;

/**
 * Admin article RDFa payment manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> RDFa.
 */
class PaymentRdfa extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = "payment_rdfa.tpl";

    /**
     * Predefined RDFa payment methods
     * 0 value have general payments, 1 have credit card payments
     *
     * @var array
     */
    protected $_aRDFaPayments = [
        "ByBankTransferInAdvance" => 0,
        "ByInvoice"               => 0,
        "Cash"                    => 0,
        "CheckInAdvance"          => 0,
        "COD"                     => 0,
        "DirectDebit"             => 0,
        "GoogleCheckout"          => 0,
        "PayPal"                  => 0,
        "PaySwarm"                => 0,
        "AmericanExpress"         => 1,
        "DinersClub"              => 1,
        "Discover"                => 1,
        "JCB"                     => 1,
        "MasterCard"              => 1,
        "VISA"                    => 1
    ];

    public function render()
    {
        $paymentId = $this->getEditObjectId();
        if (isset($paymentId)) {
            $this->_aViewData['edit'] = $this->getPayment($paymentId);
        }
        return parent::render();
    }

    /**
     * Saves changed mapping configurations
     */
    public function save()
    {
        $aParams = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("editval");
        $aRDFaPayments = (array) \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("ardfapayments");

        // Delete old mappings
        $oDb = DatabaseProvider::getDb();
        $oDb->execute("DELETE FROM oxobject2payment WHERE oxpaymentid = :oxpaymentid AND OXTYPE = 'rdfapayment'", [
            ':oxpaymentid' => \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("oxid")
        ]);

        // Save new mappings
        foreach ($aRDFaPayments as $sPayment) {
            $oMapping = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
            $oMapping->init("oxobject2payment");
            $oMapping->assign($aParams);
            $oMapping->oxobject2payment__oxobjectid = new \OxidEsales\Eshop\Core\Field($sPayment);
            $oMapping->save();
        }
    }

    /**
     * Returns an array including all available RDFa payments.
     *
     * @return array
     */
    public function getAllRDFaPayments()
    {
        $aRDFaPayments = [];
        $aAssignedRDFaPayments = $this->getAssignedRDFaPayments();
        foreach ($this->_aRDFaPayments as $sName => $iType) {
            $oPayment = new stdClass();
            $oPayment->name = $sName;
            $oPayment->type = $iType;
            $oPayment->checked = in_array($sName, $aAssignedRDFaPayments);
            $aRDFaPayments[] = $oPayment;
        }

        return $aRDFaPayments;
    }

    /**
     * Returns array of RDFa payments which are assigned to current payment
     *
     * @return array
     */
    public function getAssignedRDFaPayments()
    {
        $oDb = DatabaseProvider::getDb();
        $aRDFaPayments = [];
        $sSelect = 'select oxobjectid from oxobject2payment where oxpaymentid = :oxpaymentid and oxtype = "rdfapayment" ';
        $rs = $oDb->select($sSelect, [
            ':oxpaymentid' => \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("oxid")
        ]);
        if ($rs && $rs->count()) {
            while (!$rs->EOF) {
                $aRDFaPayments[] = $rs->fields[0];
                $rs->fetchRow();
            }
        }

        return $aRDFaPayments;
    }

    /**
     * @param string $paymentId
     * @return Payment
     */
    private function getPayment(string $paymentId): Payment
    {
        $payment = oxNew(Payment::class);
        $payment->loadInLang($this->_iEditLang, $paymentId);
        return $payment;
    }
}
