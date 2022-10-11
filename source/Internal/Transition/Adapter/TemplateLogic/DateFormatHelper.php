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

class DateFormatHelper
{

    /**
     * @param string $format
     * @param int    $timestamp
     *
     * @return string
     */
    public function fixWindowsTimeFormat($format, $timestamp)
    {
        $winFormatSearch = ['%D', '%h', '%n', '%r', '%R', '%t', '%T'];
        $winFormatReplace = ['%m/%d/%y', '%b', "\n", '%I:%M:%S %p', '%H:%M', "\t", '%H:%M:%S'];
        if (strpos($format, '%e') !== false) {
            $winFormatSearch[] = '%e';
            $winFormatReplace[] = sprintf('%\' 2d', date('j', $timestamp));
        }
        if (strpos($format, '%l') !== false) {
            $winFormatSearch[] = '%l';
            $winFormatReplace[] = sprintf('%\' 2d', date('h', $timestamp));
        }
        $format = str_replace($winFormatSearch, $winFormatReplace, $format);

        return $format;
    }
}
