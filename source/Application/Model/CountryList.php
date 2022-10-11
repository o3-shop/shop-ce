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
 * Country list manager class.
 * Collects a list of countries according to collection rules (active).
 *
 */
class CountryList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * Call parent class constructor
     */
    public function __construct()
    {
        parent::__construct('oxcountry');
    }

    /**
     * Selects and loads all active countries
     *
     * @param integer $iLang language
     */
    public function loadActiveCountries($iLang = null)
    {
        $sViewName = getViewName('oxcountry', $iLang);
        $sSelect = "SELECT oxid, oxtitle, oxisoalpha2 FROM {$sViewName} WHERE oxactive = '1' ORDER BY oxorder, oxtitle ";
        $this->selectString($sSelect);
    }
}
