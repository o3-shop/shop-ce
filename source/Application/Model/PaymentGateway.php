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

/**
 * Payment gateway manager.
 * Checks and sets payment method data, executes payment.
 *
 */
class PaymentGateway extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Payment status (active - true/not active - false) (default false).
     *
     * @var bool
     */
    protected $_blActive = false;

    /**
     * oUserpayment object (default null).
     *
     * @var object
     */
    protected $_oPaymentInfo = null;

    /**
     * Last error nr. For backward compatibility must be >3
     *
     * @abstract
     * @var string
     */
    protected $_iLastErrorNo = 4;

    /**
     * Last error text.
     *
     * @abstract
     * @var string
     */
    protected $_sLastError = null;

    /**
     * Sets payment parameters.
     *
     * @param object $oUserpayment User payment object
     */
    public function setPaymentParams($oUserpayment)
    {
        // store data
        $this->_oPaymentInfo = & $oUserpayment;
    }

    /**
     * Executes payment, returns true on success.
     *
     * @param double $dAmount Goods amount
     * @param object $oOrder  User ordering object
     *
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder)
    {
        $this->_iLastErrorNo = null;
        $this->_sLastError = null;

        if (!$this->_isActive()) {
            return true; // fake yes
        }

        // proceed with no payment
        // used for other countries
        if (@$this->_oPaymentInfo->oxuserpayments__oxpaymentsid->value == 'oxempty') {
            return true;
        }

        return false;
    }

    /**
     * Returns last payment processing error nr.
     *
     * @return int
     */
    public function getLastErrorNo()
    {
        return $this->_iLastErrorNo;
    }

    /**
     * Returns last payment processing error.
     *
     * @return int
     */
    public function getLastError()
    {
        return $this->_sLastError;
    }

    /**
     * Returns true is payment active.
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isActive" in next major
     */
    protected function _isActive() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_blActive;
    }
}
