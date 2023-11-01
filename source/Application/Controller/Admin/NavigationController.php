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
 * @copyright  Copyright (c) 2020 egate media GmbH (https://www.egate-media.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\AdminNaviRights;
use OxidEsales\EshopCommunity\Core\AdminViewSetting;

/**
 * Administrator GUI navigation manager class.
 */
class NavigationController extends \OxidEsales\Eshop\Application\Controller\Admin\AdminController
{
    /**
     * Executes parent method parent::render(), generates menu HTML code,
     * passes data to Smarty engine, returns name of template file "nav_frame.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $myUtilsServer = Registry::getUtilsServer();

        $sItem = Registry::getConfig()->getRequestParameter("item");
        $sItem = $sItem ? basename($sItem) : false;
        if (!$sItem) {
            $sItem = "nav_frame.tpl";
        } else {
            $oNavTree = $this->getNavigation();

            // set menu structure
            $this->_aViewData["menustructure"] = $oNavTree->getDomXml()->documentElement->childNodes;

            // version patch string
            $this->_aViewData["sVersion"] = $this->_sShopVersion;

            //checking requirements if this is not nav frame reload
            if (!Registry::getConfig()->getRequestParameter("navReload")) {
                // #661 execute stuff we run each time when we start admin once
                if ('home.tpl' == $sItem) {
                    $this->_aViewData['aMessage'] = $this->_doStartUpChecks();
                }
            } else {
                //removing reload param to force requirements checking next time
                Registry::getSession()->deleteVariable("navReload");
            }
        }

        $blisMallAdmin = Registry::getSession()->getVariable('malladmin');
        $oShoplist = oxNew(\OxidEsales\Eshop\Application\Model\ShopList::class);
        if (!$blisMallAdmin) {
            // we only allow to see our shop
            $iShopId = Registry::getSession()->getVariable("actshop");
            $oShop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
            $oShop->load($iShopId);
            $oShoplist->add($oShop);
        } else {
            $oShoplist->getIdTitleList();
        }

        $this->_aViewData['shoplist'] = $oShoplist;
        return $sItem;
    }

    /**
     * Changing active shop
     */
    public function chshp()
    {
        parent::chshp();

        // informing about basefrm parameters
        $this->_aViewData['loadbasefrm'] = true;
        $this->_aViewData['listview'] = Registry::getConfig()->getRequestParameter('listview');
        $this->_aViewData['editview'] = Registry::getConfig()->getRequestParameter('editview');
        $this->_aViewData['actedit'] = Registry::getConfig()->getRequestParameter('actedit');
    }

    /**
     * Destroy session, redirects to admin login and clears cache
     */
    public function logout()
    {
        $mySession = $this->getSession();
        $myConfig = $this->getConfig();

        $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $oUser->logout();

        // kill session
        $mySession->destroy();

        //resetting content cache if needed
        if ($myConfig->getConfigParam('blClearCacheOnLogout')) {
            $this->resetContentCache(true);
        }

        Registry::getUtils()->redirect('index.php', true, 302);
    }

    /**
     * Caches external url file locally, adds <base> tag with original url to load images and other links correcly
     */
    public function exturl()
    {
        $myUtils = Registry::getUtils();
        if ($sUrl = Registry::getConfig()->getRequestParameter("url")) {
            // Caching not allowed, redirecting
            $myUtils->redirect($sUrl, true, 302);
        }

        $myUtils->showMessageAndExit("");
    }

    /**
     * Every Time Admin starts we perform these checks
     * returns some messages if there is something to display
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "doStartUpChecks" in next major
     */
    protected function _doStartUpChecks() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $messages = [];

        if ($this->getConfig()->getConfigParam('blCheckSysReq') !== false) {
            // check if system requirements are ok
            $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);
            if (!$oSysReq->getSysReqStatus()) {
                $messages['warning'] = Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE');
                $messages['warning'] .= '<a href="?cl=sysreq&amp;stoken=' . $this->getSession()->getSessionChallengeToken() . '" target="basefrm">';
                $messages['warning'] .= Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
            }
        } else {
            $messages['message'] = Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE_INACTIVE');
            $messages['message'] .= '<a href="?cl=sysreq&amp;stoken=' . $this->getSession()->getSessionChallengeToken() . '" target="basefrm">';
            $messages['message'] .= Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
        }

        // version check
        if ($this->getConfig()->getConfigParam('blCheckForUpdates')) {
            if ($sVersionNotice = $this->_checkVersion()) {
                $messages['message'] .= $sVersionNotice;
            }
        }

        // check if setup dir is deleted
        if (file_exists($this->getConfig()->getConfigParam('sShopDir') . '/Setup/index.php')) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? "<br>" : '') . Registry::getLang()->translateString('SETUP_DIRNOTDELETED_WARNING');
        }

        // check if updateApp dir is deleted or empty
        $sUpdateDir = $this->getConfig()->getConfigParam('sShopDir') . '/updateApp/';
        if (file_exists($sUpdateDir) && !(count(glob("$sUpdateDir/*")) === 0)) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? "<br>" : '') . Registry::getLang()->translateString('UPDATEAPP_DIRNOTDELETED_WARNING');
        }

        // check if config file is writable
        $sConfPath = $this->getConfig()->getConfigParam('sShopDir') . "/config.inc.php";
        if (!is_readable($sConfPath) || is_writable($sConfPath)) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? "<br>" : '') . Registry::getLang()->translateString('SETUP_CONFIGPERMISSIONS_WARNING');
        }

        return $messages;
    }

    /**
     * Checks if newer shop version available. If true - returns message
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkVersion" in next major
     */
    protected function _checkVersion() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $edition = $this->getConfig()->getEdition();
        $query = 'http://admin.oxid-esales.com/' . $edition . '/onlinecheck.php?getlatestversion';
        $latestVersion = Registry::getUtilsFile()->readRemoteFileAsString($query);
        if ($latestVersion) {
            $currentVersion = $this->getConfig()->getVersion();
            if (version_compare($currentVersion, $latestVersion, '<')) {
                return sprintf(
                    Registry::getLang()->translateString('NAVIGATION_NEW_VERSION_AVAILABLE'),
                    $currentVersion,
                    $latestVersion
                );
            }
        }
    }

    public function canHaveRestrictedView()
    {
        $adminNaviRights = oxNew(AdminNaviRights::class);
        return $adminNaviRights->canHaveRestrictedView($this->getNavigation()->getDomXml());
    }

    public function canShowAllMenuItems()
    {
        $adminViewSettings = oxNew(AdminViewSetting::class);
        return $adminViewSettings->canShowAllMenuItems();
    }

    public function toggleAdminView()
    {
        $adminViewSettings = oxNew(AdminViewSetting::class);
        $adminViewSettings->toggleShowAllMenuItems();
        $this->addTplParam('doRedirect', true);
    }
}
