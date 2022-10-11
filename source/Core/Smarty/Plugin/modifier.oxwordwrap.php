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


/**
 * Smarty wordwrap modifier
 * -------------------------------------------------------------
 * Name:     wordwrap<br>
 * Purpose:  wrap a string of text at a given length
 * -------------------------------------------------------------
 *
 * @param string  $sString String to wrap
 * @param integer $iLength To length
 * @param string  $sWraper wrap using
 * @param bool    $blCut   Cut
 *
 * @return string
 */
function smarty_modifier_oxwordwrap($sString, $iLength = 80, $sWraper = "\n", $blCut = false)
{
    return getStr()->wordwrap($sString, $iLength, $sWraper, $blCut);
}
