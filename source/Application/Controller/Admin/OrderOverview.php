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
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin order overview manager.
 * Collects order overview information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Overview.
 */
class OrderOverview extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates Order, passes
     * it's data to Smarty engine and returns name of template file
     * "order_overview.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();
        parent::render();

        $oOrder = oxNew(Order::class);
        $oCur = $myConfig->getActShopCurrencyObject();
        $oLang = Registry::getLang();

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            $oOrder->load($soxId);

            $this->_aViewData["edit"] = $oOrder;
            $this->_aViewData["aProductVats"] = $oOrder->getProductVats();
            $this->_aViewData["orderArticles"] = $oOrder->getOrderArticles();
            $this->_aViewData["giftCard"] = $oOrder->getGiftCard();
            $this->_aViewData["paymentType"] = $this->_getPaymentType($oOrder);
            $this->_aViewData["deliveryType"] = $oOrder->getDelSet();
            $sTsProtectsField = 'oxorder__oxtsprotectcosts';
            if ($oOrder->$sTsProtectsField->value) {
                $this->_aViewData["tsprotectcosts"] = $oLang->formatCurrency($oOrder->$sTsProtectsField->value, $oCur);
            }
        }

        // orders today
        $dSum = $oOrder->getOrderSum(true);
        $this->_aViewData["ordersum"] = $oLang->formatCurrency($dSum, $oCur);
        $this->_aViewData["ordercnt"] = $oOrder->getOrderCnt(true);

        // ALL orders
        $dSum = $oOrder->getOrderSum();
        $this->_aViewData["ordertotalsum"] = $oLang->formatCurrency($dSum, $oCur);
        $this->_aViewData["ordertotalcnt"] = $oOrder->getOrderCnt();
        $this->_aViewData["afolder"] = $myConfig->getConfigParam('aOrderfolder');
        $this->_aViewData["alangs"] = $oLang->getLanguageNames();

        $this->_aViewData["currency"] = $oCur;

        return "order_overview.tpl";
    }

    /**
     * Returns user payment used for current order.
     * just for preview user payment is set from oxPayment
     *
     * @param object $oOrder Order object
     *
     * @return UserPayment
     * @deprecated underscore prefix violates PSR12, will be renamed to "getPaymentType" in next major
     */
    protected function _getPaymentType($oOrder) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!($oUserPayment = $oOrder->getPaymentType()) && $oOrder->oxorder__oxpaymenttype->value) {
            $oPayment = oxNew(Payment::class);
            if ($oPayment->load($oOrder->oxorder__oxpaymenttype->value)) {
                // in case due to security reasons payment info was not kept in db
                $oUserPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
                $oUserPayment->oxpayments__oxdesc = new Field($oPayment->oxpayments__oxdesc->value);
            }
        }

        return $oUserPayment;
    }

    /**
     * Gets proper file name
     *
     * @param string $sFilename file name
     *
     * @return string
     */
    public function makeValidFileName($sFilename)
    {
        $sFilename = preg_replace('/[\s]+/', '_', $sFilename);
        $sFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $sFilename);

        return str_replace(' ', '_', $sFilename);
    }

    /**
     * Sends order.
     */
    public function sendorder()
    {
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($this->getEditObjectId())) {
            $oOrder->oxorder__oxsenddate = new Field(date("Y-m-d H:i:s", Registry::getUtilsDate()->getTime()));
            $oOrder->save();

            // #1071C
            $oOrderArticles = $oOrder->getOrderArticles();
            foreach ($oOrderArticles as $sOxid => $oArticle) {
                // remove canceled articles from list
                if ($oArticle->oxorderarticles__oxstorno->value == 1) {
                    $oOrderArticles->offsetUnset($sOxid);
                }
            }

            if ((Registry::getRequest()->getRequestEscapedParameter('sendmail'))) {
                // send eMail
                $oEmail = oxNew(Email::class);
                $oEmail->sendSendedNowMail($oOrder);
            }
        }
    }

    /**
     * Resets order shipping date.
     */
    public function resetorder()
    {
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($this->getEditObjectId())) {
            $oOrder->oxorder__oxsenddate = new Field("0000-00-00 00:00:00");
            $oOrder->save();
        }
    }

    /**
     * Get information about shipping status
     *
     * @return bool
     */
    public function canResetShippingDate()
    {
        $oOrder = oxNew(Order::class);
        $blCan = false;
        if ($oOrder->load($this->getEditObjectId())) {
            $blCan = $oOrder->oxorder__oxstorno->value == "0" &&
                     !($oOrder->oxorder__oxsenddate->value == "0000-00-00 00:00:00" || $oOrder->oxorder__oxsenddate->value == "-");
        }

        return $blCan;
    }
}
