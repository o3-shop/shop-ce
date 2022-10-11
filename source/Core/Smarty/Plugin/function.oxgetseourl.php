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
 * Purpose: output SEO style url
 * add [{oxgetseourl ident="..."}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxgetseourl($params, &$smarty)
{
    $sOxid = isset($params['oxid']) ? $params['oxid'] : null;
    $sType = isset($params['type']) ? $params['type'] : null;
    $sUrl  = $sIdent = isset($params['ident']) ? $params['ident'] : null;

    // requesting specified object SEO url
    if ($sType) {
        $oObject = oxNew($sType);

        // special case for content type object when ident is provided
        if ($sType == 'oxcontent' && $sIdent && $oObject->loadByIdent($sIdent)) {
            $sUrl = $oObject->getLink();
        } elseif ($sOxid) {
            //minimising aricle object loading
            if (strtolower($sType) == "oxarticle") {
                $oObject->disablePriceLoad();
                $oObject->setNoVariantLoading(true);
            }

            if ($oObject->load($sOxid)) {
                $sUrl = $oObject->getLink();
            }
        }
    } elseif ($sUrl && \OxidEsales\Eshop\Core\Registry::getUtils()->seoIsActive()) {
        // if SEO is on ..
        $oEncoder = \OxidEsales\Eshop\Core\Registry::getSeoEncoder();
        if (($sStaticUrl = $oEncoder->getStaticUrl($sUrl))) {
            $sUrl = $sStaticUrl;
        } else {
            // in case language parameter is not added to url
            $sUrl = \OxidEsales\Eshop\Core\Registry::getUtilsUrl()->processUrl($sUrl);
        }
    }

    $sDynParams = isset($params['params']) ? $params['params'] : false;
    if ($sDynParams) {
        include_once $smarty->_get_plugin_filepath('modifier', 'oxaddparams');
        $sUrl = smarty_modifier_oxaddparams($sUrl, $sDynParams);
    }

    return $sUrl;
}
