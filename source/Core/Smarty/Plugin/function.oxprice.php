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

use OxidEsales\Eshop\Core\Registry;

/**
 * Smarty function
 * -------------------------------------------------------------
 * Purpose: Output price string
 * add [{oxprice price="..." currency="..."}] where you want to display content
 * price - decimal number: 13; 12.45; 13.01;
 * currency - currency abbreviation: EUR, USD, LTL etc.
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxprice($params, &$smarty)
{
    $sOutput = '';
    $iDecimals = 2;
    $sDecimalsSeparator = ',';
    $sThousandSeparator = '.';
    $sCurrencySign = '';
    $sSide = '';
    $mPrice = $params['price'];

    if (!is_null($mPrice)) {
        $oConfig = Registry::getConfig();

        $sPrice = ($mPrice instanceof \OxidEsales\Eshop\Core\Price) ? $mPrice->getPrice() : floatval($mPrice);
        $oCurrency = isset($params['currency']) ? $params['currency'] : $oConfig->getActShopCurrencyObject();

        if (!is_null($oCurrency)) {
            $sDecimalsSeparator = isset($oCurrency->dec) ? $oCurrency->dec : $sDecimalsSeparator;
            $sThousandSeparator = isset($oCurrency->thousand) ? $oCurrency->thousand : $sThousandSeparator;
            $sCurrencySign = isset($oCurrency->sign) ? $oCurrency->sign : $sCurrencySign;
            $sSide = isset($oCurrency->side) ? $oCurrency->side : $sSide;
            $iDecimals = isset($oCurrency->decimal) ? (int) $oCurrency->decimal : $iDecimals;
        }

        if (is_numeric($sPrice)) {
            if ((float) $sPrice > 0 || $sCurrencySign) {
                $sPrice = number_format($sPrice, $iDecimals, $sDecimalsSeparator, $sThousandSeparator);
                $sOutput = (isset($sSide) && $sSide == 'Front') ? $sCurrencySign . $sPrice : $sPrice . ' ' . $sCurrencySign;
            }

            $sOutput = trim($sOutput);
        }
    }

    return $sOutput;
}
