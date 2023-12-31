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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Themes handler class.
 *
 * @internal Do not make a module extension for this class.
 */
class Theme extends \OxidEsales\Eshop\Core\Base
{
    /**
     * Theme info array
     *
     * @var array
     */
    protected $_aTheme = [];

    /**
     * Theme info list
     *
     * @var array
     */
    protected $_aThemeList = [];

    /**
     * Load theme info
     *
     * @param string $sOXID theme id
     *
     * @return bool
     */
    public function load($sOXID)
    {
        $sFilePath = $this->getConfig()->getViewsDir() . $sOXID . "/theme.php";
        if (file_exists($sFilePath) && is_readable($sFilePath)) {
            $aTheme = [];
            include $sFilePath;
            $this->_aTheme = $aTheme;
            $this->_aTheme['id'] = $sOXID;
            $this->_aTheme['active'] = ($this->getActiveThemeId() == $sOXID);

            return true;
        }

        return false;
    }

    /**
     * Set theme as active
     */
    public function activate()
    {
        $sError = $this->checkForActivationErrors();
        if ($sError) {
            /** @var \OxidEsales\Eshop\Core\Exception\StandardException $oException */
            $oException = oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class, $sError);
            throw $oException;
        }
        $sParent = $this->getInfo('parentTheme');
        if ($sParent) {
            $this->getConfig()->saveShopConfVar("str", 'sTheme', $sParent);
            $this->getConfig()->saveShopConfVar("str", 'sCustomTheme', $this->getId());
        } else {
            $this->getConfig()->saveShopConfVar("str", 'sTheme', $this->getId());
            $this->getConfig()->saveShopConfVar("str", 'sCustomTheme', '');
        }
        $settingsHandler = oxNew(\OxidEsales\Eshop\Core\SettingsHandler::class);
        $settingsHandler->setModuleType('theme')->run($this);
    }

    /**
     * Load theme info list
     *
     * @return array
     */
    public function getList()
    {
        $this->_aThemeList = [];
        $sOutDir = $this->getConfig()->getViewsDir();
        foreach (glob($sOutDir . "*", GLOB_ONLYDIR) as $sDir) {
            $oTheme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
            if ($oTheme->load(basename($sDir))) {
                $this->_aThemeList[$sDir] = $oTheme;
            }
        }

        return $this->_aThemeList;
    }

    /**
     * Return theme information
     *
     * @param string $sName name of info item to retrieve
     *
     * @return mixed
     */
    public function getInfo($sName)
    {
        if (!isset($this->_aTheme[$sName])) {
            return null;
        }

        return $this->_aTheme[$sName];
    }

    /**
     * Return current active theme, or custom theme if specified
     *
     * @return string
     */
    public function getActiveThemeId()
    {
        $sCustTheme = $this->getConfig()->getConfigParam('sCustomTheme');
        if ($sCustTheme) {
            return $sCustTheme;
        }

        return $this->getConfig()->getConfigParam('sTheme');
    }

    /**
     * Get active themes list.
     * Examples:
     *      if flow theme is active we will get ['flow']
     *      if azure is extended by some other we will get ['azure', 'extending_theme']
     *
     * @return array
     */
    public function getActiveThemesList()
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();

        $activeThemeList = [];
        if (!$this->isAdmin()) {
            $activeThemeList[] = $config->getConfigParam('sTheme');

            if ($customThemeId = $config->getConfigParam('sCustomTheme')) {
                $activeThemeList[] = $customThemeId;
            }
        }

        return $activeThemeList;
    }

    /**
     * Return loaded parent
     *
     * @return \OxidEsales\Eshop\Core\Theme
     */
    public function getParent()
    {
        $sParent = $this->getInfo('parentTheme');
        if (!$sParent) {
            return null;
        }
        $oTheme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
        if ($oTheme->load($sParent)) {
            return $oTheme;
        }

        return null;
    }

    /**
     * run pre-activation checks and return EXCEPTION_* translation string if error
     * found or false on success
     *
     * @return string
     */
    public function checkForActivationErrors()
    {
        if (!$this->getId()) {
            return 'EXCEPTION_THEME_NOT_LOADED';
        }
        $oParent = $this->getParent();
        if ($oParent) {
            $sParentVersion = $oParent->getInfo('version');
            if (!$sParentVersion) {
                return 'EXCEPTION_PARENT_VERSION_UNSPECIFIED';
            }
            $aMyParentVersions = $this->getInfo('parentVersions');
            if (!$aMyParentVersions || !is_array($aMyParentVersions)) {
                return 'EXCEPTION_UNSPECIFIED_PARENT_VERSIONS';
            }
            if (!in_array($sParentVersion, $aMyParentVersions)) {
                return 'EXCEPTION_PARENT_VERSION_MISMATCH';
            }
        } elseif ($this->getInfo('parentTheme')) {
            return 'EXCEPTION_PARENT_THEME_NOT_FOUND';
        }

        return false;
    }

    /**
     * Get theme ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->getInfo("id");
    }
}
