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
use OxidEsales\Eshop\Application\Controller\CompareController;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsUrl;

/**
 * Current user "My account" window.
 * When user is logged in arranges "My account" window, by creating
 * links to user details, order review, notice list, wish list. There
 * is a link for logging out. Template includes Topoffer , bargain
 * boxes. O3-Shop -> MY ACCOUNT.
 */
class AccountController extends FrontendController
{
    /**
     * Number of user's orders.
     *
     * @var integer
     */
    protected $_iOrderCnt = null;

    /**
     * Current article id.
     *
     * @var string
     */
    protected $_sArticleId = null;

    /**
     * Search parameter for Html
     *
     * @var string
     */
    protected $_sSearchParamForHtml = null;

    /**
     * Search parameter
     *
     * @var string
     */
    protected $_sSearchParam = null;

    /**
     * List type
     *
     * @var string
     */
    protected $_sListType = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/dashboard.tpl';

    /**
     * Current class login template name.
     *
     * @var string
     */
    protected $_sThisLoginTemplate = 'page/account/login.tpl';

    /**
     * Alternative login template name.
     *
     * @var string
     */
    protected $_sThisAltLoginTemplate = 'page/privatesales/login.tpl';

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_sMetaDescriptionIdent = 'oxstartmetadescription';

    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_sMetaKeywordsIdent = 'oxstartmetakeywords';

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = true;

    /**
     * Status of the account deletion
     *
     * @var bool
     */
    private $accountDeletionStatus;

