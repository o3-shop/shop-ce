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

use oxField;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\MailValidator;
use OxidEsales\Eshop\Core\Registry;
use oxRegistry;

/**
 * PriceAlarm window.
 * Arranges "pricealarm" window, by sending eMail and storing into Database (etc.)
 * submission. Result - "pricealarm.tpl"  template. After user correctly
 * fulfils all required fields all information is sent to shop owner by
 * email.
 * O3-Shop -> pricealarm.
 */
class PriceAlarmController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'pricealarm.tpl';

    /**
     * Current article.
     *
     * @var object
     */
    protected $_oArticle = null;

    /**
     * Bid price.
     *
     * @var string
     */
    protected $_sBidPrice = null;

    /**
     * Price alarm status.
     *
     * @var integer
     */
    protected $_iPriceAlarmStatus = null;

    /**
     * Validates email
     * address. If email is wrong - returns false and exits. If email
     * address is OK - creates prcealarm object and saves it
     * (oxpricealarm::save()). Sends pricealarm notification mail
     * to shop owner.
     *
     * @return  bool    false on error
     */
    public function addme()
    {
        $myConfig = $this->getConfig();
        $myUtils = Registry::getUtils();

        $aParams = Registry::getConfig()->getRequestParameter('pa');
        if (!isset($aParams['email']) || !oxNew(MailValidator::class)->isValidEmail($aParams['email'])) {
            $this->_iPriceAlarmStatus = 0;

            return;
        }

        $oCur = $myConfig->getActShopCurrencyObject();
        // convert currency to default
        $dPrice = $myUtils->currency2Float($aParams['price']);

        $oAlarm = oxNew(\OxidEsales\Eshop\Application\Model\PriceAlarm::class);
        $oAlarm->oxpricealarm__oxuserid = new Field(Registry::getSession()->getVariable('usr'));
        $oAlarm->oxpricealarm__oxemail = new Field($aParams['email']);
        $oAlarm->oxpricealarm__oxartid = new Field($aParams['aid']);
        $oAlarm->oxpricealarm__oxprice = new Field($myUtils->fRound($dPrice, $oCur));
        $oAlarm->oxpricealarm__oxshopid = new Field($myConfig->getShopId());
        $oAlarm->oxpricealarm__oxcurrency = new Field($oCur->name);

        $oAlarm->oxpricealarm__oxlang = new Field(Registry::getLang()->getBaseLanguage());

        $oAlarm->save();

        // Send Email
        $oEmail = oxNew(\OxidEsales\Eshop\Core\Email::class);
        $this->_iPriceAlarmStatus = (int) $oEmail->sendPricealarmNotification($aParams, $oAlarm);
    }

    /**
     * Template variable getter. Returns bid price
     *
     * @return string
     */
    public function getBidPrice()
    {
        if ($this->_sBidPrice === null) {
            $this->_sBidPrice = false;

            $aParams = $this->_getParams();
            $oCur = $this->getConfig()->getActShopCurrencyObject();
            $iPrice = Registry::getUtils()->currency2Float($aParams['price']);
            $this->_sBidPrice = Registry::getLang()->formatCurrency($iPrice, $oCur);
        }

        return $this->_sBidPrice;
    }

    /**
     * Template variable getter. Returns active article
     *
     * @return object
     */
    public function getProduct()
    {
        if ($this->_oArticle === null) {
            $this->_oArticle = false;
            $aParams = $this->_getParams();
            $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
            $oArticle->load($aParams['aid']);
            $this->_oArticle = $oArticle;
        }

        return $this->_oArticle;
    }

    /**
     * Returns params (article id, bid price)
     *
     * @return array
     */
    private function _getParams() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::getConfig()->getRequestParameter('pa');
    }

    /**
     * Return pricealarm status (if it was send)
     *
     * @return integer
     */
    public function getPriceAlarmStatus()
    {
        return $this->_iPriceAlarmStatus;
    }
}
