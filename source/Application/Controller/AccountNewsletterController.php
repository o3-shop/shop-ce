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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use oxRegistry;

/**
 * Current user newsletter manager.
 * When user is logged in in this manager window he can modify
 * his newletter subscription status - simply register or
 * unregister from newsletter. O3-Shop -> MY ACCOUNT -> Newsletter.
 */
class AccountNewsletterController extends \OxidEsales\Eshop\Application\Controller\AccountController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/newsletter.tpl';

    /**
     * @deprecated Property is not used and will be removed.
     * Whether the newsletter option had been changed.
     *
     * @var bool
     */
    protected $_blNewsletter = null;

    /**
     * Whether the newsletter option had been changed give some affirmation.
     *
     * @var integer
     */
    protected $_iSubscriptionStatus = 0;

    /**
     * If user is not logged in - returns name of template AccountNewsletterController::_sThisLoginTemplate, or if user
     * is already logged in - returns name of template AccountNewsletterController::_sThisTemplate
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        // is logged in ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return $this->_sThisTemplate = $this->_sThisLoginTemplate;
        }

        return $this->_sThisTemplate;
    }


    /**
     * Template variable getter. Returns 0 when newsletter had been changed.
     *
     * @return int
     */
    public function isNewsletter()
    {
        $oUser = $this->getUser();
        if (!$oUser) {
            return false;
        }

        return $oUser->getNewsSubscription()->getOptInStatus();
    }

    /**
     * Removes or adds user to newsletter group according to
     * current subscription status. Returns true on success.
     *
     * @return bool
     */
    public function subscribe()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return false;
        }

        // is logged in ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return false;
        }

        $iStatus = $this->getConfig()->getRequestParameter('status');
        if ($oUser->setNewsSubscription($iStatus, $this->getConfig()->getConfigParam('blOrderOptInEmail'))) {
            $this->_iSubscriptionStatus = ($iStatus == 0 && $iStatus !== null) ? -1 : 1;
        }
    }

    /**
     * Template variable getter. Returns 1 when newsletter had been changed to "yes"
     * else return -1 if had been changed to "no".
     *
     * @return integer
     */
    public function getSubscriptionStatus()
    {
        return $this->_iSubscriptionStatus;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];
        $oUtils = Registry::getUtilsUrl();
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $sSelfLink = $this->getViewConfig()->getSelfLink();

        $aPath['title'] = Registry::getLang()->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = Registry::getSeoEncoder()->getStaticUrl($sSelfLink . 'cl=account');
        $aPaths[] = $aPath;

        $aPath['title'] = Registry::getLang()->translateString('NEWSLETTER_SETTINGS', $iBaseLanguage, false);
        $aPath['link'] = $oUtils->cleanUrl($this->getLink(), ['fnc']);
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
