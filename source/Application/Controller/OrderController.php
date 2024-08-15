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
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\BasketContentMarkGenerator;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;

/**
 * Order manager. Arranges user ordering data, checks/validates
 * it, on success stores ordering data to DB.
 */
class OrderController extends FrontendController
{
    /**
     * Payment object
     *
     * @var object
     */
    protected $_oPayment = null;

    /**
     * Active basket
     *
     * @var Basket
     */
    protected $_oBasket = null;

    /**
     * Order user remark
     *
     * @var string
     */
    protected $_sOrderRemark = null;

    /**
     * Basket articlelist
     *
     * @var object
     */
    protected $_oBasketArtList = null;

    /**
     * Remote Address
     *
     * @var string
     */
    protected $_sRemoteAddress = null;

    /**
     * Delivery address
     *
     * @var Address
     */
    protected $_oDelAddress = null;

    /**
     * Shipping set
     *
     * @var object
     */
    protected $_oShipSet = null;

    /**
     * Config option "blConfirmAGB"
     *
     * @var bool
     */
    protected $_blConfirmAGB = null;

    /**
     * Config option "blShowOrderButtonOnTop"
     *
     * @var bool
     */
    protected $_blShowOrderButtonOnTop = null;

    /**
     * Boolean of option "blConfirmAGB" error
     *
     * @var bool
     */
    protected $_blConfirmAGBError = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/checkout/order.tpl';

    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_blIsOrderStep = true;

    /**
     * Count of wrapping + cards options
     */
    protected $_iWrapCnt = null;

    /**
     * Loads basket Session::getBasket(), sets $this->oBasket->blCalcNeeded = true to
     * recalculate, sets back basket to session Session::setBasket(), executes
     * parent::init().
     */
    public function init()
    {
        // disabling performance control variable
        Registry::getConfig()->setConfigParam('bl_perfCalcVatOnlyForBasketOrder', false);

        // recalc basket cause of payment stuff
        if ($oBasket = $this->getBasket()) {
            $oBasket->onUpdate();
        }

        parent::init();
    }

    /**
     * Executes parent::render(), if basket is empty - redirects to main page
     * and exits the script (Order::validateOrder()). Loads and passes payment
     * info to template engine. Refreshes basket articles info by additionally loading
     * each article object (Order::getProdFromBasket()), adds customer
     * addressing/delivering data (Order::getDelAddressInfo()) and delivery sets
     * info (Order::getShipping()).
     *
     * @return string Returns name of template to render order::_sThisTemplate
     */
    public function render()
    {
        if ($this->getIsOrderStep()) {
            $oBasket = $this->getBasket();
            $myConfig = Registry::getConfig();

            if ($myConfig->getConfigParam('blPsBasketReservationEnabled')) {
                $this->getSession()->getBasketReservations()->renewExpiration();
                if (!$oBasket || ($oBasket && !$oBasket->getProductsCount())) {
                    Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', true, 302);
                }
            }

            // can we proceed with ordering ?
            $oUser = $this->getUser();
            if (!$oUser && ($oBasket && $oBasket->getProductsCount() > 0)) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl() . 'cl=basket', false, 302);
            } elseif (!$oBasket || !$oUser || ($oBasket && !$oBasket->getProductsCount())) {
                Registry::getUtils()->redirect($myConfig->getShopHomeUrl(), false, 302);
            }

