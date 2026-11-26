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

use OxidEsales\Eshop\Core\Registry;

class IfContentLogic
{
    /**
     * @param string $sIdent
     * @param string $sOxid
     *
     * @return mixed
     */
    public function getContent(string $sIdent = null, string $sOxid = null)
    {
        static $aContentCache = [];

        if (
            ($sIdent && isset($aContentCache[$sIdent])) ||
            ($sOxid && isset($aContentCache[$sOxid]))
        ) {
            $oContent = $sOxid ? $aContentCache[$sOxid] : $aContentCache[$sIdent];
        } else {
            $oContent = oxNew('oxContent');
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

        return $oContent;
    }

    /**
     * Parses rendered CMS content through Smarty, as Smarty's oxifcontent block does with its body.
     */
    public function renderContent(string $content, ?string $ident = null): string
    {
        return (string) Registry::getUtilsView()->parseThroughSmarty(
            $content,
            $ident . md5($content),
            Registry::getConfig()->getActiveView()
        );
    }

    /**
     * Loads active CMS content by ident (or oxid) and renders the given field through Smarty with the template
     * variables, as Smarty's oxcontent function does.
     */
    public function includeContent(array $context, ?string $ident, string $field = 'oxcontent', ?string $oxid = null): string
    {
        $text = Registry::getConfig()->getActiveShop()->isProductiveMode()
            ? ''
            : sprintf('<b>content not found ! check ident(%s) !</b>', $ident ?? 'undefined');

        $content = ($ident || $oxid) ? $this->getContent($ident, $oxid) : false;
        if (!$content) {
            return $text;
        }

        $value = (string) $content->getFieldData($field);
        $cacheId = $ident . $oxid . $field . Registry::getLang()->getBaseLanguage() . Registry::getConfig()->getShopId();
        try {
            // Returns the value unparsed when deactivateSmartyForCmsContent is set (or in the demo shop).
            return Registry::getUtilsView()->getRenderedContent($value, $context, $cacheId);
        } catch (\Throwable $error) {
            Registry::getLogger()->error(
                __METHOD__ . " - Rendering CMS content ident '$ident', oxid '$oxid', field '$field' through Smarty failed: '{$error->getMessage()}'.",
                [$error]
            );

            return '';
        }
    }
}
