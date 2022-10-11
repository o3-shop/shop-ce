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

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

use stdClass;

class FormatCurrencyLogic
{

    /**
     * @param string     $sFormat
     * @param string|int $sValue
     *
     * @return string
     */
    public function numberFormat($sFormat = "EUR@ 1.00@ ,@ .@ EUR@ 2", $sValue = 0)
    {
        // logic copied from \OxidEsales\Eshop\Core\Config::getCurrencyArray()
        $sCur = explode("@", $sFormat);
        $oCur = new stdClass();
        $oCur->id = 0;
        $oCur->name = @trim($sCur[0]);
        $oCur->rate = @trim($sCur[1]);
        $oCur->dec = @trim($sCur[2]);
        $oCur->thousand = @trim($sCur[3]);
        $oCur->sign = @trim($sCur[4]);
        $oCur->decimal = @trim($sCur[5]);

        // change for US version
        if (isset($sCur[6])) {
            $oCur->side = @trim($sCur[6]);
        }

        return \OxidEsales\Eshop\Core\Registry::getLang()->formatCurrency($sValue, $oCur);
    }
}
