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

namespace OxidEsales\EshopCommunity\Core\Exception;

/**
 * exception class for all kind of connection problems to external servers, e.g.:
 * - no connection, proxy problem, wrong configuration, etc.
 * - ipayment server
 * - online vat id check
 * - db server
 */
class ConnectionException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxConnectionException';

    /**
     * Address value
     *
     * @var string
     */
    private $_sAddress;

    /**
     * connection error as given by connect method
     *
     * @var string
     */
    private $_sConnectionError;

    /**
     * Enter address of the external server which caused the exception
     *
     * @param string $sAdress Externalserver address
     */
    public function setAdress($sAdress)
    {
        $this->_sAddress = $sAdress;
    }

    /**
     * Gives address of the external server which caused the exception
     *
     * @return string
     */
    public function getAdress()
    {
        return $this->_sAddress;
    }

    /**
     * Sets the connection error returned by the connect function
     *
     * @param string $sConnError connection error
     */
    public function setConnectionError($sConnError)
    {
        $this->_sConnectionError = $sConnError;
    }

    /**
     * Gives the connection error returned by the connect function
     *
     * @return string
     */
    public function getConnectionError()
    {
        return $this->_sConnectionError;
    }

    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function getString()
    {
        return __CLASS__ . '-' . parent::getString() . " Connection Adress --> " . $this->_sAddress . "\n" . "Connection Error --> " . $this->_sConnectionError;
    }

    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function getValues()
    {
        $aRes = parent::getValues();
        $aRes['adress'] = $this->getAdress();
        $aRes['connectionError'] = $this->getConnectionError();

        return $aRes;
    }
}
