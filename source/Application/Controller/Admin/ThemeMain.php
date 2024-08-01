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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Theme;
use oxTheme;
use oxException;

/**
 * Admin article main deliveryset manager.
 * There is possibility to change deliveryset name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main Sets.
 */
class ThemeMain extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(), creates deliveryset category tree,
     * passes data to Smarty engine and returns name of template file "deliveryset_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        $soxId = $this->getEditObjectId();

        $oTheme = oxNew(Theme::class);

        if (!$soxId) {
            $soxId = $oTheme->getActiveThemeId();
        }

        if ($oTheme->load($soxId)) {
            $this->_aViewData["oTheme"] = $oTheme;
        } else {
            Registry::getUtilsView()->addErrorToDisplay(oxNew(StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }

        parent::render();

        if ($this->themeInConfigFile()) {
            Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_THEME_SHOULD_BE_ONLY_IN_DATABASE');
        }

        return 'theme_main.tpl';
    }

    /**
     * Check if theme config is in config file.
     *
     * @return bool
     */
    public function themeInConfigFile()
    {
        $blThemeSet = isset(Registry::getConfig()->sTheme);
        $blCustomThemeSet = isset(Registry::getConfig()->sCustomTheme);

        return ($blThemeSet || $blCustomThemeSet);
    }


    /**
     * Set theme
     *
     * @return null
     */
    public function setTheme()
    {
        $sTheme = $this->getEditObjectId();
        /** @var Theme $oTheme */
        $oTheme = oxNew(Theme::class);
        if (!$oTheme->load($sTheme)) {
            Registry::getUtilsView()->addErrorToDisplay(oxNew(StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));

            return;
        }
        try {
            $oTheme->activate();
            $this->resetContentCache();
        } catch (StandardException $oEx) {
            Registry::getUtilsView()->addErrorToDisplay($oEx);
            $oEx->debugOut();
        }
    }
}
