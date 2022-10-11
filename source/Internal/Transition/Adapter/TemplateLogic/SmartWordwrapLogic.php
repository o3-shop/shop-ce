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

class SmartWordwrapLogic
{
    /**
     * @param string $string
     * @param int    $length
     * @param string $break
     * @param int    $cutRows
     * @param int    $tolerance
     * @param string $etc
     *
     * @return string
     */
    public function wrapWords($string, $length, $break, $cutRows, $tolerance, $etc)
    {

        $wrapTag = "<wrap>";
        $wrapChars = ["-"];
        $afterWrapChars = ["-" . $wrapTag];


        $string = trim($string);

        if (strlen($string) <= $length) {
            return $string;
        }

        //trying to wrap without cut
        $str = wordwrap($string, $length, $wrapTag, false);
        $arr = explode($wrapTag, $str);

        $alt = [];

        $ok = true;
        foreach ($arr as $row) {
            if (strlen($row) > ($length + $tolerance)) {
                $tmpstr = str_replace($wrapChars, $afterWrapChars, $row);
                $tmparr = explode($wrapTag, $tmpstr);

                foreach ($tmparr as $altrow) {
                    array_push($alt, $altrow);

                    if (strlen($altrow) > ($length + $tolerance)) {
                        $ok = false;
                    }
                }
            } else {
                array_push($alt, $row);
            }
        }

        $arr = $alt;

        if (!$ok) {
            //trying to wrap with cut
            $str = wordwrap($string, $length, $wrapTag, true);
            $arr = explode($wrapTag, $str);
        }

        if ($cutRows && count($arr) > $cutRows) {
            $arr = array_splice($arr, 0, $cutRows);

            if (strlen($arr[$cutRows - 1] . $etc) > $length + $tolerance) {
                $arr[$cutRows - 1] = substr($arr[$cutRows - 1], 0, $length - strlen($etc));
            }

            $arr[$cutRows - 1] = $arr[$cutRows - 1] . $etc;
        }

        return implode($break, $arr);
    }
}