            // payment is set ?
            if (!$this->getPayment()) {
                // redirecting to payment step on error ...
                Registry::getUtils()->redirect($myConfig->getShopCurrentURL() . '&cl=payment', true, 302);
            }
        }

        parent::render();

        // reload blocker
        if (!Registry::getSession()->getVariable('sess_challenge')) {
            Registry::getSession()->setVariable('sess_challenge', $this->getUtilsObjectInstance()->generateUID());
        }

        return $this->_sThisTemplate;
    }

    /**
     * Checks for order rules confirmation ("ord_agb", "ord_custinfo" form values)(if no
     * rules agreed - returns to order view), loads basket contents (plus applied
     * price/amount discount if available - checks for stock, checks user data (if no
     * data is set - returns to user login page). Stores order info to database
     * (Order::finalizeOrder()). According to sum for items automatically assigns
     * user to special user group (User::onOrderExecute(); if this option is not
     * disabled in admin). Finally, you will be redirected to next page (order::_getNextStep()).
     *
     * @return string|null
     */
    public function execute()
    {
        if (!$this->getSession()->checkSessionChallenge()) {
            return;
        }

        if (!$this->_validateTermsAndConditions()) {
            $this->_blConfirmAGBError = 1;

            return;
        }

        // additional check if we really have a user now
        $oUser = $this->getUser();
        if (!$oUser) {
            return 'user';
        }

        // get basket contents
        $oBasket = $this->getSession()->getBasket();
        if ($oBasket->getProductsCount()) {
            try {
                $oOrder = oxNew(Order::class);

                //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
                $iSuccess = $oOrder->finalizeOrder($oBasket, $oUser);

                // performing special actions after user finishes order (assignment to special user groups)
                $oUser->onOrderExecute($oBasket, $iSuccess);

                // proceeding to next view
                return $this->_getNextStep($iSuccess);
            } catch (OutOfStockException $oEx) {
                $oEx->setDestination('basket');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false, true, 'basket');
            } catch (NoArticleException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            } catch (ArticleInputException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            }
        }
    }

    /**
     * Template variable getter. Returns payment object
     *
     * @return object
     */
    public function getPayment()
    {
        if ($this->_oPayment === null) {
            $this->_oPayment = false;

            $oBasket = $this->getBasket();
            $oUser = $this->getUser();

            // payment is set ?
            $sPaymentid = $oBasket->getPaymentId();
            $oPayment = oxNew(Payment::class);

            if (
                $sPaymentid && $oPayment->load($sPaymentid) &&
                $oPayment->isValidPayment(
                    Registry::getSession()->getVariable('dynvalue'),
                    Registry::getConfig()->getShopId(),
                    $oUser,
                    $oBasket->getPriceForPayment(),
                    Registry::getSession()->getVariable('sShipSet')
                )
            ) {
                $this->_oPayment = $oPayment;
            }
        }

        return $this->_oPayment;
    }

    /**
     * Template variable getter. Returns active basket
     *
     * @return Basket
     */
    public function getBasket()
    {
        if ($this->_oBasket === null) {
            $this->_oBasket = false;
            if ($oBasket = $this->getSession()->getBasket()) {
                $this->_oBasket = $oBasket;
            }
        }

        return $this->_oBasket;
    }

    /**
     * Template variable getter. Returns execution function name
     *
     * @return string
     */
    public function getExecuteFnc()
    {
        return 'execute';
    }

    /**
     * Template variable getter. Returns user remark
     *
     * @return string
     */
    public function getOrderRemark()
    {
        if ($this->_sOrderRemark === null) {
            $this->_sOrderRemark = false;
            if ($sRemark = Registry::getSession()->getVariable('ordrem')) {
                $this->_sOrderRemark = Registry::getConfig()->checkParamSpecialChars($sRemark);
            }
        }

        return $this->_sOrderRemark;
    }

    /**
     * Template variable getter. Returns basket article list
     *
     * @return object
     */
    public function getBasketArticles()
    {
        if ($this->_oBasketArtList === null) {
            $this->_oBasketArtList = false;
            if ($oBasket = $this->getBasket()) {
                $this->_oBasketArtList = $oBasket->getBasketArticles();
            }
        }

        return $this->_oBasketArtList;
    }

    /**
     * Template variable getter. Returns delivery address
     *
     * @return object
     */
    public function getDelAddress()
    {
        if ($this->_oDelAddress === null) {
            $this->_oDelAddress = false;
            $oOrder = oxNew(Order::class);
            $this->_oDelAddress = $oOrder->getDelAddressInfo();
        }

        return $this->_oDelAddress;
    }

    /**
     * Template variable getter. Returns shipping set
     *
     * @return object
     */
    public function getShipSet()
    {
        if ($this->_oShipSet === null) {
            $this->_oShipSet = false;
            if ($oBasket = $this->getBasket()) {
                $oShipSet = oxNew(DeliverySet::class);
                if ($oShipSet->load($oBasket->getShippingId())) {
                    $this->_oShipSet = $oShipSet;
                }
            }
        }

        return $this->_oShipSet;
    }

    /**
     * Template variable getter. Returns if option "blConfirmAGB" is on
     *
     * @return bool
     */
    public function isConfirmAGBActive()
    {
        if ($this->_blConfirmAGB === null) {
            $this->_blConfirmAGB = false;
            $this->_blConfirmAGB = Registry::getConfig()->getConfigParam('blConfirmAGB');
        }

        return $this->_blConfirmAGB;
    }

    /**
     * Template variable getter. Returns if option "blConfirmAGB" was not set
     *
     * @return bool
     */
    public function isConfirmAGBError()
    {
        return $this->_blConfirmAGBError;
    }

    /**
     * Template variable getter. Returns if option "blShowOrderButtonOnTop" is on
     *
     * @return bool
     */
    public function showOrderButtonOnTop()
    {
        if ($this->_blShowOrderButtonOnTop === null) {
            $this->_blShowOrderButtonOnTop = false;
            $this->_blShowOrderButtonOnTop = Registry::getConfig()->getConfigParam('blShowOrderButtonOnTop');
        }

        return $this->_blShowOrderButtonOnTop;
    }

    /**
     * Returns wrapping options availability state (TRUE/FALSE)
     *
     * @return bool
     */
    public function isWrapping()
    {
        if (!$this->getViewConfig()->getShowGiftWrapping()) {
            return false;
        }

        if ($this->_iWrapCnt === null) {
            $this->_iWrapCnt = 0;

            $oWrap = oxNew(Wrapping::class);
            $this->_iWrapCnt += $oWrap->getWrappingCount('WRAP');
            $this->_iWrapCnt += $oWrap->getWrappingCount('CARD');
        }

        return (bool) $this->_iWrapCnt;
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
        $aPath['title'] = Registry::getLang()->translateString('ORDER', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Return error number
     *
     * @return int
     */
    public function getAddressError()
    {
        return Registry::getRequest()->getRequestEscapedParameter('iAddressError');
    }

    /**
     * Return users set delivery address md5
     *
     * @return string
     */
    public function getDeliveryAddressMD5()
    {
        // bill address
        $oUser = $this->getUser();
        $sDelAddress = $oUser->getEncodedDeliveryAddress();

        // delivery address
        if (Registry::getSession()->getVariable('deladrid')) {
            $oDelAddress = oxNew(Address::class);
            $oDelAddress->load(Registry::getSession()->getVariable('deladrid'));

            $sDelAddress .= $oDelAddress->getEncodedDeliveryAddress();
        }

        return $sDelAddress;
    }

    /**
     * Method returns object with explanation marks for articles in basket.
     *
     * @return BasketContentMarkGenerator
     */
    public function getBasketContentMarkGenerator()
    {
        return oxNew(BasketContentMarkGenerator::class, $this->getBasket());
    }

    /**
     * Returns next order step. If ordering was successful - returns string "thankyou" (possible
     * additional parameters), otherwise - returns string "payment" with additional
     * error parameters.
     *
     * @param integer $iSuccess status code
     *
     * @return  string  $sNextStep  partial parameter url for next step
     * @deprecated underscore prefix violates PSR12, will be renamed to "getNextStep" in next major
     */
    protected function _getNextStep($iSuccess) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sNextStep = 'thankyou';

        //little trick with switch for multiple cases
        switch (true) {
            case ($iSuccess === Order::ORDER_STATE_MAILINGERROR):
                $sNextStep = 'thankyou?mailerror=1';
                break;
            case ($iSuccess === Order::ORDER_STATE_INVALIDDElADDRESSCHANGED):
                $sNextStep = 'order?iAddressError=1';
                break;
            case ($iSuccess === Order::ORDER_STATE_BELOWMINPRICE):
                $sNextStep = 'order';
                break;
            case ($iSuccess === Order::ORDER_STATE_VOUCHERERROR):
                $sNextStep = 'basket';
                break;
            case ($iSuccess === Order::ORDER_STATE_PAYMENTERROR):
                // no authentication, kick back to payment methods
                Registry::getSession()->setVariable('payerror', 2);
                $sNextStep = 'payment?payerror=2';
                break;
            case ($iSuccess === Order::ORDER_STATE_ORDEREXISTS):
                break; // reload blocker active
            case (is_numeric($iSuccess) && $iSuccess > 3):
                Registry::getSession()->setVariable('payerror', $iSuccess);
                $sNextStep = 'payment?payerror=' . $iSuccess;
                break;
            case (!is_numeric($iSuccess) && $iSuccess):
                //instead of error code getting error text and setting payerror to -1
                Registry::getSession()->setVariable('payerror', -1);
                $iSuccess = urlencode($iSuccess);
                $sNextStep = 'payment?payerror=-1&payerrortext=' . $iSuccess;
                break;
            default:
                break;
        }

        return $sNextStep;
    }

    /**
     * Validates whether necessary terms and conditions checkboxes were checked.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "validateTermsAndConditions" in next major
     */
    protected function _validateTermsAndConditions() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $blValid = true;
        $oConfig = Registry::getConfig();
        $oRequest = Registry::getRequest();

        if ($oConfig->getConfigParam('blConfirmAGB') && !$oRequest->getRequestEscapedParameter('ord_agb')) {
            $blValid = false;
        }

        if ($oConfig->getConfigParam('blEnableIntangibleProdAgreement')) {
            $oBasket = $this->getBasket();

            $blDownloadableProductsAgreement = $oRequest->getRequestEscapedParameter('oxdownloadableproductsagreement');
            if ($blValid && $oBasket->hasArticlesWithDownloadableAgreement() && !$blDownloadableProductsAgreement) {
                $blValid = false;
            }

            $blServiceProductsAgreement = $oRequest->getRequestEscapedParameter('oxserviceproductsagreement');
            if ($blValid && $oBasket->hasArticlesWithIntangibleAgreement() && !$blServiceProductsAgreement) {
                $blValid = false;
            }
        }

        return $blValid;
    }

    /**
     * @return UtilsObject
     */
    protected function getUtilsObjectInstance()
    {
        return Registry::getUtilsObject();
    }
}
