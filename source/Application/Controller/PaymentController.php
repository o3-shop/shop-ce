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

namespace OxidEsales\EshopCommunity\Application\Controller;

use oxBasket;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Core\Registry;
use oxRegistry;

/**
 * Payment manager.
 * Customer payment manager class. Performs payment validation function, etc.
 */
class PaymentController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Paymentlist
     *
     * @var object
     */
    protected $_oPaymentList = null;

    /**
     * Paymentlist count
     *
     * @var integer
     */
    protected $_iPaymentCnt = null;

    /**
     * All delivery sets
     *
     * @var array
     */
    protected $_aAllSets = null;

    /**
     * Delivery sets count
     *
     * @var integer
     */
    protected $_iAllSetsCnt = null;

    /**
     * Payment object 'oxempty'
     *
     * @var object
     */
    protected $_oEmptyPayment = null;

    /**
     * Payment error
     *
     * @var string
     */
    protected $_sPaymentError = null;

    /**
     * Payment error text
     *
     * @var string
     */
    protected $_sPaymentErrorText = null;

    /**
     * Dyn values
     *
     * @var array
     */
    protected $_aDynValue = null;

    /**
     * Checked payment id
     *
     * @var string
     */
    protected $_sCheckedId = null;

    /**
     * Selected payment id in db
     *
     * @var string
     */
    protected $_sCheckedPaymentId = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/checkout/payment.tpl';

    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_blIsOrderStep = true;

    /**
     * TS protection product array
     *
     * @var array
     */
    protected $_aTsProducts = null;

    /**
     * Executes parent::render(), checks if this connection secure
     * (if not - redirects to secure payment page), loads user object
     * (if user object loading was not successfull - redirects to start
     * page), loads user delivery/shipping information. According
     * to configuration in admin, user profile data loads delivery sets,
     * and possible payment methods. Returns name of template to render
     * payment::_sThisTemplate.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        $myConfig = $this->getConfig();

        if ($myConfig->getConfigParam('blPsBasketReservationEnabled')) {
            $this->getSession()->getBasketReservations()->renewExpiration();
        }

        parent::render();

        //if it happens that you are not in SSL
        //then forcing to HTTPS

        //but first checking maybe there were redirection already to prevent infinite redirections
        //due to possible buggy ssl detection on server
        $blAlreadyRedirected = Registry::getConfig()->getRequestParameter('sslredirect') == 'forced';

        if ($this->getIsOrderStep()) {
            //additional check if we really really have a user now
            //and the basket is not empty
            $oBasket = $this->getSession()->getBasket();
            $blPsBasketReservationEnabled = $myConfig->getConfigParam('blPsBasketReservationEnabled');
            if ($blPsBasketReservationEnabled && (!$oBasket || ($oBasket && !$oBasket->getProductsCount()))) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', true, 302);
            }

            $oUser = $this->getUser();
            if (!$oUser && ($oBasket && $oBasket->getProductsCount() > 0)) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', false, 302);
            } elseif (!$oBasket || !$oUser || ($oBasket && !$oBasket->getProductsCount())) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=start', false, 302);
            }
        }

        $sFncParameter = Registry::getConfig()->getRequestParameter('fnc');
        if ($myConfig->getCurrentShopURL() != $myConfig->getSSLShopURL() && !$blAlreadyRedirected && !$sFncParameter) {
            $sPayErrorParameter = Registry::getConfig()->getRequestParameter('payerror');
            $sPayErrorTextParameter = Registry::getConfig()->getRequestParameter('payerrortext');
            $shopSecureHomeURL = $myConfig->getShopSecureHomeURL();

            $sPayError = $sPayErrorParameter ? 'payerror=' . $sPayErrorParameter : '';
            $sPayErrorText = $sPayErrorTextParameter ? 'payerrortext=' . $sPayErrorTextParameter : '';
            $sRedirectURL = $shopSecureHomeURL . 'sslredirect=forced&cl=payment&' . $sPayError . "&" . $sPayErrorText;
            Registry::getUtils()->redirect($sRedirectURL, true, 302);
        }

        if (!$this->getAllSetsCnt()) {
            // no fitting shipping set found, setting default empty payment
            $this->_setDefaultEmptyPayment();
            Registry::getSession()->setVariable('sShipSet', null);
        }

        $this->_unsetPaymentErrors();

        return $this->_sThisTemplate;
    }

    /**
     * Set default empty payment. If config param 'blOtherCountryOrder' is on,
     * tries to set 'oxempty' payment to aViewData['oxemptypayment'].
     * On error sets aViewData['payerror'] to -2
     * @deprecated underscore prefix violates PSR12, will be renamed to "setDefaultEmptyPayment" in next major
     */
    protected function _setDefaultEmptyPayment() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // no shipping method there !!
        if ($this->getConfig()->getConfigParam('blOtherCountryOrder')) {
            $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
            if ($oPayment->load('oxempty')) {
                $this->_oEmptyPayment = $oPayment;
            } else {
                // some error with setup ??
                $this->_sPaymentError = -2;
            }
        } else {
            $this->_sPaymentError = -2;
        }
    }

    /**
     * Unsets payment errors from session
     * @deprecated underscore prefix violates PSR12, will be renamed to "unsetPaymentErrors" in next major
     */
    protected function _unsetPaymentErrors() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iPayError = Registry::getConfig()->getRequestParameter('payerror');
        $sPayErrorText = Registry::getConfig()->getRequestParameter('payerrortext');

        if (!($iPayError || $sPayErrorText)) {
            $iPayError = Registry::getSession()->getVariable('payerror');
            $sPayErrorText = Registry::getSession()->getVariable('payerrortext');
        }

        if ($iPayError) {
            Registry::getSession()->deleteVariable('payerror');
            $this->_sPaymentError = $iPayError;
        }
        if ($sPayErrorText) {
            Registry::getSession()->deleteVariable('payerrortext');
            $this->_sPaymentErrorText = $sPayErrorText;
        }
    }

    /**
     * Changes shipping set to chosen one. Sets basket status to not up-to-date, which later
     * forces to recalculate it
     */
    public function changeshipping()
    {
        $oSession = $this->getSession();

        $oBasket = $oSession->getBasket();
        $oBasket->setShipping(null);
        $oBasket->onUpdate();
        $oSession->setVariable('sShipSet', $this->getConfig()->getRequestParameter('sShipSet'));
    }

    /**
     * Validates oxiddebitnote user payment data.
     * Returns null if problems on validating occured. If everything
     * is OK - returns "order" and redirects to payment confirmation
     * page.
     *
     * Session variables:
     * <b>paymentid</b>, <b>dynvalue</b>, <b>payerror</b>
     *
     * @return  mixed
     */
    public function validatePayment()
    {
        $myConfig = $this->getConfig();
        $oSession = $this->getSession();

        //#1308C - check user. Function is executed before render(), and oUser is not set!
        // Set it manually for use in methods getPaymentList(), getShippingSetList()...
        $oUser = $this->getUser();
        if (!$oUser) {
            $oSession->setVariable('payerror', 2);

            return;
        }

        if (!($sShipSetId = Registry::getConfig()->getRequestParameter('sShipSet'))) {
            $sShipSetId = $oSession->getVariable('sShipSet');
        }
        if (!($sPaymentId = Registry::getConfig()->getRequestParameter('paymentid'))) {
            $sPaymentId = $oSession->getVariable('paymentid');
        }
        if (!($aDynvalue = Registry::getConfig()->getRequestParameter('dynvalue'))) {
            $aDynvalue = $oSession->getVariable('dynvalue');
        }

        // A. additional protection
        if (!$myConfig->getConfigParam('blOtherCountryOrder') && $sPaymentId == 'oxempty') {
            $sPaymentId = '';
        }

        //#1308C - check if we have paymentID, and it really exists
        if (!$sPaymentId) {
            $oSession->setVariable('payerror', 1);

            return;
        }

        $oBasket = $oSession->getBasket();
        $oBasket->setPayment(null);
        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        $oPayment->load($sPaymentId);

        // getting basket price for payment calculation
        $dBasketPrice = $oBasket->getPriceForPayment();

        $blOK = $oPayment->isValidPayment($aDynvalue, $myConfig->getShopId(), $oUser, $dBasketPrice, $sShipSetId);

        if ($blOK) {
            $oSession->setVariable('paymentid', $sPaymentId);
            $oSession->setVariable('dynvalue', $aDynvalue);
            $oBasket->setShipping($sShipSetId);
            $oSession->deleteVariable('_selected_paymentid');

            return 'order';
        } else {
            $oSession->setVariable('payerror', $oPayment->getPaymentErrorNumber());

            //#1308C - delete paymentid from session, and save selected it just for view
            $oSession->deleteVariable('paymentid');
            $oSession->setVariable('_selected_paymentid', $sPaymentId);

            return;
        }
    }

    /**
     * Template variable getter. Returns paymentlist
     *
     * @return object
     */
    public function getPaymentList()
    {
        if ($this->_oPaymentList === null) {
            $this->_oPaymentList = false;

            $sActShipSet = Registry::getConfig()->getRequestParameter('sShipSet');
            if (!$sActShipSet) {
                $sActShipSet = Registry::getSession()->getVariable('sShipSet');
            }

            $oBasket = $this->getSession()->getBasket();

            // load sets, active set, and active set payment list
            list($aAllSets, $sActShipSet, $aPaymentList) =
                Registry::get(DeliverySetList::class)->getDeliverySetData($sActShipSet, $this->getUser(), $oBasket);

            $oBasket->setShipping($sActShipSet);

            // calculating payment expences for preview for each payment
            $this->_setValues($aPaymentList, $oBasket);
            $this->_oPaymentList = $aPaymentList;
            $this->_aAllSets = $aAllSets;
        }

        return $this->_oPaymentList;
    }

    /**
     * Template variable getter. Returns all delivery sets
     *
     * @return array
     */
    public function getAllSets()
    {
        if ($this->_aAllSets === null) {
            $this->_aAllSets = false;

            if ($this->getPaymentList()) {
                return $this->_aAllSets;
            }
        }

        return $this->_aAllSets;
    }

    /**
     * Template variable getter. Returns number of delivery sets
     *
     * @return integer
     */
    public function getAllSetsCnt()
    {
        if ($this->_iAllSetsCnt === null) {
            $this->_iAllSetsCnt = 0;

            if ($this->getPaymentList()) {
                $this->_iAllSetsCnt = count($this->_aAllSets);
            }
        }

        return $this->_iAllSetsCnt;
    }

    /**
     * Calculate payment cost for each payment. Sould be removed later
     *
     * @param array                                      $aPaymentList payments array
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     * @deprecated underscore prefix violates PSR12, will be renamed to "setValues" in next major
     */
    protected function _setValues(&$aPaymentList, $oBasket = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_array($aPaymentList)) {
            foreach ($aPaymentList as $oPayment) {
                $oPayment->calculate($oBasket);
                $oPayment->aDynValues = $oPayment->getDynValues();
                if ($oPayment->oxpayments__oxchecked->value) {
                    $this->_sCheckedId = $oPayment->getId();
                }
            }
        }
    }

    /**
     * Template variable getter. Returns payment object "oxempty"
     *
     * @return object
     */
    public function getEmptyPayment()
    {
        return $this->_oEmptyPayment;
    }

    /**
     * Template variable getter. Returns error of payments
     *
     * @return string
     */
    public function getPaymentError()
    {
        return $this->_sPaymentError;
    }

    /**
     * Template variable getter. Returns error text of payments
     *
     * @return string
     */
    public function getPaymentErrorText()
    {
        return $this->_sPaymentErrorText;
    }

    /**
     * Return if old style bank code is supported.
     *
     * @return bool
     */
    public function isOldDebitValidationEnabled()
    {
        return !$this->getConfig()->getConfigParam('blSkipDebitOldBankInfo');
    }

    /**
     * Template variable getter. Returns dyn values
     *
     * @return array
     */
    public function getDynValue()
    {
        if ($this->_aDynValue === null) {
            $this->_aDynValue = false;

            // flyspray#1217 (sarunas)
            if (($aDynValue = Registry::getSession()->getVariable('dynvalue'))) {
                $this->_aDynValue = $aDynValue;
            } else {
                $this->_aDynValue = Registry::getConfig()->getRequestParameter("dynvalue");
            }

            // #701A
            // assign debit note payment params to view data
            $aPaymentList = $this->getPaymentList();
            if (isset($aPaymentList['oxiddebitnote'])) {
                $this->_assignDebitNoteParams();
            }
        }

        return $this->_aDynValue;
    }

    /**
     * Assign debit note payment values to view data. Loads user debit note payment
     * if available and assigns payment data to $this->_aDynValue
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignDebitNoteParams" in next major
     */
    protected function _assignDebitNoteParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // #701A
        $oUserPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        //such info available ?
        if ($oUserPayment->getPaymentByPaymentType($this->getUser(), 'oxiddebitnote')) {
            $sUserPaymentField = 'oxuserpayments__oxvalue';
            $aAddPaymentData = Registry::getUtils()->assignValuesFromText($oUserPayment->$sUserPaymentField->value);

            //checking if some of values is allready set in session - leave it
            foreach ($aAddPaymentData as $oData) {
                if (
                    !isset($this->_aDynValue[$oData->name]) ||
                    (isset($this->_aDynValue[$oData->name]) && !$this->_aDynValue[$oData->name])
                ) {
                    $this->_aDynValue[$oData->name] = $oData->value;
                }
            }
        }
    }

    /**
     * Get checked payment ID. Tries to get checked payment ID from session,
     * if fails, then tries to get payment ID from last order.
     *
     * @return string
     */
    public function getCheckedPaymentId()
    {
        if ($this->_sCheckedPaymentId === null) {
            if (!($sPaymentID = Registry::getConfig()->getRequestParameter('paymentid'))) {
                $sPaymentID = Registry::getSession()->getVariable('paymentid');
            }
            if ($sPaymentID) {
                $sCheckedId = $sPaymentID;
            } elseif (($sSelectedPaymentID = Registry::getSession()->getVariable('_selected_paymentid'))) {
                $sCheckedId = $sSelectedPaymentID;
            } else {
                // #1010A.
                if ($oUser = $this->getUser()) {
                    $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
                    if (($sLastPaymentId = $oOrder->getLastUserPaymentType($oUser->getId()))) {
                        $sCheckedId = $sLastPaymentId;
                    }
                }
            }

            // #M253 set to selected payment in db
            if (!$sCheckedId && $this->_sCheckedId) {
                $sCheckedId = $this->_sCheckedId;
            }

            // #646
            $oPaymentList = $this->getPaymentList();
            if (isset($oPaymentList) && $oPaymentList && !isset($oPaymentList[$sCheckedId])) {
                end($oPaymentList);
                $sCheckedId = key($oPaymentList);
            }
            $this->_sCheckedPaymentId = $sCheckedId;
        }

        return $this->_sCheckedPaymentId;
    }

    /**
     * Template variable getter. Returns payment list count
     *
     * @return integer
     */
    public function getPaymentCnt()
    {
        if ($this->_iPaymentCnt === null) {
            $this->_iPaymentCnt = false;

            if ($oPaymentList = $this->getPaymentList()) {
                $this->_iPaymentCnt = count($oPaymentList);
            }
        }

        return $this->_iPaymentCnt;
    }

    /**
     * Function to check if array values are empty againts given array keys
     *
     * @param array $aData array of data to check
     * @param array $aKeys array of array indexes
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkArrValuesEmpty" in next major
     */
    protected function _checkArrValuesEmpty($aData, $aKeys) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!is_array($aKeys) || count($aKeys) < 1) {
            return false;
        }

        foreach ($aKeys as $sKey) {
            if (isset($aData[$sKey]) && !empty($aData[$sKey])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];


        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('PAY', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Retuns config true if Vat is splitted
     *
     * @return array
     */
    public function isPaymentVatSplitted()
    {
        return $this->getConfig()->getConfigParam('blShowVATForPayCharge');
    }
}
