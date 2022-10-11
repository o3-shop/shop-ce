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
 * File: block.oxid_content.php
 * Type: string, html
 * Name: block_oxifcontent
 * Purpose: Output content snippet if content exists
 * add [{oxifcontent ident="..."}][{/oxifcontent}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param string $content rendered content
 * @param Smarty &$smarty clever simulation of a method
 * @param bool   &$repeat repeat
 *
 * @return string
 */
function smarty_block_oxifcontent($params, $content, &$smarty, &$repeat)
{
    $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

    $sIdent  = isset($params['ident']) ? $params['ident'] : null;
    $sOxid   = isset($params['oxid']) ? $params['oxid'] : null;
    $sAssign = isset($params['assign']) ? $params['assign'] : null;
    $sObject = isset($params['object']) ? $params['object'] : 'oCont';

    if ($repeat) {
        if ($sIdent || $sOxid) {
            static $aContentCache = [];

            if (
                ($sIdent && isset($aContentCache[$sIdent])) ||
                 ($sOxid && isset($aContentCache[$sOxid]))
            ) {
                $oContent = $sOxid ? $aContentCache[$sOxid] : $aContentCache[$sIdent];
            } else {
                $oContent = oxNew("oxContent");
                $blLoaded = $sOxid ? $oContent->load($sOxid) : ($oContent->loadbyIdent($sIdent));
                if ($blLoaded && $oContent->isActive()) {
                    $aContentCache[$oContent->getId()] = $aContentCache[$oContent->getLoadId()] = $oContent;
                } else {
                    $oContent = false;
                    if ($sOxid) {
                        $aContentCache[$sOxid] = $oContent;
                    } else {
                        $aContentCache[$sIdent] = $oContent;
                    }
                }
            }

            $blLoaded = false;
            if ($oContent) {
                $smarty->assign($sObject, $oContent);
                $blLoaded = true;
            }
        } else {
            $blLoaded = false;
        }
        $repeat = $blLoaded;
    } else {
        $oStr = getStr();
        $blHasSmarty = $oStr->strstr($content, '[{');
        if ($blHasSmarty) {
            $content = \OxidEsales\Eshop\Core\Registry::getUtilsView()->parseThroughSmarty($content, $sIdent . md5($content), $myConfig->getActiveView());
        }

        if ($sAssign) {
            $smarty->assign($sAssign, $content);
        } else {
            return $content;
        }
    }
}
