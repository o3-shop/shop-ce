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

namespace OxidEsales\EshopCommunity\Setup;

use OxidEsales\Eshop\Core\Edition\EditionPathProvider;

/**
 * Setup language manager class
 */
class Language extends Core
{
    /**
     * Language translations array
     *
     * @var array
     */
    protected $_aLangData = null;

    /**
     * Returns setup interface language id
     *
     * @return string
     */
    public function getLanguage()
    {
        /** @var Session $oSession */
        $oSession = $this->getInstance("Session");
        /** @var Utilities $oUtils */
        $oUtils = $this->getInstance("Utilities");

        $iLanguage = $oUtils->getRequestVar("setup_lang", "post");

        if (isset($iLanguage)) {
            $oSession->setSessionParam('setup_lang', $iLanguage);
            $iLanguageSubmit = $oUtils->getRequestVar("setup_lang_submit", "post");
            if (isset($iLanguageSubmit)) {
                //updating setup language, so disabling redirect to next step, just reloading same step
                $_GET['istep'] = $_POST['istep'] = $this->getInstance("Setup")->getStep('STEP_WELCOME');
            }
        } elseif ($oSession->getSessionParam('setup_lang') === null) {
            $aLangs = ['en', 'de'];
            $sBrowserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
            $sBrowserLang = (in_array($sBrowserLang, $aLangs)) ? $sBrowserLang : $aLangs[0];
            $oSession->setSessionParam('setup_lang', $sBrowserLang);
        }

        return $oSession->getSessionParam('setup_lang');
    }

    /**
     * Translates passed index
     *
     * @param string $sTextIdent translation index
     *
     * @return string
     */
    public function getText($sTextIdent)
    {
        if ($this->_aLangData === null) {
            $this->_aLangData = [];
            $sLangFilePath = getShopBasePath() . EditionPathProvider::SETUP_DIRECTORY . '/' . ucfirst($this->getLanguage()) . '/lang.php';
            if (file_exists($sLangFilePath) && is_readable($sLangFilePath)) {
                $aLang = [];
                include $sLangFilePath;
                $this->_aLangData = array_merge($aLang, $this->getAdditionalMessages());
            }
        }

        return isset($this->_aLangData[$sTextIdent]) ? $this->_aLangData[$sTextIdent] : null;
    }

    /**
     * Translates module name
     *
     * @param string $sModuleName name of module
     *
     * @return string
     */
    public function getModuleName($sModuleName)
    {
        return $this->getText('MOD_' . strtoupper($sModuleName));
    }

    /**
     * Method is used for overriding.
     *
     * @return array
     */
    protected function getAdditionalMessages()
    {
        return [];
    }
}
