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

/**
 * Defines and returns delivery and billing required fields.
 */
class RequiredAddressFields
{
    /**
     * Default required fields for use when not set in config.
     *
     * @var array
     */
    private $_aDefaultRequiredFields = [
        'oxuser__oxfname',
        'oxuser__oxlname',
        'oxuser__oxstreetnr',
        'oxuser__oxstreet',
        'oxuser__oxzip',
        'oxuser__oxcity'
    ];

    /**
     * Required fields.
     *
     * @var array
     */
    private $_aRequiredFields = [];

    /**
     * Sets default required fields either from config or from _aDefaultRequiredFields.
     *
     */
    public function __construct()
    {
        $this->setRequiredFields($this->_aDefaultRequiredFields);

        $aRequiredFields = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('aMustFillFields');
        if (is_array($aRequiredFields)) {
            $this->setRequiredFields($aRequiredFields);
        }
    }

    /**
     * Sets all required fields.
     *
     * @param array $aRequiredFields
     */
    public function setRequiredFields($aRequiredFields)
    {
        $this->_aRequiredFields = $aRequiredFields;
    }

    /**
     * Returns all required fields.
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->_aRequiredFields;
    }

    /**
     * Returns required fields for user address validation.
     *
     * @return mixed
     */
    public function getBillingFields()
    {
        $aRequiredFields = $this->getRequiredFields();

        return $this->_filterFields($aRequiredFields, 'oxuser__');
    }

    /**
     * Returns required fields for delivery address validation.
     *
     * @return mixed
     */
    public function getDeliveryFields()
    {
        $aRequiredFields = $this->getRequiredFields();

        return $this->_filterFields($aRequiredFields, 'oxaddress__');
    }

    /**
     * Removes delivery fields from fields list.
     *
     * @param array  $aFields
     * @param string $sPrefix
     *
     * @return mixed
     */
    private function _filterFields($aFields, $sPrefix) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aAllowed = [];
        foreach ($aFields as $sKey => $sValue) {
            if (strpos($sValue, $sPrefix) === 0) {
                $aAllowed[] = $aFields[$sKey];
            }
        }

        return $aAllowed;
    }
}
