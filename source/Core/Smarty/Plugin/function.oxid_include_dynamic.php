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
 * Smarty function
 * -------------------------------------------------------------
 * Purpose: render or leave dynamic parts with parameters in
 * templates used by content caching algorithm.
 * Use [{oxid_include_dynamic file="..."}] instead of include
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxid_include_dynamic($params, &$smarty)
{
    $params = array_change_key_case($params, CASE_LOWER);

    if (!isset($params['file'])) {
        $smarty->trigger_error("oxid_include_dynamic: missing 'file' parameter");
        return;
    }

    if (!empty($smarty->_tpl_vars["_render4cache"])) {
        $sContent = "<oxid_dynamic>";
        foreach ($params as $key => $val) {
            $sContent .= " $key='" . base64_encode($val) . "'";
        }
        $sContent .= "</oxid_dynamic>";
        return $sContent;
    } else {
        $sPrefix = "_";
        if (array_key_exists('type', $params)) {
            $sPrefix .= $params['type'] . "_";
        }

        foreach ($params as $key => $val) {
            if ($key != 'type' && $key != 'file') {
                $sContent = $sContent ?? '';
                $sContent .= " $key='$val'";
                $smarty->assign($sPrefix . $key, $val);
            }
        }

        $smarty->assign("__oxid_include_dynamic", true);
        $sRes = $smarty->fetch($params['file']);
        $smarty->clear_assign("__oxid_include_dynamic");
        return $sRes;
    }
}
