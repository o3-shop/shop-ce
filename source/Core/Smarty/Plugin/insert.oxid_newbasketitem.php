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
 * File: insert.oxid_newbasketitem.php
 * Type: string, html
 * Name: newbasketitem
 * Purpose: Used for tracking in econda, etracker etc.
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_insert_oxid_newbasketitem($params, &$smarty)
{
    $myConfig  = \OxidEsales\Eshop\Core\Registry::getConfig();

    $aTypes = ['0' => 'none','1' => 'message', '2' => 'popup', '3' => 'basket'];
    $iType  = $myConfig->getConfigParam('iNewBasketItemMessage');

    // If corect type of message is expected
    if ($iType && $params['type'] && ($params['type'] != $aTypes[$iType])) {
        return '';
    }

    //name of template file where is stored message text
    $sTemplate = $params['tpl'] ? $params['tpl'] : 'inc_newbasketitem.snippet.tpl';

    //allways render for ajaxstyle popup
    $blRender = $params['ajax'] && ($iType == 2);

    //fetching article data
    $oNewItem = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('_newitem');

    if ($oNewItem) {
        // loading article object here because on some system passing article by session couses problems
        $oNewItem->oArticle = oxNew('oxarticle');
        $oNewItem->oArticle->Load($oNewItem->sId);

        // passing variable to template with unique name
        $smarty->assign('_newitem', $oNewItem);

        // deleting article object data
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('_newitem');

        $blRender = true;
    }

    // returning generated message content
    if ($blRender) {
        return $smarty->fetch($sTemplate);
    }
}