    /**
     * Loads action articles. If user is logged and returns name of
     * template to render account::_sThisTemplate
     *
     * @return  string  $_sThisTemplate current template file name
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        // performing redirect if needed
        $this->redirectAfterLogin();

        // is logged in ?
        $user = $this->getUser();
        $passwordField = 'oxuser__oxpassword';
        if (
            !$user || !$user->$passwordField->value ||
            ($this->isEnabledPrivateSales() && (!$user->isTermsAccepted() || $this->confirmTerms()))
        ) {
            $this->_sThisTemplate = $this->getLoginTemplate();
        }

        return $this->_sThisTemplate;
    }

    /**
     * Returns login template name:
     *  - if "login" feature is on returns $this->_sThisAltLoginTemplate
     *  - else returns $this->_sThisLoginTemplate
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLoginTemplate" in next major
     */
    protected function _getLoginTemplate() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLoginTemplate();
    }

    /**
     * Returns login template name:
     *  - if "login" feature is on returns $this->_sThisAltLoginTemplate
     *  - else returns $this->_sThisLoginTemplate
     *
     * @return string
     */
    protected function getLoginTemplate()
    {
        return $this->isEnabledPrivateSales() ? $this->_sThisAltLoginTemplate : $this->_sThisLoginTemplate;
    }

    /**
     * Confirms term agreement. Returns value of confirmed term
     *
     * @return string | bool
     * @throws DatabaseConnectionException
     */
    public function confirmTerms()
    {
        $termsConfirmation = Registry::getRequest()->getRequestEscapedParameter('term');
        if (!$termsConfirmation && $this->isEnabledPrivateSales()) {
            $user = $this->getUser();
            if ($user && !$user->isTermsAccepted()) {
                $termsConfirmation = true;
            }
        }

        return $termsConfirmation;
    }

    /**
     * Returns array from parent::getNavigationParams(). If current request
     * contains "sourcecl" and "anid" parameters - appends array with this
     * data. Array is used to fill forms and append shop urls with actual
     * state parameters
     *
     * @return array
     */
    public function getNavigationParams()
    {
        $parameters = parent::getNavigationParams();

        if ($sourceClass = Registry::getRequest()->getRequestEscapedParameter('sourcecl')) {
            $parameters['sourcecl'] = $sourceClass;
        }

        if ($articleId = Registry::getRequest()->getRequestEscapedParameter('anid')) {
            $parameters['anid'] = $articleId;
        }

        return $parameters;
    }

    /**
     * For some user actions (like writing product
     * review) user must be logged in. So e.g. in product details page
     * there is a link leading to current view. Link contains parameter
     * "sourcecl", which tells where to redirect after successful login.
     * If this parameter is defined and oxcmp_user::getLoginStatus() ==
     * USER_LOGIN_SUCCESS (means user has just logged in) then user is
     * redirected back to source view.
     *
     * @return void
     */
    public function redirectAfterLogin()
    {
        // in case source class is provided - redirecting back to it with all default parameters
        if (
            ($sourceClass = Registry::getRequest()->getRequestEscapedParameter('sourcecl')) &&
            $this->_oaComponents['oxcmp_user']->getLoginStatus() === USER_LOGIN_SUCCESS
        ) {
            $redirectUrl = Registry::getConfig()->getShopUrl() . 'index.php?cl=' . rawurlencode($sourceClass);

            // building redirect link
            foreach ($this->getNavigationParams() as $key => $value) {
                if ($value && $key != "sourcecl") {
                    $redirectUrl .= '&' . rawurlencode($key) . "=" . rawurlencode($value);
                }
            }

            /** @var UtilsUrl $utilsUrl */
            $utilsUrl = Registry::getUtilsUrl();
            return Registry::getUtils()->redirect($utilsUrl->processUrl($redirectUrl), true, 302);
        }
    }

    /**
     * changes default template for compare in popup
     *
     * @return null
     * @throws DatabaseConnectionException
     */
    public function getOrderCnt()
    {
        if ($this->_iOrderCnt === null) {
            $this->_iOrderCnt = 0;
            if ($user = $this->getUser()) {
                $this->_iOrderCnt = $user->getOrderCount();
            }
        }

        return $this->_iOrderCnt;
    }

    /**
     * Return the active article id
     *
     * @return string | bool
     */
    public function getArticleId()
    {
        if ($this->_sArticleId === null) {
            // passing wishlist information
            if ($articleId = Registry::getRequest()->getRequestEscapedParameter('aid')) {
                $this->_sArticleId = $articleId;
            }
        }

        return $this->_sArticleId;
    }

    /**
     * Template variable getter. Returns search parameter for Html
     *
     * @return string
     */
    public function getSearchParamForHtml()
    {
        if ($this->_sSearchParamForHtml === null) {
            $this->_sSearchParamForHtml = false;
            if ($this->getArticleId()) {
                $this->_sSearchParamForHtml = Registry::getRequest()->getRequestEscapedParameter('searchparam');
            }
        }

        return $this->_sSearchParamForHtml;
    }

    /**
     * Template variable getter. Returns search parameter
     *
     * @return string
     */
    public function getSearchParam()
    {
        if ($this->_sSearchParam === null) {
            $this->_sSearchParam = false;
            if ($this->getArticleId()) {
                $this->_sSearchParam = rawurlencode(Registry::getRequest()->getRequestEscapedParameter('searchparam', true));
            }
        }

        return $this->_sSearchParam;
    }

    /**
     * Template variable getter. Returns list type
     *
     * @return string
     */
    public function getListType()
    {
        if ($this->_sListType === null) {
            $this->_sListType = false;
            if ($this->getArticleId()) {
                // searching in vendor #671
                $this->_sListType = Registry::getRequest()->getRequestEscapedParameter('listtype');
            }
        }

        return $this->_sListType;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $paths = [];
        $pathData = [];
        $language = Registry::getLang();
        $baseLanguageId = $language->getBaseLanguage();
        if ($user = $this->getUser()) {
            $username = $user->oxuser__oxusername->value;
            $pathData['title'] = $language->translateString('MY_ACCOUNT', $baseLanguageId, false) . " - " . $username;
        } else {
            $pathData['title'] = $language->translateString('LOGIN', $baseLanguageId, false);
        }
        $pathData['link'] = $this->getLink();
        $paths[] = $pathData;

        return $paths;
    }

    /**
     * Template variable getter. Returns article list count in comparison
     *
     * @return integer
     */
    public function getCompareItemsCnt()
    {
        $compare = oxNew(CompareController::class);

        return $compare->getCompareItemsCnt();
    }

    /**
     * Page Title
     *
     * @return string
     */
    public function getTitle()
    {
        $title = parent::getTitle();

        if (Registry::getConfig()->getActiveView()->getClassKey() == 'account') {
            $baseLanguageId = Registry::getLang()->getBaseLanguage();
            $title = Registry::getLang()->translateString('PAGE_TITLE_ACCOUNT', $baseLanguageId, false);
            if ($user = $this->getUser()) {
                $username = $user->oxuser__oxusername->value;
                $title .= ' - "' . $username . '"';
            }
        }

        return $title;
    }

    /**
     * Deletes User account.
     */
    public function deleteAccount()
    {
        $this->accountDeletionStatus = false;
        $user = $this->getUser();

        /**
         * Setting derived to false allows mall users to delete their account being in a different shop as the shop
         * the account was originally created in.
         */
        if (Registry::getConfig()->getConfigParam('blMallUsers')) {
            $user->setIsDerived(false);
        }

        if ($this->canUserAccountBeDeleted() && $user->delete()) {
            $this->accountDeletionStatus = true;
            $user->logout();
            $session = Registry::getSession();
            $session->destroy();
        }
    }

    /**
     * Returns true if User is allowed to delete own account.
     *
     * @return bool
     */
    public function isUserAllowedToDeleteOwnAccount()
    {
        $allowUsersToDeleteTheirAccount = Registry::getConfig()->getConfigParam('blAllowUsersToDeleteTheirAccount');

        $user = $this->getUser();

        return $allowUsersToDeleteTheirAccount && $user && !$user->isMallAdmin();
    }

    /**
     * Template variable getter. Returns true, if a user account has been successfully deleted, else false.
     *
     * @return bool
     */
    public function getAccountDeletionStatus()
    {
        return $this->accountDeletionStatus;
    }

    /**
     * Checks if possible to delete user.
     *
     * @return bool
     */
    private function canUserAccountBeDeleted()
    {
        return Registry::getSession()->checkSessionChallenge() && $this->isUserAllowedToDeleteOwnAccount();
    }
}
