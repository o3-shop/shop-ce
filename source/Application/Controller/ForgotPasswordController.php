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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\InputValidator;
use OxidEsales\Eshop\Core\Registry;

/**
 * Password reminder page.
 * Collects top-article-list, bargain article list. There is a form with entry
 * field to enter login name (usually email). After user enters required
 * information and submits "Request Password" button mail is sent to users email.
 * O3-Shop -> MY ACCOUNT -> "Forgot your password? - click here."
 */
class ForgotPasswordController extends FrontendController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/forgotpwd.tpl';

    /**
     * Send forgot E-Mail.
     *
     * @var string
     */
    protected $_sForgotEmail = null;

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Update link expiration status
     *
     * @var bool
     */
    protected $_blUpdateLinkStatus = null;

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = true;

    /**
     * Executes oxemail::SendForgotPwdEmail() and sends login
     * password to user according to login name (email).
     *
     * Template variables:
     * <b>sendForgotMail</b>
     */
    public function forgotPassword()
    {
        $sEmail = Registry::getRequest()->getRequestEscapedParameter('lgn_usr');
        $this->_sForgotEmail = $sEmail;
        $oEmail = oxNew(Email::class);

        // problems sending passwd reminder ?
        $iSuccess = false;
        if ($sEmail) {
            $iSuccess = $oEmail->sendForgotPwdEmail($sEmail);
        }
        if ($iSuccess !== true) {
            $sError = ($iSuccess === false) ? 'ERROR_MESSAGE_PASSWORD_EMAIL_INVALID' : 'MESSAGE_NOT_ABLE_TO_SEND_EMAIL';
            Registry::getUtilsView()->addErrorToDisplay($sError);
            $this->_sForgotEmail = false;
        }
    }

    /**
     * Checks if password is fine and updates old one with new
     * password. On success user is redirected to success page
     *
     * @return string
     */
    public function updatePassword()
    {
        $sNewPass = Registry::getRequest()->getRequestEscapedParameter('password_new', true);
        $sConfPass = Registry::getRequest()->getRequestEscapedParameter('password_new_confirm', true);

        $oUser = oxNew(User::class);

        /** @var InputValidator $oInputValidator */
        $oInputValidator = Registry::getInputValidator();
        if (($oExcp = $oInputValidator->checkPassword($oUser, $sNewPass, $sConfPass, true))) {
            return Registry::getUtilsView()->addErrorToDisplay($oExcp->getMessage(), false, true);
        }

        // passwords are fine - updating and login user in
        if ($oUser->loadUserByUpdateId($this->getUpdateId())) {
            // setting new pass ...
            $oUser->setPassword($sNewPass);

            // resetting update pass params
            $oUser->setUpdateKey(true);

            // saving ..
            $oUser->save();

            // forcing user login
            Registry::getSession()->setVariable('usr', $oUser->getId());

            return 'forgotpwd?success=1';
        } else {
            // expired reminder
            $oUtilsView = Registry::getUtilsView();

            return $oUtilsView->addErrorToDisplay('ERROR_MESSAGE_PASSWORD_LINK_EXPIRED', false, true);
        }
    }

    /**
     * If user password update was successful - setting success status
     *
     * @return bool
     */
    public function updateSuccess()
    {
        return (bool) Registry::getRequest()->getRequestEscapedParameter('success');
    }

    /**
     * Notifies that password update form must be shown
     *
     * @return bool
     */
    public function showUpdateScreen()
    {
        return (bool) $this->getUpdateId();
    }

    /**
     * Returns special id used for password update functionality
     *
     * @return string
     */
    public function getUpdateId()
    {
        return Registry::getRequest()->getRequestEscapedParameter('uid');
    }

    /**
     * Returns password update link expiration status
     *
     * @return bool
     */
    public function isExpiredLink()
    {
        if (($sKey = $this->getUpdateId())) {
            $blExpired = oxNew(User::class)->isExpiredUpdateId($sKey);
        }

        return $blExpired;
    }

    /**
     * Template variable getter. Returns searched article list
     *
     * @return string
     */
    public function getForgotEmail()
    {
        return $this->_sForgotEmail;
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

        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('FORGOT_PASSWORD', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Get password reminder page title
     *
     * @return string
     */
    public function getTitle()
    {
        $sTitle = 'FORGOT_PASSWORD';

        if ($this->showUpdateScreen()) {
            $sTitle = 'NEW_PASSWORD';
        } elseif ($this->updateSuccess()) {
            $sTitle = 'CHANGE_PASSWORD';
        }

        return Registry::getLang()->translateString($sTitle, Registry::getLang()->getBaseLanguage(), false);
    }
}
