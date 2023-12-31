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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Company VAT identification number checker. Check if number belongs to the country.
 */
class CompanyVatInCountryChecker extends \OxidEsales\Eshop\Core\CompanyVatInChecker implements \OxidEsales\Eshop\Core\Contract\ICountryAware
{
    /**
     * Error string if country mismatch
     */
    const ERROR_ID_NOT_VALID = 'ID_NOT_VALID';

    /**
     * Country
     *
     * @var oxCountry
     */
    private $_oCountry = null;

    /**
     * Country setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Country $country
     */
    public function setCountry(\OxidEsales\Eshop\Application\Model\Country $country)
    {
        $this->_oCountry = $country;
    }

    /**
     * Country getter
     *
     * @return oxCountry
     */
    public function getCountry()
    {
        return $this->_oCountry;
    }

    /**
     * Validates.
     *
     * @param \OxidEsales\Eshop\Application\Model\CompanyVatIn $vatIn
     *
     * @return bool
     */
    public function validate(\OxidEsales\Eshop\Application\Model\CompanyVatIn $vatIn)
    {
        $result = false;
        $country = $this->getCountry();
        if (!is_null($country)) {
            $result = ($country->getVATIdentificationNumberPrefix() === $vatIn->getCountryCode());
            if (!$result) {
                $this->setError(self::ERROR_ID_NOT_VALID);
            }
        }

        return $result;
    }
}
