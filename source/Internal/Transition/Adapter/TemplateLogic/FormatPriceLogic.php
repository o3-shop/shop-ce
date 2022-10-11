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

use OxidEsales\Eshop\Core\Registry;

class FormatPriceLogic
{

    /**
     * @param array $params
     *
     * @return string
     */
    public function formatPrice(array $params): string
    {
        $output = '';
        $inputPrice = $params['price'];
        if (!is_null($inputPrice)) {
            $output = $this->calculatePrice($inputPrice, $params);
        }

        return $output;
    }

    /**
     * @param mixed $inputPrice
     * @param array $params
     *
     * @return string
     */
    private function calculatePrice($inputPrice, array $params): string
    {
        $config = Registry::getConfig();
        $price = ($inputPrice instanceof \OxidEsales\Eshop\Core\Price) ? $inputPrice->getPrice() : floatval($inputPrice);
        $currency = isset($params['currency']) ? (object) $params['currency'] : $config->getActShopCurrencyObject();
        $output = '';

        if (is_numeric($price)) {
            $output = $this->getFormattedPrice($currency, $price);
        }

        return $output;
    }

    /**
     * @param object $currency active currency object
     * @param mixed  $price
     *
     * @return string
     */
    private function getFormattedPrice($currency, $price): string
    {
        $output = '';
        $decimalSeparator = isset($currency->dec) ? $currency->dec : ',';
        $thousandsSeparator = isset($currency->thousand) ? $currency->thousand : '.';
        $currencySymbol = isset($currency->sign) ? $currency->sign : '';
        $currencySymbolLocation = isset($currency->side) ? $currency->side : '';
        $decimals = isset($currency->decimal) ? (int) $currency->decimal : 2;

        if ((float) $price > 0 || $currencySymbol) {
            $price = number_format($price, $decimals, $decimalSeparator, $thousandsSeparator);
            $output = (isset($currencySymbolLocation) && $currencySymbolLocation == 'Front') ? $currencySymbol . $price : $price . ' ' . $currencySymbol;
        }

        $output = trim($output);

        return $output;
    }
}
