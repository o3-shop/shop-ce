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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\EshopCommunity\Core\AdminNaviRights;
use OxidEsales\EshopCommunity\Core\AdminViewSetting;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck\UpdateCheckServiceInterface;

/**
 * Administrator GUI navigation manager class.
 */
class NavigationController extends AdminController
{
    /**
     * Executes parent method parent::render(), generates menu HTML code,
     * passes data to Smarty engine, returns name of template file "nav_frame.tpl".
     *
     * @return string
     * @throws Exception
     */
    public function render()
    {
        parent::render();

        // Make the most recent cached UpdateCheckResult available to all
        // NavigationController renders — including header.tpl, which has no
        // path of its own to UpdateCheckService and needs to know whether
        // the providers were reachable last time we ran a check (to decide
        // whether to render the manual re-check icon). _doStartUpChecks()
        // (called below for home.tpl) may overwrite this with a freshly
        // fetched result.
        $cachedUpdateCheckResult = $this->getUpdateCheckService()->getCachedResult();
        if ($cachedUpdateCheckResult !== null) {
            $this->_aViewData['updateCheckResult'] = $cachedUpdateCheckResult;
        }

        $sItem = Registry::getRequest()->getRequestEscapedParameter('item');
        $sItem = $sItem ? basename($sItem) : false;
        if (!$sItem) {
            $sItem = 'nav_frame.tpl';
        } else {
            $oNavTree = $this->getNavigation();

            // set menu structure
            $this->_aViewData['menustructure'] = $oNavTree->getDomXml()->documentElement->childNodes;

            // version patch string
            $this->_aViewData['sVersion'] = oxNew(ShopVersion::class)->getVersion();

            //checking requirements if this is not nav frame reload
            if (!Registry::getRequest()->getRequestEscapedParameter('navReload')) {
                // #661 execute stuff we run each time when we start admin once
                if ('home.tpl' == $sItem) {
                    $this->_aViewData['aMessage'] = $this->_doStartUpChecks();
                }
            } else {
                //removing reload param to force requirements checking next time
                Registry::getSession()->deleteVariable('navReload');
            }
        }

        $blisMallAdmin = Registry::getSession()->getVariable('malladmin');
        $oShoplist = oxNew(\OxidEsales\Eshop\Application\Model\ShopList::class);
        if (!$blisMallAdmin) {
            // we only allow to see our shop
            $iShopId = Registry::getSession()->getVariable('actshop');
            $oShop = oxNew(Shop::class);
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
        $this->_aViewData['listview'] = Registry::getRequest()->getRequestEscapedParameter('listview');
        $this->_aViewData['editview'] = Registry::getRequest()->getRequestEscapedParameter('editview');
        $this->_aViewData['actedit'] = Registry::getRequest()->getRequestEscapedParameter('actedit');
    }

    /**
     * Destroy session, redirects to admin login and clears cache
     */
    public function logout()
    {
        $mySession = Registry::getSession();
        $myConfig = Registry::getConfig();

        $oUser = oxNew(User::class);
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
     * Caches external url file locally, adds <base> tag with original url to load images and other links correctly
     */
    public function exturl()
    {
        $myUtils = Registry::getUtils();
        if ($sUrl = Registry::getRequest()->getRequestEscapedParameter('url')) {
            // Caching not allowed, redirecting
            $myUtils->redirect($sUrl, true, 302);
        }

        $myUtils->showMessageAndExit('');
    }

    /**
     * Every Time Admin starts we perform these checks
     * returns some messages if there is something to display
     *
     * @return array
     * @throws Exception
     * @deprecated Transitional during #107. Modules SHOULD override _doStartUpChecks()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes doStartUpChecks() to the canonical override
      *             target and retires _doStartUpChecks(); until then, _doStartUpChecks() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _doStartUpChecks() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $messages = [];

        if (!empty(Registry::getConfig()->getConfigParam('blCheckSysReq', true))) {
            // check if system requirements are ok
            $oSysReq = oxNew(\OxidEsales\Eshop\Core\SystemRequirements::class);
            if (!$oSysReq->getSysReqStatus()) {
                $messages['warning'] = Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE');
                $messages['warning'] .= '<a href="?cl=sysreq&amp;stoken=' . Registry::getSession()->getSessionChallengeToken() . '" target="basefrm">';
                $messages['warning'] .= Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
            }
        } else {
            $messages['message'] = Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE_INACTIVE');
            $messages['message'] .= '<a href="?cl=sysreq&amp;stoken=' . Registry::getSession()->getSessionChallengeToken() . '" target="basefrm">';
            $messages['message'] .= Registry::getLang()->translateString('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
        }

        // version check via UpdateCheckService
        $forceUpdateCheck = (bool) Registry::getRequest()->getRequestEscapedParameter('forceUpdateCheck');
        if ($forceUpdateCheck || Registry::getConfig()->getConfigParam('blCheckForUpdates')) {
            $updateCheckResult = $this->getUpdateCheckService()->check($forceUpdateCheck);
            $this->_aViewData['updateCheckResult'] = $updateCheckResult;
        }

        // check if setup dir is deleted
        if (file_exists(Registry::getConfig()->getConfigParam('sShopDir') . '/Setup/index.php')) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? '<br>' : '') . Registry::getLang()->translateString('SETUP_DIRNOTDELETED_WARNING');
        }

        // check if updateApp dir is deleted or empty
        $sUpdateDir = Registry::getConfig()->getConfigParam('sShopDir') . '/updateApp/';
        if (file_exists($sUpdateDir) && !(count(glob("$sUpdateDir/*")) === 0)) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? '<br>' : '') . Registry::getLang()->translateString('UPDATEAPP_DIRNOTDELETED_WARNING');
        }

        // check if config file is writable
        $sConfPath = Registry::getConfig()->getConfigParam('sShopDir') . '/config.inc.php';
        if (!is_readable($sConfPath) || is_writable($sConfPath)) {
            $messages['warning'] .= ((!empty($messages['warning'])) ? '<br>' : '') . Registry::getLang()->translateString('SETUP_CONFIGPERMISSIONS_WARNING');
        }

        return $messages;
    }

    /**
     * Every Time Admin starts we perform these checks
     * returns some messages if there is something to display
     *
     * @return array
     * @throws Exception
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _doStartUpChecks(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make doStartUpChecks() the canonical override target.
     */
    protected function doStartUpChecks()
    {
        return $this->_doStartUpChecks();
    }

    /**
     * Checks if newer shop version available. If true - returns message
     *
     * @return string
     * @throws Exception
     * @deprecated Transitional during #107. Modules SHOULD override _checkVersion()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes checkVersion() to the canonical override
      *             target and retires _checkVersion(); until then, _checkVersion() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _checkVersion() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $result = $this->getUpdateCheckService()->check();

        if ($result->isCoreUpdateAvailable()) {
            $currentVersion = oxNew(ShopVersion::class)->getVersion();
            return sprintf(
                Registry::getLang()->translateString('NAVIGATION_NEW_VERSION_AVAILABLE'),
                $currentVersion,
                $result->getLatestCoreVersion()
            );
        }
    }

    /**
     * Checks if newer shop version available. If true - returns message
     *
     * @return string|void
     * @throws Exception
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _checkVersion(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make checkVersion() the canonical override target.
     */
    protected function checkVersion()
    {
        return $this->_checkVersion();
    }

    /**
     * @return UpdateCheckServiceInterface
     */
    protected function getUpdateCheckService(): UpdateCheckServiceInterface
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(UpdateCheckServiceInterface::class);
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
