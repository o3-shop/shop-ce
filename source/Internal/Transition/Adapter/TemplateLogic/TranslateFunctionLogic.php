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

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

class TranslateFunctionLogic
{
    /**
     * @param array $params
     *
     * @return string
     */
    public function getTranslation(array $params): string
    {
        startProfile("smarty_function_oxmultilang");
        $language = \OxidEsales\Eshop\Core\Registry::getLang();
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $activeShop = $config->getActiveShop();
        $isAdmin = $language->isAdmin();
        $ident = isset($params['ident']) ? $params['ident'] : 'IDENT MISSING';
        $args = isset($params['args']) ? $params['args'] : false;
        $suffix = isset($params['suffix']) ? $params['suffix'] : 'NO_SUFFIX';
        $showError = isset($params['noerror']) ? !$params['noerror'] : true;
        $tplLang = $language->getTplLanguage();
        if (!$isAdmin && $activeShop->isProductiveMode()) {
            $showError = false;
        }
        try {
            $translation = $language->translateString($ident, $tplLang, $isAdmin);
            $translationNotFound = !$language->isTranslated();
            if ('NO_SUFFIX' != $suffix) {
                $suffixTranslation = $language->translateString($suffix, $tplLang, $isAdmin);
            }
        } catch (\OxidEsales\Eshop\Core\Exception\LanguageException $oEx) {
            // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
        }
        if ($translationNotFound && isset($params['alternative'])) {
            $translation = $params['alternative'];
            $translationNotFound = false;
        }
        if (!$translationNotFound) {
            if ($args !== false) {
                if (is_array($args)) {
                    $translation = vsprintf($translation, $args);
                } else {
                    $translation = sprintf($translation, $args);
                }
            }
            if ('NO_SUFFIX' != $suffix) {
                $translation .= $suffixTranslation;
            }
        } elseif ($showError) {
            $translation = 'ERROR: Translation for ' . $ident . ' not found!';
        }
        stopProfile("smarty_function_oxmultilang");

        return $translation;
    }
}
