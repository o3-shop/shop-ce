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

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Exception\ConnectionException;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\SystemEventHandler;

/**
 * Administrator login form.
 * Performs administrator login form data collection.
 */
class LoginController extends AdminController
{
    /** Login page view id. */
    public const VIEW_ID = 'login';

    /**
     * Sets value for _sThisAction to "login".
     */
    public function __construct()
    {
        Registry::getConfig()->setConfigParam('blAdmin', true);
        $this->_sThisAction = 'login';
    }

    /**
     * Executes parent method parent::render(), creates shop object, sets template parameters
     * and returns name of template file "login.tpl".
     *
     * @return string
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        // automatically redirect to SSL login
        if (!$myConfig->isSsl() && strpos($myConfig->getConfigParam('sAdminSSLURL'), 'https://') === 0) {
            Registry::getUtils()->redirect($myConfig->getConfigParam('sAdminSSLURL'), false, 302);
        }

        //resets user once on this screen.
        $oUser = oxNew(User::class);
        $oUser->logout();

        BaseController::render();

        $this->setShopConfigParameters();

        if ($myConfig->isDemoShop()) {
            // demo
            $this->addTplParam('user', 'admin');
            $this->addTplParam('pwd', 'admin');
        }
        //#533 user profile
        $this->addTplParam('profiles', Registry::getUtils()->loadAdminProfile($myConfig->getConfigParam('aInterfaceProfiles')));

        $aLanguages = $this->getAvailableLanguages();
        $this->addTplParam('aLanguages', $aLanguages);

        // setting templates language to selected language id
        foreach ($aLanguages as $iKey => $oLang) {
            if ($oLang->selected) {
                Registry::getLang()->setTplLanguage($iKey);
                break;
            }
        }

        return 'login.tpl';
    }

    /**
     * Sets configuration parameters related to current shop.
     */
    protected function setShopConfigParameters()
    {
        $myConfig = Registry::getConfig();

        $oBaseShop = oxNew(Shop::class);
        $oBaseShop->load($myConfig->getBaseShopId());
        $this->getViewConfig()->setViewConfigParam('sShopVersion', oxNew(ShopVersion::class)->getVersion());
    }

    /**
     * Checks user login data, on success returns "admin_start".
     *
     * @return string|void
     */
    public function checklogin()
    {
        $myUtilsServer = Registry::getUtilsServer();
        $myUtilsView = Registry::getUtilsView();

        $sUser = Registry::getRequest()->getRequestParameter('user');
        $sPass = Registry::getRequest()->getRequestParameter('pwd');
        $sProfile = Registry::getRequest()->getRequestEscapedParameter('profile');

        try { // trying to log in
            $session = Registry::getSession();
            $adminProfiles = $session->getVariable('aAdminProfiles');
            $session->initNewSession();
            $session->setVariable('aAdminProfiles', $adminProfiles);

            /** @var User $oUser */
            $oUser = oxNew(User::class);
            $oUser->login($sUser, $sPass);

            if ($oUser->oxuser__oxrights->value === 'user') {
                throw oxNew(UserException::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
            }

            $iSubshop = (int) $oUser->oxuser__oxrights->value;
            if ($iSubshop) {
                Registry::getSession()->setVariable('shp', $iSubshop);
                Registry::getSession()->setVariable('currentadminshop', $iSubshop);
                Registry::getConfig()->setShopId($iSubshop);
            }
        } catch (UserException|CookieException $oEx) {
            $myUtilsView->addErrorToDisplay($oEx);
            $oStr = Str::getStr();
            $this->addTplParam('user', $oStr->htmlspecialchars($sUser));
            $this->addTplParam('pwd', $oStr->htmlspecialchars($sPass));
            $this->addTplParam('profile', $oStr->htmlspecialchars($sProfile));

            return;
        } catch (ConnectionException $oEx) {
            $myUtilsView->addErrorToDisplay($oEx);
        }

        //execute onAdminLogin() event
        $oEvenHandler = oxNew(SystemEventHandler::class);
        $oEvenHandler->onAdminLogin();

        // #533
        if (isset($sProfile)) {
            $aProfiles = Registry::getSession()->getVariable('aAdminProfiles');
            if ($aProfiles && isset($aProfiles[$sProfile])) {
                // setting cookie to store last locally used profile
                $myUtilsServer->setOxCookie('oxidadminprofile', $sProfile . '@' . implode('@', $aProfiles[$sProfile]), time() + 31536000, '/');
                Registry::getSession()->setVariable('profile', $aProfiles[$sProfile]);
            }
        } else {
            //deleting cookie info, as setting profile to default
            $myUtilsServer->setOxCookie('oxidadminprofile', '', time() - 3600, '/');
        }

        // languages
        $iLang = Registry::getRequest()->getRequestEscapedParameter('chlanguage');
        $aLanguages = Registry::getLang()->getAdminTplLanguageArray();
        if (!isset($aLanguages[$iLang])) {
            $iLang = key($aLanguages);
        }

        $myUtilsServer->setOxCookie('oxidadminlanguage', $aLanguages[$iLang]->abbr, time() + 31536000, '/');

        //P
        //\OxidEsales\Eshop\Core\Registry::getSession()->setVariable( "blAdminTemplateLanguage", $iLang );
        Registry::getLang()->setTplLanguage($iLang);

        return 'admin_start';
    }

    /**
     * Users are always authorized to use login page.
     * Rewrites authorization method.
     *
     * @return boolean
     * @deprecated underscore prefix violates PSR12, will be renamed to "authorize" in next major
     */
    protected function _authorize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->authorize();
    }

    /**
     * Users are always authorized to use login page.
     * Rewrites authorization method.
     *
     * @return boolean
     */
    protected function authorize()
    {
        return true;
    }

    /**
     * Current view ID getter
     *
     * @return string
     */
    public function getViewId()
    {
        return self::VIEW_ID;
    }

    /**
     * Get available admin interface languages
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAvailableLanguages" in next major
     */
    protected function _getAvailableLanguages() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getAvailableLanguages();
    }

    /**
     * Get available admin interface languages
     *
     * @return array
     */
    protected function getAvailableLanguages()
    {
        $sDefLang = Registry::getUtilsServer()->getOxCookie('oxidadminlanguage');
        $sDefLang = $sDefLang ? $sDefLang : $this->getBrowserLanguage();

        $aLanguages = Registry::getLang()->getAdminTplLanguageArray();
        foreach ($aLanguages as $oLang) {
            $oLang->selected = ($sDefLang == $oLang->abbr) ? 1 : 0;
        }

        return $aLanguages;
    }

    /**
     * Get detected user browser language abbreviation
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getBrowserLanguage" in next major
     */
    protected function _getBrowserLanguage() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getBrowserLanguage();
    }

    /**
     * Get detected user browser language abbreviation
     *
     * @return string
     */
    protected function getBrowserLanguage()
    {
        return strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
    }
}
