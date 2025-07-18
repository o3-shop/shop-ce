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

use Exception;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\InputValidator;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\UtilsView;

/**
 * Current user password change form.
 * When user is logged in he may change his Billing and Shipping
 * information (this is important for ordering purposes).
 * Information as email, password, greeting, name, company, address,
 * etc. Some fields must be entered. O3-Shop -> MY ACCOUNT
 * -> Update your billing and delivery settings.
 */
class AccountPasswordController extends AccountController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/password.tpl';

    /**
     * Whether the password had been changed.
     *
     * @var bool
     */
    protected $_blPasswordChanged = false;

    /**
     * If user is not logged in - returns name of template AccountUserController::_sThisLoginTemplate, or if user is
     * already logged in additionally loads user delivery address info and forms country list. Returns name of template
     * AccountUserController::_sThisTemplate
     *
     * @return string $_sThisTemplate current template file name
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
     * changes current user password
     *
     * @return void
     * @throws Exception
     */
    public function changePassword()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        $oUser = $this->getUser();
        if (!$oUser) {
            return;
        }

        $sOldPass = Registry::getRequest()->getRequestEscapedParameter('password_old', true);
        $sNewPass = Registry::getRequest()->getRequestEscapedParameter('password_new', true);
        $sConfPass = Registry::getRequest()->getRequestEscapedParameter('password_new_confirm', true);

        /** @var InputValidator $oInputValidator */
        $oInputValidator = Registry::getInputValidator();
        if (($oExcp = $oInputValidator->checkPassword($oUser, $sNewPass, $sConfPass, true))) {
            switch ($oExcp->getMessage()) {
                case Registry::getLang()->translateString('ERROR_MESSAGE_INPUT_EMPTYPASS'):
                case Registry::getLang()->translateString('ERROR_MESSAGE_PASSWORD_TOO_SHORT'):
                    return Registry::getUtilsView()->addErrorToDisplay(
                        'ERROR_MESSAGE_PASSWORD_TOO_SHORT',
                        false,
                        true
                    );
                default:
                    return Registry::getUtilsView()->addErrorToDisplay(
                        'ERROR_MESSAGE_PASSWORD_DO_NOT_MATCH',
                        false,
                        true
                    );
            }
        }

        if (!$sOldPass || !$oUser->isSamePassword($sOldPass)) {
            /** @var UtilsView $oUtilsView */
            $oUtilsView = Registry::getUtilsView();

            return $oUtilsView->addErrorToDisplay('ERROR_MESSAGE_CURRENT_PASSWORD_INVALID', false, true);
        }

        // testing passed - changing password
        $oUser->setPassword($sNewPass);
        if ($oUser->save()) {
            $this->_blPasswordChanged = true;
            // deleting user autologin cookies.
            Registry::getUtilsServer()->deleteUserCookie(Registry::getConfig()->getShopId());
        }
    }

    /**
     * Template variable getter. Returns true when password had been changed.
     *
     * @return bool
     */
    public function isPasswordChanged()
    {
        return $this->_blPasswordChanged;
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

        /** @var SeoEncoder $oSeoEncoder */
        $oSeoEncoder = Registry::getSeoEncoder();
        $oLang = Registry::getLang();
        $iBaseLanguage = $oLang->getBaseLanguage();
        $aPath['title'] = $oLang->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = $oSeoEncoder->getStaticUrl($this->getViewConfig()->getSelfLink() . 'cl=account');
        $aPaths[] = $aPath;

        $aPath['title'] = $oLang->translateString('CHANGE_PASSWORD', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
