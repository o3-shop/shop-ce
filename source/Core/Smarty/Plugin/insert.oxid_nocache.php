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
 * Smarty plugin
 * -------------------------------------------------------------
 * File: insert.oxid_nocache.php
 * Type: string, html
 * Name: oxid_nocache
 * Purpose: Inserts Items not cached
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_insert_oxid_nocache($params, &$smarty)
{
    $smarty->caching = false;

    // #1184M - specialchar search
    $sSearchParamForHTML = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("searchparam");
    $sSearchParamForLink = rawurlencode(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("searchparam", true));
    if ($sSearchParamForHTML) {
        $smarty->assign_by_ref("searchparamforhtml", $sSearchParamForHTML);
        $smarty->assign_by_ref("searchparam", $sSearchParamForLink);
    }

    $sSearchCat = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("searchcnid");
    if ($sSearchCat) {
        $decodedSearchCat = rawurldecode($sSearchCat);
        $smarty->assign_by_ref("searchcnid", $decodedSearchCat);
    }

    foreach (array_keys($params) as $key) {
        $viewData = & $params[$key];
        $smarty->assign_by_ref($key, $viewData);
    }

    $sOutput = $smarty->fetch($params['tpl']);

    $smarty->caching = false;

    return $sOutput;
}
