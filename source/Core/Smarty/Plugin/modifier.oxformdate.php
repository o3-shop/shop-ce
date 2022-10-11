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
 * Smarty modifier
 * -------------------------------------------------------------
 * Name:     smarty_modifier_oxformdate<br>
 * Purpose:  Conterts date/timestamp/datetime type value to user defined format
 * Example:  {$object|oxformdate:"foo"}
 * -------------------------------------------------------------
 *
 * @param object $oConvObject   oxField object
 * @param string $sFieldType    additional type if field (this may help to force formatting)
 * @param bool   $blPassedValue bool if true, will simulate object as sometimes we need to apply formatting to some regulat values
 *
 * @return string
 */
function smarty_modifier_oxformdate($oConvObject, $sFieldType = null, $blPassedValue = false)
{
   // creating fake bject
    if ($blPassedValue || is_string($oConvObject)) {
        $sValue = $oConvObject;
        $oConvObject = new \OxidEsales\Eshop\Core\Field();
        $oConvObject->fldmax_length = "0";
        $oConvObject->fldtype = $sFieldType;
        $oConvObject->setValue($sValue);
    }

    $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

    // if such format applies to this type of field - sets formatted value to passed object
    if (!$myConfig->getConfigParam('blSkipFormatConversion')) {
        if ($oConvObject->fldtype == "datetime" || $sFieldType == "datetime") {
            \OxidEsales\Eshop\Core\Registry::getUtilsDate()->convertDBDateTime($oConvObject);
        } elseif ($oConvObject->fldtype == "timestamp" || $sFieldType == "timestamp") {
            \OxidEsales\Eshop\Core\Registry::getUtilsDate()->convertDBTimestamp($oConvObject);
        } elseif ($oConvObject->fldtype == "date" || $sFieldType == "date") {
            \OxidEsales\Eshop\Core\Registry::getUtilsDate()->convertDBDate($oConvObject);
        }
    }

    return $oConvObject->value;
}

/* vim: set expandtab: */
