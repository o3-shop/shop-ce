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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin user payment settings manager.
 * Collects user payment settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Payment.
 */
class UserPayment extends AdminDetailsController
{
    /**
     * (default false).
     *
     * @var bool
     */
    protected $_blDelete = false;

    /**
     * Selected user
     *
     * @var object
     */
    protected $_oActiveUser = null;

    /**
     * Selected user payment
     *
     * @var string
     */
    protected $_sPaymentId = null;

    /**
     * List of all payments
     *
     * @var object
     */
    protected $_oPaymentTypes = null;

    /**
     * Selected user payment
     *
     * @var object
     */
    protected $_oUserPayment = null;

    /**
     * List of all user payments
     *
     * @var object
     */
    protected $_oUserPayments = null;

    /**
     * Executes parent method parent::render(), creates oxlist and oxuser objects
     * and returns the name of the template file.
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();
        $this->_aViewData["edit"] = $this->getSelUserPayment();
        $this->_aViewData["oxpaymentid"] = $this->getPaymentId();
        $this->_aViewData["paymenttypes"] = $this->getPaymentTypes();
        $this->_aViewData["edituser"] = $this->getUser();
        $this->_aViewData["userpayments"] = $this->getUserPayments();
        $sOxId = $this->getEditObjectId();

        if (!$this->_allowAdminEdit($sOxId)) {
            $this->_aViewData['readonly'] = true;
        }

        return "user_payment.tpl";
    }

    /**
     * Saves user payment settings.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        if ($this->_allowAdminEdit($soxId)) {
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
            $aDynvalues = Registry::getRequest()->getRequestEscapedParameter('dynvalue');

            if (isset($aDynvalues)) {
                // store the dynvalues
                $aParams['oxuserpayments__oxvalue'] = Registry::getUtils()->assignValuesToText($aDynvalues);
            }

            if ($aParams['oxuserpayments__oxid'] == "-1") {
                $aParams['oxuserpayments__oxid'] = null;
            }

            $oAddress = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
            $oAddress->assign($aParams);
            $oAddress->save();
        }
    }

    /**
     * Deletes selected user payment information.
     */
    public function delPayment()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        $soxId = $this->getEditObjectId();
        if ($this->_allowAdminEdit($soxId)) {
            if ($aParams['oxuserpayments__oxid'] != "-1") {
                $oAddress = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
                if ($oAddress->load($aParams['oxuserpayments__oxid'])) {
                    $this->_blDelete = (bool) $oAddress->delete();
                }
            }
        }
    }

    /**
     * Returns selected user
     *
     * @return User
     */
    public function getUser()
    {
        if ($this->_oActiveUser == null) {
            $this->_oActiveUser = false;
            $sOxId = $this->getEditObjectId();
            if (isset($sOxId) && $sOxId != "-1") {
                // load object
                $this->_oActiveUser = oxNew(User::class);
                $this->_oActiveUser->load($sOxId);
            }
        }

        return $this->_oActiveUser;
    }

    /**
     * Returns selected Payment ID
     *
     * @return object
     */
    public function getPaymentId()
    {
        if ($this->_sPaymentId == null) {
            $this->_sPaymentId = Registry::getRequest()->getRequestEscapedParameter('oxpaymentid');
            if (!$this->_sPaymentId || $this->_blDelete) {
                if ($oUser = $this->getUser()) {
                    $oUserPayments = $oUser->getUserPayments();
                    if (isset($oUserPayments[0])) {
                        $this->_sPaymentId = $oUserPayments[0]->oxuserpayments__oxid->value;
                    }
                }
            }
            if (!$this->_sPaymentId) {
                $this->_sPaymentId = "-1";
            }
        }

        return $this->_sPaymentId;
    }

    /**
     * Returns selected Payment ID
     *
     * @return object
     */
    public function getPaymentTypes()
    {
        if ($this->_oPaymentTypes == null) {
            // all paymenttypes
            $this->_oPaymentTypes = oxNew(ListModel::class);
            $this->_oPaymentTypes->init("oxpayment");
            $oListObject = $this->_oPaymentTypes->getBaseObject();
            $oListObject->setLanguage(Registry::getLang()->getObjectTplLanguage());
            $this->_oPaymentTypes->getList();
        }

        return $this->_oPaymentTypes;
    }

    /**
     * Returns selected Payment
     *
     * @return object
     * @throws DatabaseConnectionException
     */
    public function getSelUserPayment()
    {
        if ($this->_oUserPayment == null) {
            $this->_oUserPayment = false;
            $sPaymentId = $this->getPaymentId();
            if ($sPaymentId != "-1" && isset($sPaymentId)) {
                $this->_oUserPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
                $this->_oUserPayment->load($sPaymentId);
                $sTemplate = $this->_oUserPayment->oxuserpayments__oxvalue->value;

                // generate selected payment-type
                $oPaymentTypes = $this->getPaymentTypes();
                foreach ($oPaymentTypes as $oPayment) {
                    if ($oPayment->oxpayments__oxid->value == $this->_oUserPayment->oxuserpayments__oxpaymentsid->value) {
                        $oPayment->selected = 1;
                        // if there are no values assigned we set default from payment-type
                        if (!$sTemplate) {
                            $sTemplate = $oPayment->oxpayments__oxvaldesc->value;
                        }
                        break;
                    }
                }
                $this->_oUserPayment->setDynValues(Registry::getUtils()->assignValuesFromText($sTemplate));
            }
        }

        return $this->_oUserPayment;
    }

    /**
     * Returns selected Payment ID
     *
     * @return object
     */
    public function getUserPayments()
    {
        if ($this->_oUserPayments == null) {
            $this->_oUserPayments = false;
            if ($oUser = $this->getUser()) {
                $sTplLang = Registry::getLang()->getObjectTplLanguage();
                $sPaymentId = $this->getPaymentId();
                $this->_oUserPayments = $oUser->getUserPayments();
                // generate selected
                foreach ($this->_oUserPayments as $oUserPayment) {
                    $oPayment = oxNew(Payment::class);
                    $oPayment->setLanguage($sTplLang);
                    $oPayment->load($oUserPayment->oxuserpayments__oxpaymentsid->value);
                    $oUserPayment->oxpayments__oxdesc = clone $oPayment->oxpayments__oxdesc;
                    if ($oUserPayment->oxuserpayments__oxid->value == $sPaymentId) {
                        $oUserPayment->selected = 1;
                        break;
                    }
                }
            }
        }

        return $this->_oUserPayments;
    }
}
