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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;

/**
 * Admin article main deliveryset manager.
 * There is a possibility to change deliveryset name, article, user, etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main Sets.
 */
class ThemeConfiguration extends ShopConfiguration
{
    protected $_sTheme = null;

    /**
     * Executes parent method parent::render(), creates deliveryset category tree,
     * passes data to Smarty engine, and returns the name of the template file "deliveryset_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        $sTheme = $this->_sTheme = $this->getEditObjectId();
        $sShopId = $myConfig->getShopId();

        if (!isset($sTheme)) {
            $sTheme = $this->_sTheme = Registry::getConfig()->getConfigParam('sTheme');
        }

        $oTheme = oxNew(Theme::class);
        if ($oTheme->load($sTheme)) {
            $this->_aViewData['oTheme'] = $oTheme;

            try {
                $aDbVariables = $this->loadConfVars($sShopId, $this->_getModuleForConfigVars());
                $this->_aViewData['var_constraints'] = $aDbVariables['constraints'];
                $this->_aViewData['var_grouping'] = $aDbVariables['grouping'];
                foreach ($this->_aConfParams as $sType => $sParam) {
                    $this->_aViewData[$sParam] = $aDbVariables['vars'][$sType];
                }
            } catch (StandardException $oEx) {
                Registry::getUtilsView()->addErrorToDisplay($oEx);
                $oEx->debugOut();
            }
        } else {
            Registry::getUtilsView()->addErrorToDisplay(oxNew(StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }

        return 'theme_config.tpl';
    }

    /**
     * return theme filter for config variables
     *
     * @return string
     * @deprecated the underscore prefix violates PSR12, will be renamed to "getModuleForConfigVars" in the next major
     */
    protected function _getModuleForConfigVars() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_sTheme === null) {
            $this->_sTheme = $this->getEditObjectId();
        }

        return Config::OXMODULE_THEME_PREFIX . $this->_sTheme;
    }

    /**
     * Saves shop configuration variables
     */
    public function saveConfVars()
    {
        $myConfig = Registry::getConfig();

        AdminDetailsController::save();

        $sShopId = $myConfig->getShopId();

        $sModule = $this->_getModuleForConfigVars();

        foreach ($this->_aConfParams as $sType => $sParam) {
            $aConfVars = Registry::getRequest()->getRequestEscapedParameter($sParam);
            if (is_array($aConfVars)) {
                foreach ($aConfVars as $sName => $sValue) {
                    $myConfig->saveShopConfVar(
                        $sType,
                        $sName,
                        $this->_serializeConfVar($sType, $sName, $sValue),
                        $sShopId,
                        $sModule
                    );
                }
            }
        }
    }

    /**
     * Override `ShopConfiguration::save()` to skip the parent's oxshops
     * write path. The parent does:
     *
     *     $shop = oxNew(Shop::class);
     *     if ($shop->load($this->getEditObjectId())) { ... $shop->save(); }
     *
     * On a regular shop-config screen `getEditObjectId()` returns a numeric
     * shop OXID and `Shop::load()` matches an oxshops row. On THIS screen
     * (`ThemeConfiguration`) `getEditObjectId()` returns the theme name
     * (e.g. `'wave'`, `'flow'`, `'o3-theme'`) — the Shop model can't
     * resolve that against `oxshops.OXID`, but still ends up writing to
     * `oxshops` with `OXID=0`/the theme name and triggers an INSERT
     * exception when saving theme settings.
     *
     * Theme config variables go through `saveConfVars()` (oxconfig only),
     * which is correct on its own. We override `save()` to call exactly
     * that and stop. Bug report from @egate-media-Frank; verified against the
     * parent at ShopConfiguration.php:207-219.
     */
    public function save()
    {
        $this->saveConfVars();
    }
}
