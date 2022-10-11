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
 * Purpose: Modifies provided language constant with it's translation
 * usage: [{$val|oxmultilangassign}]
 * -------------------------------------------------------------
 *
 * @param string $sIdent language constant ident
 * @param mixed  $args   for constants using %s notations
 *
 * @return string
 */
function smarty_modifier_oxmultilangassign($sIdent, $args = null)
{
    if (!isset($sIdent)) {
        $sIdent = 'IDENT MISSING';
    }

    $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
    $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
    $oShop = $oConfig->getActiveShop();
    $iLang = $oLang->getTplLanguage();
    $blShowError = true;

    if ($oShop->isProductiveMode()) {
        $blShowError = false;
    }

    try {
        $sTranslation = $oLang->translateString($sIdent, $iLang, $oLang->isAdmin());
        $blTranslationNotFound = !$oLang->isTranslated();
    } catch (\OxidEsales\Eshop\Core\Exception\LanguageException $oEx) {
        // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
    }

    if (!$blTranslationNotFound) {
        if ($args) {
            if (is_array($args)) {
                $sTranslation = vsprintf($sTranslation, $args);
            } else {
                $sTranslation = sprintf($sTranslation, $args);
            }
        }
    } elseif ($blShowError) {
        $sTranslation = 'ERROR: Translation for ' . $sIdent . ' not found!';
    }

    return $sTranslation;
}
