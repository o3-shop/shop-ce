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

use oxDb;

/**
 * Country manager
 *
 */
class Country extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxcountry';

    /**
     * State list
     *
     * @var oxStateList
     */
    protected $_aStates = null;

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxcountry');
    }

    /**
     * returns true if this country is a foreign country
     *
     * @return bool
     */
    public function isForeignCountry()
    {
        return !in_array($this->getId(), $this->getConfig()->getConfigParam('aHomeCountry'));
    }

    /**
     * returns true if this country is marked as EU
     *
     * @return bool
     */
    public function isInEU()
    {
        return (bool) ($this->oxcountry__oxvatstatus->value == 1);
    }

    /**
     * Returns current state list
     *
     * @return array
     */
    public function getStates()
    {
        if (!is_null($this->_aStates)) {
            return $this->_aStates;
        }

        $sCountryId = $this->getId();
        $sViewName = getViewName("oxstates", $this->getLanguage());
        $sQ = "select * from {$sViewName} where `oxcountryid` = :oxcountryid order by `oxtitle`  ";
        $this->_aStates = oxNew(\OxidEsales\Eshop\Core\Model\ListModel::class);
        $this->_aStates->init("oxstate");
        $this->_aStates->selectString($sQ, [
            ':oxcountryid' => $sCountryId
        ]);

        return $this->_aStates;
    }

    /**
     * Returns country id by code
     *
     * @param string $sCode country code
     *
     * @return string
     */
    public function getIdByCode($sCode)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        return $oDb->getOne("select oxid from oxcountry where oxisoalpha2 = :oxisoalpha2", [
            ':oxisoalpha2' => $sCode
        ]);
    }

    /**
     * Method returns VAT identification number prefix.
     *
     * @return string
     */
    public function getVATIdentificationNumberPrefix()
    {
        return $this->oxcountry__oxvatinprefix->value;
    }
}
