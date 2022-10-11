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
 * Purpose: Output translated salutation field
 * add [{$}] where you want to display content
 * -------------------------------------------------------------
 *
 * @param string $sIdent language constant ident
 *
 * @return string
 */
function smarty_modifier_oxmultilangsal($sIdent)
{
    $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
    $iLang = $oLang->getTplLanguage();

    if (!isset($iLang)) {
        $iLang = $oLang->getBaseLanguage();
        if (!isset($iLang)) {
            $iLang = 0;
        }
    }

    try {
        $sTranslation = $oLang->translateString($sIdent, $iLang, $oLang->isAdmin());
    } catch (\OxidEsales\Eshop\Core\Exception\LanguageException $oEx) {
        // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
    }

    return $sTranslation;
}
