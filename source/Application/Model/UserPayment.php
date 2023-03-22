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

use oxRegistry;
use oxDb;

/**
 * User payment manager.
 * Performs assigning, loading, inserting and updating functions for
 * user payment.
 *
 */
class UserPayment extends \OxidEsales\Eshop\Core\Model\BaseModel
{
    // you can change this if you want more security
    // DO NOT !! CHANGE THIS FILE AND STORE CREDIT CARD INFORMATION
    // THIS IS MORE THAN LIKELY ILLEGAL !!
    // CHECK YOUR CREDIT CARD CONTRACT

    /**
     * Payment information encryption key
     *
     * @deprecated since v6.5.1 (2020-02-07); Database encoding was completely removed and property is not used anymore.
     *
     * @var string.
     */
    protected $_sPaymentKey = 'fq45QS09_fqyx09239QQ';

    /**
     * Name of current class
     *
     * @var string
     */
    protected $_sClassName = 'oxuserpayment';

    /**
     * Store credit card information in db or not
     *
     * @deprecated since v6.5.1 (2019-02-07); credit card payment method will be no longer supported
     *
     * @var bool
     */
    protected $_blStoreCreditCardInfo = null;

    /**
     * Payment info object
     *
     * @var oxpayment
     */
    protected $_oPayment = null;

    /**
     * current dyn values
     *
     * @var array
     */
    protected $_aDynValues = null;

    /**
     * Special getter for oxpayments__oxdesc field
     *
     * @param string $sName name of field
     *
     * @return string
     */
    public function __get($sName)
    {
        //due to compatibility with templates
        if ($sName == 'oxpayments__oxdesc') {
            if ($this->_oPayment === null) {
                $this->_oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
                $this->_oPayment->load($this->oxuserpayments__oxpaymentsid->value);
            }

            return $this->_oPayment->oxpayments__oxdesc;
        }

        if ($sName == 'aDynValues') {
            if ($this->_aDynValues === null) {
                $this->_aDynValues = $this->getDynValues();
            }

            return $this->_aDynValues;
        }

        return parent::__get($sName);
    }

    /**
     * Class constructor. Sets payment key for encoding sensitive data and
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxuserpayments');
        $this->_sPaymentKey = \OxidEsales\Eshop\Core\Registry::getUtils()->strRot13($this->_sPaymentKey);
        $this->setStoreCreditCardInfo($this->getConfig()->getConfigParam('blStoreCreditCardInfo'));
    }

    /**
     * Returns payment key used for DB value description
     *
     * @deprecated since v6.5.1 (2020-02-07); Database encoding was completely removed and method is not used anymore.
     *
     * @return string
     */
    public function getPaymentKey()
    {
        return $this->_sPaymentKey;
    }

    /**
     * Loads user payment object
     *
     * @param string $sOxId oxuserpayment id
     *
     * @return mixed
     */
    public function load($sOxId)
    {
        $sSelect = 'select oxid, oxuserid, oxpaymentsid, oxvalue
                    from oxuserpayments where oxid = ' . \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($sOxId);

        return $this->assignRecord($sSelect);
    }


    /**
     * Inserts payment information to DB. Returns insert status.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "insert" in next major
     */
    protected function _insert() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // @deprecated since v6.5.1 (2019-02-07); credit card payment method will be no longer supported
        // we do not store credit card information
        // check and in case skip it
        if (!$this->getStoreCreditCardInfo() && $this->oxuserpayments__oxpaymentsid->value == 'oxidcreditcard') {
            return true;
        }
        // END deprecated

        //encode sensitive data
        $sEncodedValue = '';
        if ($sValue = $this->oxuserpayments__oxvalue->value) {
            // Function is called from inside a transaction in Category::save (see ESDEV-3804 and ESDEV-3822).
            // No need to explicitly force master here.
            $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sEncodedValue = $database->getOne("select encode( " . $database->quote($sValue) . ", '" . $this->getPaymentKey() . "' )");
            $this->oxuserpayments__oxvalue->setValue($sEncodedValue);
        }

        $blRet = parent::_insert();

        //restore, as encoding was needed only for saving
        if ($sEncodedValue) {
            $this->oxuserpayments__oxvalue->setValue($sValue);
        }

        return $blRet;
    }

    /**
     * Updates payment record in DB. Returns update status.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "update" in next major
     */
    protected function _update() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {

        //encode sensitive data
        if ($sValue = $this->oxuserpayments__oxvalue->value) {
            // Function is called from inside a transaction in Category::save (see ESDEV-3804 and ESDEV-3822).
            // No need to explicitly force master here.
            $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

            $sEncodedValue = $database->getOne("select encode( " . $database->quote($sValue) . ", '" . $this->getPaymentKey() . "' )");
            $this->oxuserpayments__oxvalue->setValue($sEncodedValue);
        }

        $blRet = parent::_update();

        //restore, as encoding was needed only for saving
        if ($sEncodedValue) {
            $this->oxuserpayments__oxvalue->setValue($sValue);
        }

        return $blRet;
    }

    /**
     * Set store or not credit card information in db
     *
     * @param bool $blStoreCreditCardInfo store or not credit card info
     *
     * @deprecated since v6.5.1 (2019-02-07); credit card payment method will be no longer supported
     */
    public function setStoreCreditCardInfo($blStoreCreditCardInfo)
    {
        $this->_blStoreCreditCardInfo = $blStoreCreditCardInfo;
    }

    /**
     * Get store or not credit card information in db parameter
     *
     * @deprecated since v6.5.1 (2019-02-07); credit card payment method will be no longer supported
     *
     * @return bool
     */
    public function getStoreCreditCardInfo()
    {
        return $this->_blStoreCreditCardInfo;
    }

    /**
     * Get user payment by payment id
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser        user object
     * @param string                                   $sPaymentType payment type
     *
     * @return bool
     */
    public function getPaymentByPaymentType($oUser = null, $sPaymentType = null)
    {
        $blGet = false;
        if ($oUser && $sPaymentType != null) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sQ = 'select oxpaymentid from oxorder where oxpaymenttype = :oxpaymenttype and
                    oxuserid = :oxuserid order by oxorderdate desc';
            $params = [
                ':oxpaymenttype' => $sPaymentType,
                ':oxuserid' => $oUser->getId()
            ];

            if (($sOxId = $oDb->getOne($sQ, $params))) {
                $blGet = $this->load($sOxId);
            }
        }

        return $blGet;
    }

    /**
     * Returns an array of dyn payment values
     *
     * @return array
     */
    public function getDynValues()
    {
        if (!$this->getStoreCreditCardInfo() && $this->oxuserpayments__oxpaymentsid->value == 'oxidcreditcard') {
            return null;
        }

        if (!$this->_aDynValues) {
            $sRawDynValue = null;
            if (is_object($this->oxuserpayments__oxvalue)) {
                $sRawDynValue = $this->oxuserpayments__oxvalue->getRawValue();
            }

            $this->_aDynValues = \OxidEsales\Eshop\Core\Registry::getUtils()->assignValuesFromText($sRawDynValue);
        }

        return $this->_aDynValues;
    }

    /**
     * sets the dyn values
     *
     * @param array $aDynValues the array of dy values
     */
    public function setDynValues($aDynValues)
    {
        $this->_aDynValues = $aDynValues;
    }
}
