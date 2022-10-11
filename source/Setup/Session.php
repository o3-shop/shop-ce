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

/**
 * Setup session manager class
 */
class Session extends Core
{
    /**
     * Session data array
     *
     * @var array
     */
    protected $_aSessionData = null;

    /**
     * Session ID
     *
     * @var string
     */
    protected $_sSid = null;

    /**
     * Session name
     *
     * @var string
     */
    protected $_sSessionName = 'setup_sid';

    /**
     * Is new session
     *
     * @var bool
     */
    protected $_blNewSession = false;

    /**
     * Initialize session class
     */
    public function __construct()
    {
        ini_set('session.use_cookies', 0);

        // initialize session
        $this->_startSession();
        $this->_initSessionData();
    }

    /**
     * Start session
     */
    protected function _startSession() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        session_name($this->_sSessionName);

        /** @var Utilities $oUtils */
        $oUtils = $this->getInstance("Utilities");
        $sSid = $oUtils->getRequestVar('sid', 'get');

        if (empty($sSid)) {
            $sSid = $oUtils->getRequestVar('sid', 'post');
        }

        if (!empty($sSid)) {
            session_id($sSid);
        }

        session_start();
        $sSid = $this->_validateSession();
        $this->setSid($sSid);
    }

    /**
     * Validate if session is started by setup script, if not, generate new session.
     *
     * @return string Session ID
     */
    protected function _validateSession() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->getIsNewSession() === true) {
            $this->setSessionParam('setup_session', true);
        } elseif ($this->getSessionParam('setup_session') !== true) {
            $sNewSid = $this->_getNewSessionID();
            session_write_close();

            session_id($sNewSid);
            session_start();
            $this->setSessionParam('setup_session', true);
        }

        return session_id();
    }

    /**
     * Generate new unique session ID
     *
     * @return string
     */
    protected function _getNewSessionID() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        session_regenerate_id(false);
        $this->setIsNewSession(true);

        return session_id();
    }

    /**
     * Returns session id, which is used in forms and urls
     * (actually this id keeps all session data)
     *
     * @return string
     */
    public function getSid()
    {
        return $this->_sSid;
    }

    /**
     * Sets current session ID
     *
     * @param string $sSid session ID
     */
    public function setSid($sSid)
    {
        $this->_sSid = $sSid;
    }

    /**
     * Initializes setup session data array
     */
    protected function _initSessionData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /** @var Utilities $oUtils */
        $oUtils = $this->getInstance("Utilities");

        //storing country value settings to session
        $sCountryLang = $oUtils->getRequestVar("country_lang", "post");
        if (isset($sCountryLang)) {
            $this->setSessionParam('country_lang', $sCountryLang);
        }

        //storing shop language value settings to session
        $sShopLang = $oUtils->getRequestVar("sShopLang", "post");
        if (isset($sShopLang)) {
            $this->setSessionParam('sShopLang', $sShopLang);
        }

        //storing check for updates settings to session
        $blCheckForUpdates = $oUtils->getRequestVar("check_for_updates", "post");
        if (isset($blCheckForUpdates)) {
            $this->setSessionParam('check_for_updates', $blCheckForUpdates);
        }

        // store eula to session
        $iEula = $oUtils->getRequestVar("iEula", "post");
        if (isset($iEula)) {
            $this->setSessionParam('eula', $iEula);
        }
    }

    /**
     * Return session object reference.
     *
     * @return array
     */
    protected function &_getSessionData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $_SESSION;
    }

    /**
     * @param bool $value
     */
    public function setIsNewSession($value)
    {
        $this->_blNewSession = $value;
    }

    /**
     * @return bool
     */
    public function getIsNewSession()
    {
        return $this->_blNewSession;
    }

    /**
     * Returns session parameter value
     *
     * @param string $sParamName parameter name
     *
     * @return mixed
     */
    public function getSessionParam($sParamName)
    {
        $aSessionData = & $this->_getSessionData();
        if (isset($aSessionData[$sParamName])) {
            return $aSessionData[$sParamName];
        }
    }

    /**
     * Sets session parameter value
     *
     * @param string $sParamName  parameter name
     * @param mixed  $sParamValue parameter value
     */
    public function setSessionParam($sParamName, $sParamValue)
    {
        $aSessionData = & $this->_getSessionData();
        $aSessionData[$sParamName] = $sParamValue;
    }
}
