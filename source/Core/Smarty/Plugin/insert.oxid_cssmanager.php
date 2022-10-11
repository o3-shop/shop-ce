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
 * File: insert.oxid_cssmanager.php
 * Type: string, html
 * Name: oxid_cmpbasket
 * Purpose: Includes css style file according to template file or sets default
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_insert_oxid_cssmanager($params, &$smarty)
{
    $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

    $smarty->caching = false;

    // template file name
    $sTplName = $smarty->oxobject->getTemplateName();

    // css file extension
    $sCssExt  = "css";

    // sets name of alternative CSS file passed template parameters
    if (isset($params["cssname"]) && $params["cssname"]) {
        $sAltCss = $params["cssname"];
    // possible CSS file for current template
    } else {
        $sAltCss = $sTplName . "." . $sCssExt;
    }

    // user defined alternative CSS files dir
    $sAltCssDir = "styles/";

    // URL to templates, there may be stored and css files
    if (isset($params["cssurl"]) && $params["cssurl"]) {
        $sTplURL = $params["cssurl"];
    } else {
        $sTplURL =  $myConfig->getResourceUrl($sAltCssDir, isAdmin());
    }

    // direct path to templates, there may be stored and css files
    if (isset($params["csspath"]) && $params["csspath"]) {
        $sTplPath = $params["csspath"];
    } else {
        $sTplPath = $myConfig->getResourcePath($sAltCssDir, isAdmin());
    }

    // full path to alternavive CSS file
    $sAltFullPath = $sTplPath . $sAltCss;

    $sOutput = "";
    // checking if alternative CSS file exists and returning URL to CSS file
    if ($sTplName && file_exists($sAltFullPath) && is_file($sAltFullPath)) {
        $sOutput = '<link rel="stylesheet" href="' . $sTplURL . $sAltCss . '">';
    }

    $smarty->caching = false;

    return $sOutput;
}
