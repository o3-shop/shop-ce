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
use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 * Address handler
 */
class Address extends BaseModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxaddress';

    /**
     * Active address status
     *
     * @var bool
     */
    protected $_blSelected = false;

    /**
     * @var State
     */
    protected $_oStateObject = null;

    /**
     * Returns oxState object
     *
     * @return State
     * @deprecated underscore prefix violates PSR12, will be renamed to "getStateObject" in next major
     */
    protected function _getStateObject() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (is_null($this->_oStateObject)) {
            $this->_oStateObject = oxNew(State::class);
        }

        return $this->_oStateObject;
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxaddress');
    }

    /**
     * Magic getter returns address as a single line string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Formats address as a single line string
     *
     * @return string
     */
    public function toString()
    {
        $sFirstName = $this->oxaddress__oxfname->value;
        $sLastName = $this->oxaddress__oxlname->value;
        $sStreet = $this->oxaddress__oxstreet->value;
        $sStreetNr = $this->oxaddress__oxstreetnr->value;
        $sCity = $this->oxaddress__oxcity->value;

        //format it
        $sAddress = "";
        if ($sFirstName || $sLastName) {
            $sAddress = $sFirstName . ($sFirstName ? " " : "") . "$sLastName, ";
        }
        $sAddress .= "$sStreet $sStreetNr, $sCity";

        return trim($sAddress);
    }

    /**
     * Returns encoded address.
     *
     * @return string
     */
    public function getEncodedDeliveryAddress()
    {
        return md5($this->_getMergedAddressFields());
    }

    /**
     * Get state id for current address
     *
     * @return mixed
     */
    public function getStateId()
    {
        return $this->oxaddress__oxstateid->value;
    }


    /**
     * Get state title
     *
     * @param null $sId state ID
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getStateTitle($sId = null)
    {
        $oState = $this->_getStateObject();

        if (is_null($sId)) {
            $sId = $this->getStateId();
        }

        return $oState->getTitleById($sId);
    }

    /**
     * Returns TRUE if current address is selected
     *
     * @return bool
     */
    public function isSelected()
    {
        return $this->_blSelected;
    }

    /**
     * Sets address state as selected
     */
    public function setSelected()
    {
        $this->_blSelected = true;
    }

    /**
     * Returns merged address fields.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMergedAddressFields" in next major
     */
    protected function _getMergedAddressFields() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sDelAddress = '';
        $sDelAddress .= $this->oxaddress__oxcompany;
        $sDelAddress .= $this->oxaddress__oxfname;
        $sDelAddress .= $this->oxaddress__oxlname;
        $sDelAddress .= $this->oxaddress__oxstreet;
        $sDelAddress .= $this->oxaddress__oxstreetnr;
        $sDelAddress .= $this->oxaddress__oxaddinfo;
        $sDelAddress .= $this->oxaddress__oxcity;
        $sDelAddress .= $this->oxaddress__oxcountryid;
        $sDelAddress .= $this->oxaddress__oxstateid;
        $sDelAddress .= $this->oxaddress__oxzip;
        $sDelAddress .= $this->oxaddress__oxfon;
        $sDelAddress .= $this->oxaddress__oxfax;
        $sDelAddress .= $this->oxaddress__oxsal;

        return $sDelAddress;
    }
}
