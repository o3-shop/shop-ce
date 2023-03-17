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
 * Purpose: Output help popup icon and help text
 * add [{oxinputhelp ident="..."}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_oxinputhelp($params, &$smarty)
{
    $sIdent = $params['ident'];
    $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
    $iLang  = $oLang->getTplLanguage();
    $blAdmin = null;

    try {
        $sTranslation = $oLang->translateString($sIdent, $iLang, $blAdmin);
    } catch (\OxidEsales\Eshop\Core\Exception\LanguageException $oEx) {
        // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
    }

    if (!$sTranslation || $sTranslation == $sIdent) {
        //no translation, return empty string
        return '';
    }

    //name of template file where is stored message text
    $sTemplate = 'inputhelp.tpl';

    $smarty->assign('sHelpId', $sIdent);
    $smarty->assign('sHelpText', $sTranslation);

    return $smarty->fetch($sTemplate);
}
