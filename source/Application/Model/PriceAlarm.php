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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * PriceAlarm manager.
 * Performs PriceAlarm data/objects loading, deleting.
 *
 */
class PriceAlarm extends BaseModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxpricealarm';

    /**
     * Article object
     *
     * @var object
     */
    protected $_oArticle = null;

    /**
     * Formatted original article price
     *
     * @var string
     */
    protected $_fPrice = null;

    /**
     * Original article price
     *
     * @var double
     */
    protected $_dPrice = null;

    /**
     * Full article title
     *
     * @var string
     */
    protected $_sTitle = null;

    /**
     * Currency object
     *
     * @var object
     */
    protected $_oCurrency = null;

    /**
     * Customer proposed price
     *
     * @var string
     */
    protected $_fProposedPrice = null;

    /**
     * PriceAlarm status
     *
     * @var int
     */
    protected $_iStatus = null;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()), loads
     * base shop objects.
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxpricealarm');
    }

    /**
     * Inserts object data into DB, returns true on success.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // set oxinsert value
        $this->oxpricealarm__oxinsert = new Field(date('Y-m-d', Registry::getUtilsDate()->getTime()));

        return parent::_insert();
    }

    /**
     * Loads pricealarm article
     *
     * @return object
     * @throws DatabaseConnectionException
     */
    public function getArticle()
    {
        if ($this->_oArticle == null) {
            $this->_oArticle = false;
            $oArticle = oxNew(Article::class);
            if ($oArticle->load($this->oxpricealarm__oxartid->value)) {
                $this->_oArticle = $oArticle;
            }
        }

        return $this->_oArticle;
    }

    /**
     * Returns formatted pricealarm article original price
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getFPrice()
    {
        if ($this->_fPrice == null) {
            $this->_fPrice = false;
            if ($dArtPrice = $this->getPrice()) {
                $myLang = Registry::getLang();
                $oThisCurr = $this->getPriceAlarmCurrency();
                $this->_fPrice = $myLang->formatCurrency($dArtPrice, $oThisCurr);
            }
        }

        return $this->_fPrice;
    }

    /**
     * Returns pricealarm article original price
     *
     * @return double
     * @throws DatabaseConnectionException
     */
    public function getPrice()
    {
        if ($this->_dPrice == null) {
            $this->_dPrice = false;
            if ($oArticle = $this->getArticle()) {
                $myUtils = Registry::getUtils();
                $oThisCurr = $this->getPriceAlarmCurrency();

                // #889C - Netto prices in Admin
                // (we have to call $oArticle->getPrice() to get price with VAT)
                $dArtPrice = $oArticle->getPrice()->getBruttoPrice() * $oThisCurr->rate;
                $dArtPrice = $myUtils->fRound($dArtPrice);

                $this->_dPrice = $dArtPrice;
            }
        }

        return $this->_dPrice;
    }

    /**
     * Returns pricealarm article full title
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getTitle()
    {
        if ($this->_sTitle == null) {
            $this->_sTitle = false;
            if ($oArticle = $this->getArticle()) {
                $this->_sTitle = $oArticle->oxarticles__oxtitle->value;
                if ($oArticle->oxarticles__oxparentid->value && !$oArticle->oxarticles__oxtitle->value) {
                    $oParent = oxNew(Article::class);
                    $oParent->load($oArticle->oxarticles__oxparentid->value);
                    $this->_sTitle = $oParent->oxarticles__oxtitle->value . " " . $oArticle->oxarticles__oxvarselect->value;
                }
            }
        }

        return $this->_sTitle;
    }

    /**
     * Returns pricealarm currency object
     *
     * @return object
     */
    public function getPriceAlarmCurrency()
    {
        if ($this->_oCurrency == null) {
            $this->_oCurrency = false;
            $myConfig = Registry::getConfig();
            $oThisCurr = $myConfig->getCurrencyObject($this->oxpricealarm__oxcurrency->value);

            // #869A we should perform currency conversion
            // (older versions doesn't have currency info - assume as it is default - first in currency array)
            if (!$oThisCurr) {
                $oDefCurr = $myConfig->getActShopCurrencyObject();
                $oThisCurr = $myConfig->getCurrencyObject($oDefCurr->name);
                $this->oxpricealarm__oxcurrency->setValue($oDefCurr->name);
            }
            $this->_oCurrency = $oThisCurr;
        }

        return $this->_oCurrency;
    }

    /**
     * Returns formatted proposed price
     *
     * @return string
     */
    public function getFProposedPrice()
    {
        if ($this->_fProposedPrice == null) {
            $this->_fProposedPrice = false;
            if ($oThisCurr = $this->getPriceAlarmCurrency()) {
                $myLang = Registry::getLang();
                $this->_fProposedPrice = $myLang->formatCurrency($this->oxpricealarm__oxprice->value, $oThisCurr);
            }
        }

        return $this->_fProposedPrice;
    }

    /**
     * Returns pricealarm status
     *
     * @return integer
     * @throws DatabaseConnectionException
     */
    public function getPriceAlarmStatus()
    {
        if ($this->_iStatus == null) {
            // neutral status
            $this->_iStatus = 0;

            // shop price is less or equal
            $dArtPrice = $this->getPrice();
            if ($this->oxpricealarm__oxprice->value >= $dArtPrice) {
                $this->_iStatus = 1;
            }

            // suggestion to user is sent
            if ($this->oxpricealarm__oxsended->value != "0000-00-00 00:00:00") {
                $this->_iStatus = 2;
            }
        }

        return $this->_iStatus;
    }
}
