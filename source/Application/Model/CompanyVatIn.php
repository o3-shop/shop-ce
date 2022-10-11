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

use oxStr;

/**
 * Company VAT identification number (VATIN)
 */
class CompanyVatIn
{
    /**
     * VAT identification number
     *
     * @var string
     */
    private $_sCompanyVatNumber;

    /**
     * Constructor
     *
     * @param string $sCompanyVatNumber - company vat identification number.
     */
    public function __construct($sCompanyVatNumber)
    {
        $this->_sCompanyVatNumber = $sCompanyVatNumber;
    }

    /**
     * Returns country code from number.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->strtoupper(\OxidEsales\Eshop\Core\Str::getStr()->substr($this->_cleanUp($this->_sCompanyVatNumber), 0, 2));
    }

    /**
     * Returns country code from number.
     *
     * @return string
     */
    public function getNumbers()
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->substr($this->_cleanUp($this->_sCompanyVatNumber), 2);
    }

    /**
     * Removes spaces and symbols: '-',',','.' from string
     *
     * @param string $sValue Value.
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "cleanUp" in next major
     */
    protected function _cleanUp($sValue) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->preg_replace("/\s|-/", '', $sValue);
    }


    /**
     * Cast to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_sCompanyVatNumber;
    }
}
