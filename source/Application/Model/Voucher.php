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

use Exception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Exception\VoucherException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use stdClass;

/**
 * Voucher manager.
 * Performs deletion, generating, assigning to group and other voucher
 * managing functions.
 *
 */
class Voucher extends BaseModel
{
    protected $_oSerie = null;

    /**
     * Vouchers does not need shop id check as this causes problems with
     * inherited vouchers. Voucher validity check is made by oxVoucher::getVoucherByNr()
     *
     * @var bool
     */
    protected $_blDisableShopCheck = true;

    /**
     * @var string Name of current class
     */
    protected $_sClassName = 'oxvoucher';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxvouchers');
    }

    /**
     * Gets voucher from db by given number.
     *
     * @param string $sVoucherNr Voucher number
     * @param array $aVouchers Array of available vouchers (default array())
     * @param bool $blCheckAvailability check if voucher is still reserved or not
     *
     * @return bool|null
     * @throws VoucherException exception
     * @throws DatabaseConnectionException
     */
    public function getVoucherByNr($sVoucherNr, $aVouchers = [], $blCheckAvailability = false)
    {
        $oRet = null;
        if (!empty($sVoucherNr)) {
            $sViewName = $this->getViewName();
            $sSeriesViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxvoucherseries');
            $oDb = DatabaseProvider::getMaster();

            $sQ = "select {$sViewName}.* from {$sViewName}, {$sSeriesViewName} where
                        {$sSeriesViewName}.oxid = {$sViewName}.oxvoucherserieid and
                        {$sViewName}.oxvouchernr = " . $oDb->quote($sVoucherNr) . " and ";

            if (is_array($aVouchers)) {
                foreach ($aVouchers as $sVoucherId => $sSkipVoucherNr) {
                    $sQ .= "{$sViewName}.oxid != " . $oDb->quote($sVoucherId) . " and ";
                }
            }
            $sQ .= "( {$sViewName}.oxorderid is NULL || {$sViewName}.oxorderid = '' ) ";
            $sQ .= " and ( {$sViewName}.oxdateused is NULL || {$sViewName}.oxdateused = 0 ) ";

            //voucher timeout for 3 hours
            if ($blCheckAvailability) {
                $iTime = time() - $this->_getVoucherTimeout();
                $sQ .= " and {$sViewName}.oxreserved < '{$iTime}' order by {$sViewName}.oxreserved asc ";
            }

            $sQ .= " limit 1 FOR UPDATE";

            if (!($oRet = $this->assignRecord($sQ))) {
                $oEx = oxNew(VoucherException::class);
                $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
                $oEx->setVoucherNr($sVoucherNr);
                throw $oEx;
            }
        }

        return $oRet;
    }

    /**
     * marks voucher as used
     *
     * @param string $sOrderId order id
     * @param string $sUserId user id
     * @param double $dDiscount used discount
     * @throws Exception
     */
    public function markAsUsed($sOrderId, $sUserId, $dDiscount)
    {
        //saving oxreserved field
        if ($this->oxvouchers__oxid->value) {
            $this->oxvouchers__oxorderid->setValue($sOrderId);
            $this->oxvouchers__oxuserid->setValue($sUserId);
            $this->oxvouchers__oxdiscount->setValue($dDiscount);
            $this->oxvouchers__oxdateused->setValue(date("Y-m-d", Registry::getUtilsDate()->getTime()));
            $this->save();
        }
    }

    /**
     * mark voucher as reserved
     */
    public function markAsReserved()
    {
        //saving oxreserved field
        $sVoucherID = $this->oxvouchers__oxid->value;

        if ($sVoucherID) {
            $oDb = DatabaseProvider::getMaster();
            $sQ = "update oxvouchers set oxreserved = :oxreserved where oxid = :oxid";
            $oDb->execute($sQ, [
                ':oxreserved' => time(),
                ':oxid' => $sVoucherID
            ]);
        }
    }

    /**
     * un mark as reserved
     */
    public function unMarkAsReserved()
    {
        //saving oxreserved field
        $sVoucherID = $this->oxvouchers__oxid->value;

        if ($sVoucherID) {
            $oDb = DatabaseProvider::getDb();
            $sQ = "update oxvouchers set oxreserved = 0 where oxid = :oxid";
            $oDb->execute($sQ, [':oxid' => $sVoucherID]);
        }
    }

    /**
     * Returns the discount value used.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     *
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     */
    public function getDiscountValue($dPrice)
    {
        if ($this->_isProductVoucher()) {
            return $this->_getProductDiscountValue((double) $dPrice);
        } elseif ($this->_isCategoryVoucher()) {
            return $this->_getCategoryDiscountValue((double) $dPrice);
        } else {
            return $this->_getGenericDiscountValue((double) $dPrice);
        }
    }

    // Checking General Availability

    /**
     * Checks availability without user logged in. Returns array with errors.
     *
     * @param array $aVouchers array of vouchers
     * @param double $dPrice current sum (price)
     *
     * @return bool
     *
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     */
    public function checkVoucherAvailability($aVouchers, $dPrice)
    {
        $this->_isAvailableWithSameSeries($aVouchers);
        $this->_isAvailableWithOtherSeries($aVouchers);
        $this->_isValidDate();
        $this->_isAvailablePrice($dPrice);
        $this->_isNotReserved();
        $this->isAvailable();

        // returning true - no exception was thrown
        return true;
    }

    /**
     * Performs basket level voucher availability check (no need to check if voucher
     * is reserved or so).
     *
     * @param array $aVouchers array of vouchers
     * @param double $dPrice current sum (price)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     */
    public function checkBasketVoucherAvailability($aVouchers, $dPrice)
    {
        $this->_isAvailableWithSameSeries($aVouchers);
        $this->_isAvailableWithOtherSeries($aVouchers);
        $this->_isValidDate();
        $this->_isAvailablePrice($dPrice);
        $this->isAvailable();

        // returning true - no exception was thrown
        return true;
    }

    protected function isAvailable()
    {
        if (empty($this->oxvouchers__oxorderid->value)) {
            return true;
        }

        $exception = oxNew(VoucherException::class);
        $exception->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
        $exception->setVoucherNr($this->oxvouchers__oxvouchernr->value);
        throw $exception;
    }

    /**
     * Checks availability about price. Returns error array.
     *
     * @param double $dPrice base article price
     *
     * @return bool
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAvailablePrice" in next major
     */
    protected function _isAvailablePrice($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oSeries = $this->getSerie();
        $oCur = Registry::getConfig()->getActShopCurrencyObject();
        if ($oSeries->oxvoucherseries__oxminimumvalue->value && $dPrice < ($oSeries->oxvoucherseries__oxminimumvalue->value * $oCur->rate)) {
            $oEx = oxNew(VoucherException::class);
            $oEx->setMessage('ERROR_MESSAGE_VOUCHER_INCORRECTPRICE');
            $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
            throw $oEx;
        }

        return true;
    }

    /**
     * Checks if calculation with vouchers of the same series possible. Returns
     * true on success.
     *
     * @param array $aVouchers array of vouchers
     *
     * @return bool
     *
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAvailableWithSameSeries" in next major
     */
    protected function _isAvailableWithSameSeries($aVouchers) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_array($aVouchers)) {
            $sId = $this->getId();
            if (isset($aVouchers[$sId])) {
                unset($aVouchers[$sId]);
            }
            $oSeries = $this->getSerie();
            if (!$oSeries->oxvoucherseries__oxallowsameseries->value) {
                foreach ($aVouchers as $voucherId => $voucherNr) {
                    $oVoucher = oxNew(Voucher::class);
                    $oVoucher->load($voucherId);
                    if ($this->oxvouchers__oxvoucherserieid->value == $oVoucher->oxvouchers__oxvoucherserieid->value) {
                        $oEx = oxNew(VoucherException::class);
                        $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOTALLOWEDSAMESERIES');
                        $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
                        throw $oEx;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Checks if calculation with vouchers from the other series possible.
     * Returns true on success.
     *
     * @param array $aVouchers array of vouchers
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAvailableWithOtherSeries" in next major
     */
    protected function _isAvailableWithOtherSeries($aVouchers) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_array($aVouchers) && count($aVouchers)) {
            $oSeries = $this->getSerie();
            $sIds = implode(',', DatabaseProvider::getDb()->quoteArray(array_keys($aVouchers)));
            $blAvailable = true;
            $oDb = DatabaseProvider::getDb();
            if (!$oSeries->oxvoucherseries__oxallowotherseries->value) {
                // just search for vouchers with different series
                $sSql = "select 1 from oxvouchers where oxvouchers.oxid in ($sIds) and ";
                $sSql .= "oxvouchers.oxvoucherserieid != :notoxvoucherserieid";
            } else {
                // search for vouchers with different series and those vouchers do not allow other series
                $sSql = "select 1 from oxvouchers left join oxvoucherseries on oxvouchers.oxvoucherserieid=oxvoucherseries.oxid ";
                $sSql .= "where oxvouchers.oxid in ($sIds) and oxvouchers.oxvoucherserieid != :notoxvoucherserieid ";
                $sSql .= "and not oxvoucherseries.oxallowotherseries";
            }
            $blAvailable &= !$oDb->getOne($sSql, [
                ':notoxvoucherserieid' => $this->oxvouchers__oxvoucherserieid->value
            ]);
            if (!$blAvailable) {
                $oEx = oxNew(VoucherException::class);
                $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOTALLOWEDOTHERSERIES');
                $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
                throw $oEx;
            }
        }

        return true;
    }

    /**
     * Checks if voucher is in valid time period. Returns true on success.
     *
     * @return bool
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isValidDate" in next major
     */
    protected function _isValidDate() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oSeries = $this->getSerie();
        $iTime = time();

        // If date is not set will add day before and day after to check if voucher valid today.
        $iTomorrow = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
        $iYesterday = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));

        // Checks if beginning date is set, if not set $iFrom to yesterday, so it will be valid.
        $iFrom = ((int) $oSeries->oxvoucherseries__oxbegindate->value) ?
            strtotime($oSeries->oxvoucherseries__oxbegindate->value) : $iYesterday;

        // Checks if end date is set, if no set $iTo to tomorrow, so it will be valid.
        $iTo = ((int) $oSeries->oxvoucherseries__oxenddate->value) ?
            strtotime($oSeries->oxvoucherseries__oxenddate->value) : $iTomorrow;

        if ($iFrom < $iTime && $iTo > $iTime) {
            return true;
        }

        $oEx = oxNew(VoucherException::class);
        $oEx->setMessage('MESSAGE_COUPON_EXPIRED');
        if ($iFrom > $iTime && $iTo > $iTime) {
            $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
        }
        $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
        throw $oEx;
    }

    /**
     * Checks if voucher is not yet reserved before.
     *
     * @throws VoucherException exception
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isNotReserved" in next major
     */
    protected function _isNotReserved() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->oxvouchers__oxreserved->value < time() - $this->_getVoucherTimeout()) {
            return true;
        }

        $oEx = oxNew(VoucherException::class);
        $oEx->setMessage('EXCEPTION_VOUCHER_ISRESERVED');
        $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
        throw $oEx;
    }

    // Checking User Availability

    /**
     * Checks availability for the given user. Returns array with errors.
     *
     * @param object $oUser user object
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     */
    public function checkUserAvailability($oUser)
    {
        $this->_isAvailableInOtherOrder($oUser);
        $this->_isValidUserGroup($oUser);

        // returning true if no exception was thrown
        return true;
    }

    /**
     * Checks if user already used vouchers from this series and can he use it again.
     *
     * @param object $oUser user object
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isAvailableInOtherOrder" in next major
     */
    protected function _isAvailableInOtherOrder($oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oSeries = $this->getSerie();
        if (!$oSeries->oxvoucherseries__oxallowuseanother->value) {
            $oDb = DatabaseProvider::getDb();
            $sSelect = 'select count(*) from ' . $this->getViewName() . ' 
                where oxuserid = :oxuserid and ';
            $sSelect .= 'oxvoucherserieid = :oxvoucherserieid and ';
            $sSelect .= '((oxorderid is not NULL and oxorderid != "") or (oxdateused is not NULL and oxdateused != 0)) ';

            $params = [
                ':oxuserid' => $oUser->oxuser__oxid->value,
                ':oxvoucherserieid' => $this->oxvouchers__oxvoucherserieid->value
            ];

            if ($oDb->getOne($sSelect, $params)) {
                $oEx = oxNew(VoucherException::class);
                $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOTALLOWEDSAMESERIES');
                $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
                throw $oEx;
            }
        }

        return true;
    }

    /**
     * Checks if user belongs to the same group as the voucher. Returns true on success.
     *
     * @param object $oUser user object
     *
     * @return bool
     * @throws ObjectException
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "isValidUserGroup" in next major
     */
    protected function _isValidUserGroup($oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oVoucherSeries = $this->getSerie();
        $oUserGroups = $oVoucherSeries->setUserGroups();

        if (!$oUserGroups->count()) {
            return true;
        }

        if ($oUser) {
            foreach ($oUserGroups as $oGroup) {
                if ($oUser->inGroup($oGroup->getId())) {
                    return true;
                }
            }
        }

        $oEx = oxNew(VoucherException::class);
        $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOTVALIDUSERGROUP');
        $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
        throw $oEx;
    }

    /**
     * Returns compact voucher object which is used in oxBasket
     *
     * @return stdClass
     */
    public function getSimpleVoucher()
    {
        $oVoucher = new stdClass();
        $oVoucher->sVoucherId = $this->getId();
        $oVoucher->sVoucherNr = null;
        if ($this->oxvouchers__oxvouchernr) {
            $oVoucher->sVoucherNr = $this->oxvouchers__oxvouchernr->value;
        }

        // R. set in oxBasket : $oVoucher->fVoucherdiscount = Registry::getLang()->formatCurrency( $this->oxvouchers__oxdiscount->value );

        return $oVoucher;
    }

    /**
     * create oxVoucherSerie object of this voucher
     *
     * @return VoucherSerie
     */
    public function getSerie()
    {
        if ($this->_oSerie !== null) {
            return $this->_oSerie;
        }
        $oSerie = oxNew(VoucherSerie::class);
        if (!$oSerie->load($this->oxvouchers__oxvoucherserieid->value)) {
            throw oxNew(ObjectException::class);
        }
        $this->_oSerie = $oSerie;

        return $oSerie;
    }

    /**
     * Returns true if voucher is product specific, otherwise false
     *
     * @return boolean
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "isProductVoucher" in next major
     */
    protected function _isProductVoucher() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $oSeries = $this->getSerie();
        $sSelect = "select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype";
        $blOk = (bool) $oDb->getOne($sSelect, [
            ':oxdiscountid' => $oSeries->getId(),
            ':oxtype' => 'oxarticles'
        ]);

        return $blOk;
    }

    /**
     * Returns true if voucher is category specific, otherwise false
     *
     * @return boolean
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "isCategoryVoucher" in next major
     */
    protected function _isCategoryVoucher() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $oSeries = $this->getSerie();
        $sSelect = "select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype";
        $blOk = (bool) $oDb->getOne($sSelect, [
            ':oxdiscountid' => $oSeries->getId(),
            ':oxtype' => 'oxcategories'
        ]);

        return $blOk;
    }

    /**
     * Returns the discount object created from voucher serie data
     *
     * @return object
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSerieDiscount" in next major
     */
    protected function _getSerieDiscount() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oSeries = $this->getSerie();
        $oDiscount = oxNew(Discount::class);

        $oDiscount->setId($oSeries->getId());
        $oDiscount->oxdiscount__oxshopid = new Field($oSeries->oxvoucherseries__oxshopid->value);
        $oDiscount->oxdiscount__oxactive = new Field(true);
        $oDiscount->oxdiscount__oxactivefrom = new Field($oSeries->oxvoucherseries__oxbegindate->value);
        $oDiscount->oxdiscount__oxactiveto = new Field($oSeries->oxvoucherseries__oxenddate->value);
        $oDiscount->oxdiscount__oxtitle = new Field($oSeries->oxvoucherseries__oxserienr->value);
        $oDiscount->oxdiscount__oxamount = new Field(1);
        $oDiscount->oxdiscount__oxamountto = new Field(MAX_64BIT_INTEGER);
        $oDiscount->oxdiscount__oxprice = new Field(0);
        $oDiscount->oxdiscount__oxpriceto = new Field(MAX_64BIT_INTEGER);
        $oDiscount->oxdiscount__oxaddsumtype = new Field($oSeries->oxvoucherseries__oxdiscounttype->value == 'percent' ? '%' : 'abs');
        $oDiscount->oxdiscount__oxaddsum = new Field($oSeries->oxvoucherseries__oxdiscount->value);
        $oDiscount->oxdiscount__oxitmartid = new Field();
        $oDiscount->oxdiscount__oxitmamount = new Field();
        $oDiscount->oxdiscount__oxitmmultiple = new Field();

        return $oDiscount;
    }

    /**
     * Returns basket item information array from session or order.
     *
     * @param null $oDiscount discount object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getBasketItems" in next major
     */
    protected function _getBasketItems($oDiscount = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->oxvouchers__oxorderid->value) {
            return $this->_getOrderBasketItems($oDiscount);
        } elseif (Registry::getSession()->getBasket()) {
            return $this->_getSessionBasketItems($oDiscount);
        } else {
            return [];
        }
    }

    /**
     * Returns basket item information (id,amount,price) array taking item list from order.
     *
     * @param null $oDiscount discount object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getOrderBasketItems" in next major
     */
    protected function _getOrderBasketItems($oDiscount = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_null($oDiscount)) {
            $oDiscount = $this->_getSerieDiscount();
        }

        $oOrder = oxNew(Order::class);
        $oOrder->load($this->oxvouchers__oxorderid->value);

        $aItems = [];
        $iCount = 0;

        foreach ($oOrder->getOrderArticles(true) as $oOrderArticle) {
            if (!$oOrderArticle->skipDiscounts() && $oDiscount->isForBasketItem($oOrderArticle)) {
                $aItems[$iCount] = [
                    'oxid'     => $oOrderArticle->getProductId(),
                    'price'    => $oOrderArticle->oxorderarticles__oxbprice->value,
                    'discount' => $oDiscount->getAbsValue($oOrderArticle->oxorderarticles__oxbprice->value),
                    'amount'   => $oOrderArticle->oxorderarticles__oxamount->value,
                ];
                $iCount++;
            }
        }

        return $aItems;
    }

    /**
     * Returns basket item information (id,amount,price) array taking item list from session.
     *
     * @param null $oDiscount discount object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSessionBasketItems" in next major
     */
    protected function _getSessionBasketItems($oDiscount = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_null($oDiscount)) {
            $oDiscount = $this->_getSerieDiscount();
        }

        $oBasket = Registry::getSession()->getBasket();
        $aItems = [];
        $iCount = 0;

        foreach ($oBasket->getContents() as $oBasketItem) {
            if (!$oBasketItem->isDiscountArticle() && ($oArticle = $oBasketItem->getArticle()) && !$oArticle->skipDiscounts() && $oDiscount->isForBasketItem($oArticle)) {
                $aItems[$iCount] = [
                    'oxid'     => $oArticle->getId(),
                    'price'    => $oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $oBasket)->getPrice(),
                    'discount' => $oDiscount->getAbsValue($oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $oBasket)->getPrice()),
                    'amount'   => $oBasketItem->getAmount(),
                ];

                $iCount++;
            }
        }

        return $aItems;
    }

    /**
     * Returns the discount value used.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @throws ObjectException
     * @deprecated on b-dev (2015-03-31); Use function _getGenericDiscountValue()
     *
     */
    protected function _getGenericDiscoutValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getGenericDiscountValue($dPrice);
    }

    /**
     * Returns the discount value used.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "getGenericDiscountValue" in next major
     */
    protected function _getGenericDiscountValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oSeries = $this->getSerie();
        if ($oSeries->oxvoucherseries__oxdiscounttype->value == 'absolute') {
            $oCur = Registry::getConfig()->getActShopCurrencyObject();
            $dDiscount = $oSeries->oxvoucherseries__oxdiscount->value * $oCur->rate;
        } else {
            $dDiscount = $oSeries->oxvoucherseries__oxdiscount->value / 100 * $dPrice;
        }

        if ($dDiscount > $dPrice) {
            $dDiscount = $dPrice;
        }

        return $dDiscount;
    }


    /**
     * Return discount value
     *
     * @return double
     */
    public function getDiscount()
    {
        $oSeries = $this->getSerie();

        return $oSeries->oxvoucherseries__oxdiscount->value;
    }

    /**
     * Return discount type
     *
     * @return string
     */
    public function getDiscountType()
    {
        $oSeries = $this->getSerie();

        return $oSeries->oxvoucherseries__oxdiscounttype->value;
    }

    /**
     * Returns the discount value used, if voucher is applied only for specific products.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     * @deprecated on b-dev (2015-03-31); Use function _getProductDiscountValue()
     */
    protected function _getProductDiscoutValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getProductDiscountValue($dPrice);
    }

    /**
     * Returns the discount value used, if voucher is applied only for specific products.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductDiscountValue" in next major
     */
    protected function _getProductDiscountValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDiscount = $this->_getSerieDiscount();
        $aBasketItems = $this->_getBasketItems($oDiscount);

        // Basket Item Count and isAdmin check (unable to access property $oOrder->_getOrderBasket()->_blSkipVouchersAvailabilityChecking)
        if (!count($aBasketItems) && !$this->isAdmin()) {
            $oEx = oxNew(VoucherException::class);
            $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
            $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
            throw $oEx;
        }

        $oSeries = $this->getSerie();

        $oVoucherPrice = oxNew(Price::class);
        $oDiscountPrice = oxNew(Price::class);
        $oProductPrice = oxNew(Price::class);
        $oProductTotal = oxNew(Price::class);

        // Is the voucher discount applied to at least one basket item
        $blDiscountApplied = false;

        foreach ($aBasketItems as $aBasketItem) {
            // If discount was already applied for the voucher to at least one basket items, then break
            if ($blDiscountApplied and !empty($oSeries->oxvoucherseries__oxcalculateonce->value)) {
                break;
            }

            $oDiscountPrice->setPrice($aBasketItem['discount']);
            $oProductPrice->setPrice($aBasketItem['price']);

            // Individual voucher is not multiplied by article amount
            if (!$oSeries->oxvoucherseries__oxcalculateonce->value) {
                $oDiscountPrice->multiply($aBasketItem['amount']);
                $oProductPrice->multiply($aBasketItem['amount']);
            }

            $oVoucherPrice->add($oDiscountPrice->getBruttoPrice());
            $oProductTotal->add($oProductPrice->getBruttoPrice());

            if (!empty($aBasketItem['discount'])) {
                $blDiscountApplied = true;
            }
        }

        $dVoucher = $oVoucherPrice->getBruttoPrice();
        $dProduct = $oProductTotal->getBruttoPrice();

        if ($dVoucher > $dProduct) {
            return $dProduct;
        }

        return $dVoucher;
    }

    /**
     * Returns the discount value used, if voucher is applied only for specific categories.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     * @deprecated on b-dev (2015-03-31); Use function _getCategoryDiscountValue()
     */
    protected function _getCategoryDiscoutValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getCategoryDiscountValue($dPrice);
    }

    /**
     * Returns the discount value used, if voucher is applied only for specific categories.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @return double
     * @throws DatabaseConnectionException
     * @throws ObjectException
     * @throws VoucherException exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCategoryDiscountValue" in next major
     */
    protected function _getCategoryDiscountValue($dPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDiscount = $this->_getSerieDiscount();
        $aBasketItems = $this->_getBasketItems($oDiscount);

        // Basket Item Count and isAdmin check (unable to access property $oOrder->_getOrderBasket()->_blSkipVouchersAvailabilityChecking)
        if (!count($aBasketItems) && !$this->isAdmin()) {
            $oEx = oxNew(VoucherException::class);
            $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
            $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
            throw $oEx;
        }

        $oProductPrice = oxNew(Price::class);
        $oProductTotal = oxNew(Price::class);

        foreach ($aBasketItems as $aBasketItem) {
            $oProductPrice->setPrice($aBasketItem['price']);
            $oProductPrice->multiply($aBasketItem['amount']);
            $oProductTotal->add($oProductPrice->getBruttoPrice());
        }

        $dProduct = $oProductTotal->getBruttoPrice();
        $dVoucher = $oDiscount->getAbsValue($dProduct);

        return ($dVoucher > $dProduct) ? $dProduct : $dVoucher;
    }

    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName name of variable to get
     *
     * @return string
     */
    public function __get($sName)
    {
        switch ($sName) {
            // simple voucher mapping
            case 'sVoucherId':
                return $this->getId();
            case 'sVoucherNr':
                return $this->oxvouchers__oxvouchernr;
            case 'fVoucherdiscount':
                return $this->oxvouchers__oxdiscount;
        }
        return parent::__get($sName);
    }

    /**
     * Returns a configured value for voucher timeouts or a default
     * of 3 hours if not configured
     *
     * @return integer Seconds a voucher can stay in status reserved
     * @deprecated underscore prefix violates PSR12, will be renamed to "getVoucherTimeout" in next major
     */
    protected function _getVoucherTimeout() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iVoucherTimeout = (int) Registry::getConfig()->getConfigParam('iVoucherTimeout') ?:
            3 * 3600;

        return $iVoucherTimeout;
    }
}
