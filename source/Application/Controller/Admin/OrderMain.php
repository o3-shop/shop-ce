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
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsDate;

/**
 * Admin article main order manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Orders -> Display Orders -> Main.
 */
class OrderMain extends AdminDetailsController
{
    /**
     * Whitelist of parameters whose change does not require a full order recalculation.
     *
     * @var array
     */
    protected $fieldsTriggerNoOrderRecalculation = ['oxorder__oxordernr',
                                                         'oxorder__oxbillnr',
                                                         'oxorder__oxtrackcode',
                                                         'oxorder__oxpaid'];

    /**
     * Executes parent method parent::render(), creates Order and
     * UserPayment objects, passes data to Smarty engine and returns
     * name of template file "order_main.tpl".
     *
     * @return string
     * @throws ArticleException
     * @throws ArticleInputException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    public function render()
    {
        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oOrder = oxNew(Order::class);
            $oOrder->load($soxId);

            // paid ?
            $sOxPaidField = 'oxorder__oxpaid';
            $sDelTypeField = 'oxorder__oxdeltype';

            if ($oOrder->$sOxPaidField->value != "0000-00-00 00:00:00") {
                $oOrder->blIsPaid = true;
                /** @var UtilsDate $oUtilsDate */
                $oUtilsDate = Registry::getUtilsDate();
                $oOrder->$sOxPaidField = new Field($oUtilsDate->formatDBDate($oOrder->$sOxPaidField->value));
            }

            $this->_aViewData["edit"] = $oOrder;
            $this->_aViewData["paymentType"] = $oOrder->getPaymentType();
            $this->_aViewData["oShipSet"] = $oOrder->getShippingSetList();

            if ($oOrder->$sDelTypeField->value) {
                // order user
                $oUser = oxNew(User::class);
                $oUser->load($oOrder->oxorder__oxuserid->value);

                // order sum in default currency
                $dPrice = $oOrder->oxorder__oxtotalbrutsum->value / $oOrder->oxorder__oxcurrate->value;

                /** @var \OxidEsales\Eshop\Application\Model\PaymentList $oPaymentList */
                $oPaymentList = Registry::get(\OxidEsales\Eshop\Application\Model\PaymentList::class);
                $this->_aViewData["oPayments"] =
                                        $oPaymentList->getPaymentList($oOrder->$sDelTypeField->value, $dPrice, $oUser);
            }

            // any voucher used ?
            $this->_aViewData["aVouchers"] = $oOrder->getVoucherNrList();
        }

        $this->_aViewData["sNowValue"] = date("Y-m-d H:i:s", Registry::getUtilsDate()->getTime());

        return "order_main.tpl";
    }

    /**
     * Saves main orders configuration parameters.
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        $oOrder = oxNew(Order::class);
        if ($soxId != "-1") {
            $oOrder->load($soxId);
        } else {
            $aParams['oxorder__oxid'] = null;
        }

        $needOrderRecalculate = false;
        if (is_array($aParams)) {
            foreach ($aParams as $parameter => $value) {
                //parameter changes for not whitelisted parameters trigger order recalculation
                $orderField = $oOrder->$parameter;
                if (($value != $orderField->value) && !in_array($parameter, $this->fieldsTriggerNoOrderRecalculation)) {
                    $needOrderRecalculate = true;
                }
            }
        }

        //change payment
        $sPayId = Registry::getRequest()->getRequestEscapedParameter('setPayment');
        if (!empty($sPayId) && ($sPayId != $oOrder->oxorder__oxpaymenttype->value)) {
            $aParams['oxorder__oxpaymenttype'] = $sPayId;
            $needOrderRecalculate = true;
        }

        $oOrder->assign($aParams);

        $aDynvalues = Registry::getRequest()->getRequestEscapedParameter('dynvalue');
        if (isset($aDynvalues)) {
            $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
            $oPayment->load($oOrder->oxorder__oxpaymentid->value);
            $oPayment->oxuserpayments__oxvalue->setValue(Registry::getUtils()->assignValuesToText($aDynvalues));
            $oPayment->save();
            $needOrderRecalculate = true;
        }
        //change delivery set
        $sDelSetId = Registry::getRequest()->getRequestEscapedParameter('setDelSet');
        if (!empty($sDelSetId) && ($sDelSetId != $oOrder->oxorder__oxdeltype->value)) {
            $oOrder->oxorder__oxpaymenttype->setValue("oxempty");
            $oOrder->setDelivery($sDelSetId);
            $needOrderRecalculate = true;
        } else {
            // keeps old delivery cost
            $oOrder->reloadDelivery(false);
        }

        if ($needOrderRecalculate) {
            // keeps old discount
            $oOrder->reloadDiscount(false);
            $oOrder->recalculateOrder();
        } else {
            //nothing changed in order that requires a full recalculation
            $oOrder->save();
        }

        // set oxid if inserted
        $this->setEditObjectId($oOrder->getId());
    }

    /**
     * Sends order.
     */
    public function sendOrder()
    {
        $soxId = $this->getEditObjectId();
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($soxId)) {
            // #632A
            $oOrder->oxorder__oxsenddate = new Field(date("Y-m-d H:i:s", Registry::getUtilsDate()->getTime()));
            $oOrder->save();

            // #1071C
            $oOrder->getOrderArticles(true);
            if (Registry::getRequest()->getRequestEscapedParameter('sendmail')) {
                // send eMail
                $oEmail = oxNew(Email::class);
                $oEmail->sendSendedNowMail($oOrder);
            }
            $this->onOrderSend();
        }
    }

    /**
     * Sends download links.
     */
    public function sendDownloadLinks()
    {
        $soxId = $this->getEditObjectId();
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($soxId)) {
            $oEmail = oxNew(Email::class);
            $oEmail->sendDownloadLinksMail($oOrder);
        }
    }

    /**
     * Resets order shipping date.
     */
    public function resetOrder()
    {
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($this->getEditObjectId())) {
            $oOrder->oxorder__oxsenddate = new Field("0000-00-00 00:00:00");
            $oOrder->save();

            $this->onOrderReset();
        }
    }

    /**
     * Method is used for overriding.
     */
    protected function onOrderSend()
    {
    }

    /**
     * Method is used for overriding.
     */
    protected function onOrderReset()
    {
    }
}
