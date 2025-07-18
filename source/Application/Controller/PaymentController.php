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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\PaymentList;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Payment manager.
 * Customer payment manager class. Performs payment validation function, etc.
 */
class PaymentController extends FrontendController
{
    /**
     * Payment-list
     *
     * @var object
     */
    protected $_oPaymentList = null;

    /**
     * Payment-list count
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
     * (if user object loading was not successful - redirects to start
     * page), loads user delivery/shipping information. According
     * to configuration in admin, user profile data loads delivery sets,
     * and possible payment methods. Returns name of template to render
     * payment::_sThisTemplate.
     *
     * @return  string  current template file name
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->getConfigParam('blPsBasketReservationEnabled')) {
            Registry::getSession()->getBasketReservations()->renewExpiration();
        }

        parent::render();

        //if it happens that you are not in SSL
        //then forcing to HTTPS

        //but first checking maybe there were redirection already to prevent infinite redirections
        //due to possible buggy ssl detection on server
        $blAlreadyRedirected = Registry::getRequest()->getRequestEscapedParameter('sslredirect') == 'forced';

        if ($this->getIsOrderStep()) {
            //additional check if we really have a user now
            //and the basket is not empty
            $oBasket = Registry::getSession()->getBasket();
            $blPsBasketReservationEnabled = $myConfig->getConfigParam('blPsBasketReservationEnabled');
            if ($blPsBasketReservationEnabled && (!$oBasket || !$oBasket->getProductsCount())) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', true, 302);
            }

            $oUser = $this->getUser();
            if (!$oUser && ($oBasket && $oBasket->getProductsCount() > 0)) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', false, 302);
            } elseif (!$oBasket || !$oUser || !$oBasket->getProductsCount()) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=start', false, 302);
            }
        }

        $sFncParameter = Registry::getRequest()->getRequestEscapedParameter('fnc');
        if ($myConfig->getCurrentShopURL() != $myConfig->getSSLShopURL() && !$blAlreadyRedirected && !$sFncParameter) {
            $sPayErrorParameter = Registry::getRequest()->getRequestEscapedParameter('payerror');
            $sPayErrorTextParameter = Registry::getRequest()->getRequestEscapedParameter('payerrortext');
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
        if (Registry::getConfig()->getConfigParam('blOtherCountryOrder')) {
            $oPayment = oxNew(Payment::class);
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
        $iPayError = Registry::getRequest()->getRequestEscapedParameter('payerror');
        $sPayErrorText = Registry::getRequest()->getRequestEscapedParameter('payerrortext');

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
        $oSession = Registry::getSession();

        $oBasket = $oSession->getBasket();
        $oBasket->setShipping(null);
        $oBasket->onUpdate();
        $oSession->setVariable('sShipSet', Registry::getRequest()->getRequestEscapedParameter('sShipSet'));
    }

    /**
     * Validates oxiddebitnote user payment data.
     * Returns null if problems on validating occurred. If everything
     * is OK - returns "order" and redirects to payment confirmation
     * page.
     *
     * Session variables:
     * <b>paymentid</b>, <b>dynvalue</b>, <b>payerror</b>
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function validatePayment()
    {
        $myConfig = Registry::getConfig();
        $oSession = Registry::getSession();

        //#1308C - check user. Function is executed before render(), and oUser is not set!
        // Set it manually for use in methods getPaymentList(), getShippingSetList()...
        $oUser = $this->getUser();
        if (!$oUser) {
            $oSession->setVariable('payerror', 2);

            return;
        }

        if (!($sShipSetId = Registry::getRequest()->getRequestEscapedParameter('sShipSet'))) {
            $sShipSetId = $oSession->getVariable('sShipSet');
        }
        if (!($sPaymentId = Registry::getRequest()->getRequestEscapedParameter('paymentid'))) {
            $sPaymentId = $oSession->getVariable('paymentid');
        }
        if (!($aDynvalue = Registry::getRequest()->getRequestEscapedParameter('dynvalue'))) {
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
        $oPayment = oxNew(Payment::class);
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
        }
    }

    /**
     * Template variable getter. Returns payment-list
     *
     * @return PaymentList
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getPaymentList()
    {
        if ($this->_oPaymentList === null) {
            $this->_oPaymentList = false;

            $sActShipSet = Registry::getRequest()->getRequestEscapedParameter('sShipSet');
            if (!$sActShipSet) {
                $sActShipSet = Registry::getSession()->getVariable('sShipSet');
            }

            $oBasket = Registry::getSession()->getBasket();

            // load sets, active set, and active set payment list
            list($aAllSets, $sActShipSet, $aPaymentList) =
                Registry::get(DeliverySetList::class)->getDeliverySetData($sActShipSet, $this->getUser(), $oBasket);

            $oBasket->setShipping($sActShipSet);

            // calculating payment expenses for preview for each payment
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
     * Calculate payment cost for each payment. Should be removed later
     *
     * @param array                                      $aPaymentList payments array
     * @param Basket $oBasket      basket object
     * @deprecated underscore prefix violates PSR12, will be renamed to "setValues" in next major
     */
    protected function _setValues($aPaymentList, $oBasket = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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
        return !Registry::getConfig()->getConfigParam('blSkipDebitOldBankInfo');
    }

    /**
     * Template variable getter. Returns dyn values
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getDynValue()
    {
        if ($this->_aDynValue === null) {
            $this->_aDynValue = false;

            // flyspray#1217 (sarunas)
            if (($aDynValue = Registry::getSession()->getVariable('dynvalue'))) {
                $this->_aDynValue = $aDynValue;
            } else {
                $this->_aDynValue = Registry::getRequest()->getRequestEscapedParameter('dynvalue');
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
        $oUserPayment = oxNew(UserPayment::class);
        //such info available ?
        if ($oUserPayment->getPaymentByPaymentType($this->getUser(), 'oxiddebitnote')) {
            $sUserPaymentField = 'oxuserpayments__oxvalue';
            $aAddPaymentData = Registry::getUtils()->assignValuesFromText($oUserPayment->$sUserPaymentField->value);

            //checking if some of the values is already set in session - leave it
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCheckedPaymentId()
    {
        if ($this->_sCheckedPaymentId === null) {
            if (!($sPaymentID = Registry::getRequest()->getRequestEscapedParameter('paymentid'))) {
                $sPaymentID = Registry::getSession()->getVariable('paymentid');
            }
            if ($sPaymentID) {
                $sCheckedId = $sPaymentID;
            } elseif (($sSelectedPaymentID = Registry::getSession()->getVariable('_selected_paymentid'))) {
                $sCheckedId = $sSelectedPaymentID;
            } else {
                // #1010A.
                if ($oUser = $this->getUser()) {
                    $oOrder = oxNew(Order::class);
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
     * Function to check if array values are empty against given array keys
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
            if (!empty($aData[$sKey])) {
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
     * Returns config true if Vat is split
     *
     * @return array
     */
    public function isPaymentVatSplitted()
    {
        return Registry::getConfig()->getConfigParam('blShowVATForPayCharge');
    }
}
