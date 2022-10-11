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

class TruncateLogic
{
    /**
     * @param string  $sString
     * @param integer $iLength
     * @param string  $sSufix
     * @param bool    $blBreakWords
     * @param bool    $middle
     *
     * @return string
     */
    public function truncate(string $sString = null, int $iLength = 80, string $sSufix = '...', bool $blBreakWords = false, bool $middle = false): string
    {
        if ($iLength == 0) {
            return '';
        } elseif ($iLength > 0 && getStr()->strlen($sString) > $iLength) {
            $iLength -= getStr()->strlen($sSufix);

            $sString = str_replace(['&#039;', '&quot;'], ["'", '"'], $sString);

            if (!$blBreakWords) {
                $sString = getStr()->preg_replace('/\s+?(\S+)?$/', '', getStr()->substr($sString, 0, $iLength + 1));
            }

            $sString = getStr()->substr($sString, 0, $iLength) . $sSufix;

            return str_replace(["'", '"'], ['&#039;', '&quot;'], $sString);
        }

        return $sString ?: '';
    }
}
