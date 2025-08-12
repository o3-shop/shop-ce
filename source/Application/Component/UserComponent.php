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

namespace OxidEsales\EshopCommunity\Application\Component;

use Exception;
use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User\UserShippingAddressUpdatableFields;
use OxidEsales\Eshop\Application\Model\User\UserUpdatableFields;
use OxidEsales\Eshop\Core\Contract\AbstractUpdatableFields;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Exception\ConnectionException;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\InputException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Form\FormFields;
use OxidEsales\Eshop\Core\Form\FormFieldsTrimmer;
use OxidEsales\Eshop\Core\Form\UpdatableFieldsConstructor;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Application\Model\User;
use Throwable;

// defining login/logout states
define('USER_LOGIN_SUCCESS', 1);
define('USER_LOGIN_FAIL', 2);
define('USER_LOGOUT', 3);

/**
 * User object manager.
 * Sets user details data, switches, logouts, logins user etc.
 *
 * @subpackage oxcmp
 */
class UserComponent extends BaseController
{
    /**
     * Boolean - if user is new or not.
     *
     * @var bool
     */
    protected $_blIsNewUser = false;

    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Newsletter subscription status
     *
     * @var bool
     */
    protected $_blNewsSubscriptionStatus = null;

    /**
     * User login state marker:
     *  - USER_LOGIN_SUCCESS - user successfully logged in;
     *  - USER_LOGIN_FAIL - login failed;
     *  - USER_LOGOUT - user logged out.
     *
     * @var int
     */
    protected $_iLoginStatus = null;

    /**
     * Terms/conditions version number
     *
     * @var string
     */
    protected $_sTermsVer = null;

    /**
     * View classes accessible for not logged in customers
     *
     * @var array
     */
    protected $_aAllowedClasses = [
        'register',
        'forgotpwd',
        'content',
        'account',
        'clearcookies',
        'oxwservicemenu',
        'oxwminibasket',
    ];

    /**
     * Sets oxcmp_oxuser::blIsComponent = true, fetches user error
     * code and sets it to default - 0. Executes parent::init().
     *
     * Session variable:
     * <b>usr_err</b>
     */
    public function init()
    {
        $this->saveDeliveryAddressState();
        $this->loadSessionUser();
        $this->saveInvitor();

        parent::init();
    }

    /**
     * Executes parent::render(), oxcmp_user::loadSessionUser(), loads user delivery
     * info. Returns user object oxcmp_user::oUser.
     *
     * @return  object  user object
     */
    public function render()
    {
        // checks if private sales allows further tasks
        $this->checkPsState();

        parent::render();

        return $this->getUser();
    }

    /**
     * If private sales enabled, checks:
     *  (1) if no session user and view can be accessed;
     *  (2) session user is available and accepted terms version matches actual version.
     * In case any condition is not satisfied redirects user to:
     *  (1) login page;
     *  (2) terms agreement page;
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkPsState" in next major
     */
    protected function _checkPsState() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->checkPsState();
    }

    /**
     * If private sales enabled, checks:
     *  (1) if no session user and view can be accessed;
     *  (2) session user is available and accepted terms version matches actual version.
     * In case any condition is not satisfied redirects user to:
     *  (1) login page;
     *  (2) terms agreement page;
     */
    protected function checkPsState()
    {
        $oConfig = Registry::getConfig();
        if ($this->getParent()->isEnabledPrivateSales()) {
            // load session user
            $oUser = $this->getUser();
            $sClass = $this->getParent()->getClassKey();

            // no session user
            if (!$oUser && !in_array($sClass, $this->_aAllowedClasses)) {
                Registry::getUtils()->redirect($oConfig->getShopHomeUrl() . 'cl=account', false, 302);
            }

            if ($oUser && !$oUser->isTermsAccepted() && !in_array($sClass, $this->_aAllowedClasses)) {
                Registry::getUtils()->redirect($oConfig->getShopHomeUrl() . 'cl=account&term=1', false, 302);
            }
        }
    }

    /**
     * Tries to load user ID from session.
     *
     * @return null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadSessionUser" in next major
     */
    protected function _loadSessionUser() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->loadSessionUser();
    }

    /**
     * Tries to load user ID from session.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function loadSessionUser()
    {
        $myConfig = Registry::getConfig();
        $oUser = $this->getUser();

        // no session user
        if (!$oUser) {
            return;
        }

        // this user is blocked, deny him
        if ($oUser->inGroup('oxidblocked')) {
            $sUrl = $myConfig->getShopHomeUrl() . 'cl=content&tpl=user_blocked.tpl';
            Registry::getUtils()->redirect($sUrl, true, 302);
        }

        // TODO: move this to a proper place
        if ($oUser->isLoadedFromCookie() && !$myConfig->getConfigParam('blPerfNoBasketSaving')) {
            if ($oBasket = Registry::getSession()->getBasket()) {
                $oBasket->load();
                $oBasket->onUpdate();
            }
        }
    }

    /**
     * Collects posted user information from posted variables ("lgn_usr",
     * "lgn_pwd", "lgn_cook"), executes \OxidEsales\Eshop\Application\Model\User::login() and checks if
     * such user exists.
     *
     * Session variables:
     * <b>usr</b>, <b>usr_err</b>
     *
     * Template variables:
     * <b>usr_err</b>
     *
     * @return  string  redirection string
     */
    public function login()
    {
        $sUser = Registry::getRequest()->getRequestEscapedParameter('lgn_usr');
        $sPassword = Registry::getRequest()->getRequestParameter('lgn_pwd');
        $sCookie = Registry::getRequest()->getRequestEscapedParameter('lgn_cook');

        $this->setLoginStatus(USER_LOGIN_FAIL);

        // trying to log in user
        try {
            /** @var \OxidEsales\Eshop\Application\Model\User $oUser */
            $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
            $oUser->login($sUser, $sPassword, $sCookie);
            $this->setLoginStatus(USER_LOGIN_SUCCESS);
        } catch (UserException $oEx) {
            // for login component send exception text to a custom component (if defined)
            Registry::getUtilsView()->addErrorToDisplay($oEx, false, true, '', false);

            return 'user';
        } catch (CookieException $oEx) {
            Registry::getUtilsView()->addErrorToDisplay($oEx);

            return 'user';
        }

        // finalizing ..
        return $this->afterLogin($oUser);
    }

    /**
     * Special functionality which is performed after user logs in (or user is created without pass).
     * Performs additional checking if user is not BLOCKED
     * (\OxidEsales\Eshop\Application\Model\User::InGroup("oxidblocked")) - if yes - redirects to blocked user
     * page ("cl=content&tpl=user_blocked.tpl").
     * Stores cookie info if user confirmed in login screen.
     * Then loads delivery info and forces basket to recalculate
     * (\OxidEsales\Eshop\Core\Session::getBasket() + oBasket::blCalcNeeded = true). Returns
     * "payment" to redirect to payment screen. If problems occurred loading
     * user - sets error code according problem, and returns "user" to redirect
     * to user info screen.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "afterLogin" in next major
     */
    protected function _afterLogin($oUser) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->afterLogin($oUser);
    }

    /**
     * Special functionality which is performed after user logs in (or user is created without pass).
     * Performs additional checking if user is not BLOCKED
     * (\OxidEsales\Eshop\Application\Model\User::InGroup("oxidblocked")) - if yes - redirects to blocked user
     * page ("cl=content&tpl=user_blocked.tpl").
     * Stores cookie info if user confirmed in login screen.
     * Then loads delivery info and forces basket to recalculate
     * (\OxidEsales\Eshop\Core\Session::getBasket() + oBasket::blCalcNeeded = true). Returns
     * "payment" to redirect to payment screen. If problems occurred loading
     * user - sets error code according problem, and returns "user" to redirect
     * to user info screen.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return string
     */
    protected function afterLogin($oUser)
    {
        $oSession = Registry::getSession();
        if ($oSession->isSessionStarted()) {
            $oSession->regenerateSessionId();
        }

        // this user is blocked, deny him
        if ($oUser->inGroup('oxidblocked')) {
            $sUrl = Registry::getConfig()->getShopHomeUrl() . 'cl=content&tpl=user_blocked.tpl';
            Registry::getUtils()->redirect($sUrl, true, 302);
        }

        // recalc basket
        if ($oBasket = $oSession->getBasket()) {
            $oBasket->onUpdate();
        }

        return 'payment';
    }

    /**
     * Executes oxcmp_user::login() method. After login user will not be
     * redirected to user or payment screens.
     */
    public function login_noredirect() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $blAgb = Registry::getRequest()->getRequestEscapedParameter('ord_agb');

        if ($this->getParent()->isEnabledPrivateSales() && $blAgb !== null && ($oUser = $this->getUser())) {
            if ($blAgb) {
                $oUser->acceptTerms();
            }
        } else {
            $this->login();

            if (!$this->isAdmin() && !Registry::getConfig()->getConfigParam('blPerfNoBasketSaving')) {
                //load basket from the database
                try {
                    if ($oBasket = Registry::getSession()->getBasket()) {
                        $oBasket->load();
                    }
                } catch (Exception $oE) {
                    //just ignore it
                }
            }
        }
    }

    /**
     * Special utility function which is executed right after
     * oxcmp_user::logout is called. Currently, it unsets such
     * session parameters as user chosen payment id, delivery
     * address id, active delivery set.
     * @deprecated underscore prefix violates PSR12, will be renamed to "afterLogout" in next major
     */
    protected function _afterLogout() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->afterLogout();
    }

    /**
     * Special utility function which is executed right after
     * oxcmp_user::logout is called. Currently, it unsets such
     * session parameters as user chosen payment id, delivery
     * address id, active delivery set.
     */
    protected function afterLogout()
    {
        Registry::getSession()->deleteVariable('paymentid');
        Registry::getSession()->deleteVariable('sShipSet');
        Registry::getSession()->deleteVariable('deladrid');
        Registry::getSession()->deleteVariable('dynvalue');

        // resetting & recalc basket
        if ($oBasket = Registry::getSession()->getBasket()) {
            $oBasket->resetUserInfo();
            $oBasket->onUpdate();

            // resetting voucher reservations
            if ($vouchers = $oBasket->getVouchers()) {
                foreach ($vouchers as $voucherId => $voucher) {
                    $oBasket->removeVoucher($voucherId);
                }
            }
        }

        Registry::getSession()->delBasket();
    }

    /**
     * Deletes user information from session:<br>
     * "usr", "dynvalue", "paymentid"<br>
     * also deletes cookie, unsets \OxidEsales\Eshop\Core\Config::oUser,
     * oxcmp_user::oUser, forces basket to recalculate.
     *
     * @return void|string
     */
    public function logout()
    {
        $myConfig = Registry::getConfig();
        $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);

        if ($oUser->logout()) {
            $this->setLoginStatus(USER_LOGOUT);

            // finalizing ..
            $this->afterLogout();

            $this->resetPermissions();

            if ($this->getParent()->isEnabledPrivateSales()) {
                return 'account';
            }

            // redirecting if user logs out in SSL mode
            if (Registry::getRequest()->getRequestEscapedParameter('redirect') && $myConfig->getConfigParam('sSSLShopURL')) {
                Registry::getUtils()->redirect($this->getLogoutLink());
            }
        }
    }

    /**
     * Any additional permission reset actions required on logout or change user actions
     */
    protected function resetPermissions()
    {
    }

    /**
     * Executes blUserRegistered = oxcmp_user::_changeUser_noRedirect().
     * if this returns true - returns "payment" (this redirects to
     * payment page), else returns blUserRegistered value.
     *
     * @see oxcmp_user::_changeUser_noRedirect()
     *
     * @return  string|bool    redirection string or true if user is registered, false otherwise
     */
    public function changeUser()
    {
        return ($this->changeUserWithoutRedirect() === true) ? 'payment' : false;
    }

    /**
     * Executes oxcmp_user::_changeuser_noredirect().
     * returns "account_user" (this redirects to billing and shipping settings page) on success
     *
     * @return void|string
     */
    public function changeuser_testvalues() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // skip updating user info if this is just form reload
        // on selecting delivery address
        // We do redirect only on success not to lose errors.

        if ($this->changeUserWithoutRedirect()) {
            return 'account_user';
        }
    }

    /**
     * First test if all required fields were filled, then performed
     * additional checking oxcmp_user::CheckValues(). If no errors
     * occurred - trying to create new user (\OxidEsales\Eshop\Application\Model\User::CreateUser()),
     * logging him to shop (\OxidEsales\Eshop\Application\Model\User::Login() if user has entered password).
     * If \OxidEsales\Eshop\Application\Model\User::CreateUser() returns false - this means user is
     * already created - we are only logging him to shop (oxcmp_user::Login()).
     * If there is any error with missing data - function will return
     * false and set error code (oxcmp_user::iError). If user was
     * created successfully - will return "payment" to redirect to
     * payment interface.
     *
     * Template variables:
     * <b>usr_err</b>
     *
     * Session variables:
     * <b>usr_err</b>, <b>usr</b>
     *
     * @return  mixed    redirection string or true if successful, false otherwise
     * @throws DatabaseErrorException
     * @throws StandardException
     */
    public function createUser()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');

            return false;
        }

        $blActiveLogin = $this->getParent()->isEnabledPrivateSales();

        $oConfig = Registry::getConfig();
        $oRequest = Registry::getRequest();

        if ($blActiveLogin && !$oRequest->getRequestEscapedParameter('ord_agb') && $oConfig->getConfigParam('blConfirmAGB')) {
            Registry::getUtilsView()->addErrorToDisplay('READ_AND_CONFIRM_TERMS', false, true);

            return false;
        }

        // collecting values to check
        $sUser = $oRequest->getRequestEscapedParameter('lgn_usr');

        // first pass
        $sPassword = $oRequest->getRequestParameter('lgn_pwd');

        // second pass
        $sPassword2 = $oRequest->getRequestParameter('lgn_pwd2');

        $aInvAddress = $oRequest->getRequestParameter('invadr');

        $aInvAddress = $this->cleanAddress($aInvAddress, oxNew(UserUpdatableFields::class));
        $aInvAddress = $this->trimAddress($aInvAddress);

        $aDelAddress = $this->getDelAddressData();
        $aDelAddress = $this->cleanAddress($aDelAddress, oxNew(UserShippingAddressUpdatableFields::class));
        $aDelAddress = $this->trimAddress($aDelAddress);

        try {
            /** @var \OxidEsales\Eshop\Application\Model\User $oUser */
            $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
            $oUser->checkValues($sUser, $sPassword, $sPassword2, $aInvAddress, $aDelAddress);

            $iActState = $blActiveLogin ? 0 : 1;

            // setting values
            $oUser->oxuser__oxusername = new Field($sUser, Field::T_RAW);
            $oUser->setPassword($sPassword);
            $oUser->oxuser__oxactive = new Field($iActState, Field::T_RAW);

            // used for checking if user email currently subscribed
            $iSubscriptionStatus = $oUser->getNewsSubscription()->getOptInStatus();

            $database = DatabaseProvider::getDb();
            $database->startTransaction();
            try {
                $oUser->createUser();
                $oUser = $this->configureUserBeforeCreation($oUser);
                $oUser->load($oUser->getId());
                $oUser->changeUserData(
                    $oUser->oxuser__oxusername->value,
                    $sPassword,
                    $sPassword,
                    $aInvAddress,
                    $aDelAddress
                );

                if ($blActiveLogin) {
                    // accepting terms...
                    $oUser->acceptTerms();
                }

                $database->commitTransaction();
            } catch (Exception $exception) {
                $database->rollbackTransaction();

                throw $exception;
            }

            $sUserId = Registry::getSession()->getVariable('su');
            $sRecEmail = Registry::getSession()->getVariable('re');
            if (Registry::getConfig()->getConfigParam('blInvitationsEnabled') && $sUserId && $sRecEmail) {
                // setting registration credit points...
                $oUser->setCreditPointsForRegistrant($sUserId, $sRecEmail);
            }

            // assigning to newsletter
            $blOptin = Registry::getRequest()->getRequestEscapedParameter('blnewssubscribed');
            if ($blOptin && $iSubscriptionStatus == 1) {
                // if user was assigned to newsletter
                // and is creating account with newsletter checked,
                // don't require confirm
                $oUser->getNewsSubscription()->setOptInStatus(1);
                $oUser->addToGroup('oxidnewsletter');
                $this->_blNewsSubscriptionStatus = 1;
            } else {
                $blOrderOptInEmailParam = Registry::getConfig()->getConfigParam('blOrderOptInEmail');
                $this->_blNewsSubscriptionStatus = $oUser->setNewsSubscription($blOptin, $blOrderOptInEmailParam);
            }

            $oUser->addToGroup('oxidnotyetordered');
            $oUser->logout();
        } catch (UserException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception, false, true);

            return false;
        } catch (InputException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception, false, true);

            return false;
        } catch (DatabaseConnectionException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception, false, true);

            return false;
        } catch (ConnectionException $exception) {
            Registry::getUtilsView()->addErrorToDisplay($exception, false, true);

            return false;
        }

        if (!$blActiveLogin) {
            Registry::getSession()->setVariable('usr', $oUser->getId());
            $this->afterLogin($oUser);

            // order remark
            //V #427: order remark for new users
            $sOrderRemark = Registry::getRequest()->getRequestParameter('order_remark');
            if ($sOrderRemark) {
                Registry::getSession()->setVariable('ordrem', $sOrderRemark);
            }
        }

        // send register eMail
        //TODO: move into user
        if ((int) Registry::getRequest()->getRequestEscapedParameter('option') == 3) {
            $oxEMail = oxNew(Email::class);
            if ($blActiveLogin) {
                $oxEMail->sendRegisterConfirmEmail($oUser);
            } else {
                $oxEMail->sendRegisterEmail($oUser);
            }
        }

        // new registered
        $this->_blIsNewUser = true;

        $sAction = 'payment?new_user=1&success=1';
        if ($this->_blNewsSubscriptionStatus !== null && !$this->_blNewsSubscriptionStatus) {
            $sAction = 'payment?new_user=1&success=1&newslettererror=4';
        }

        return $sAction;
    }

    /**
     * If any additional configurations required right before user creation
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user
     *
     * @return \OxidEsales\Eshop\Application\Model\User The user we gave in.
     */
    protected function configureUserBeforeCreation($user)
    {
        return $user;
    }

    /**
     * Creates new oxid user
     *
     * @return void|string
     * @throws DatabaseErrorException
     * @throws StandardException
     */
    public function registerUser()
    {
        // registered new user ?
        if ($this->createUser() && $this->_blIsNewUser) {
            if ($this->_blNewsSubscriptionStatus === null || $this->_blNewsSubscriptionStatus) {
                return 'register?success=1';
            } else {
                return 'register?success=1&newslettererror=4';
            }
        } else {
            // problems with registration ...
            $this->logout();
        }
    }

    /**
     * Deletes user shipping address.
     */
    public function deleteShippingAddress()
    {
        $request = oxNew(Request::class);
        $addressId = $request->getRequestParameter('oxaddressid');

        $address = oxNew(Address::class);
        $address->load($addressId);
        if ($this->canUserDeleteShippingAddress($address) && Registry::getSession()->checkSessionChallenge()) {
            $address->delete($addressId);
        }
    }

    /**
     * Checks if shipping address is assigned to user.
     *
     * @param Address $address
     * @return bool
     */
    private function canUserDeleteShippingAddress($address)
    {
        $canDelete = false;
        $user = $this->getUser();
        if ($address->oxaddress__oxuserid->value === $user->getId()) {
            $canDelete = true;
        }

        return $canDelete;
    }

    /**
     * Saves invitor ID
     * @deprecated underscore prefix violates PSR12, will be renamed to "saveInvitor" in next major
     */
    protected function _saveInvitor() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->saveInvitor();
    }

    /**
     * Saves invitor ID
     */
    protected function saveInvitor()
    {
        if (Registry::getConfig()->getConfigParam('blInvitationsEnabled')) {
            $this->getInvitor();
            $this->setRecipient();
        }
    }

    /**
     * Saving show/hide delivery address state
     * @deprecated underscore prefix violates PSR12, will be renamed to "saveDeliveryAddressState" in next major
     */
    protected function _saveDeliveryAddressState() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->saveDeliveryAddressState();
    }

    /**
     * Saving show/hide delivery address state
     */
    protected function saveDeliveryAddressState()
    {
        $oSession = Registry::getSession();

        $blShow = Registry::getRequest()->getRequestEscapedParameter('blshowshipaddress');
        if (!isset($blShow)) {
            $blShow = $oSession->getVariable('blshowshipaddress');
        }

        $oSession->setVariable('blshowshipaddress', $blShow);
    }

    /**
     * Mostly used for customer profile editing screen (O3-Shop ->
     * MY ACCOUNT). Checks if oUser is set (oxcmp_user::oUser) - if
     * not - executes oxcmp_user::loadSessionUser(). If user unchecked newsletter
     * subscription option - removes him from this group. There is an
     * additional MUST FILL fields checking. Function returns true or false
     * according to user data submission status.
     *
     * Session variables:
     * <b>ordrem</b>
     *
     * @deprecated since v6.0.0 (2017-02-27); Use changeUserWithoutRedirect().
     *
     * @return  bool true on success, false otherwise
     */
    protected function _changeUser_noRedirect() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps,PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->changeUserWithoutRedirect();
    }

    /**
     * Mostly used for customer profile editing screen (O3-Shop ->
     * MY ACCOUNT). Checks if oUser is set (oxcmp_user::oUser) - if
     * not - executes oxcmp_user::loadSessionUser(). If user unchecked newsletter
     * subscription option - removes him from this group. There is an
     * additional MUST FILL fields checking. Function returns true or false
     * according to user data submission status.
     *
     * Session variables:
     * <b>ordrem</b>
     *
     * @return  bool true on success, false otherwise
     */
    protected function changeUserWithoutRedirect()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return false;
        }

        // no user ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return false;
        }

        // collecting values to check
        $aDelAddress = $this->getDelAddressData();
        $aDelAddress = $this->cleanAddress($aDelAddress, oxNew(UserShippingAddressUpdatableFields::class));
        $aDelAddress = $this->trimAddress($aDelAddress);

        // if user company name, username and additional info has special chars
        $aInvAddress = Registry::getRequest()->getRequestParameter('invadr');
        $aInvAddress = $this->cleanAddress($aInvAddress, oxNew(UserUpdatableFields::class));
        $aInvAddress = $this->trimAddress($aInvAddress);

        $sUserName = $oUser->oxuser__oxusername->value;
        $sPassword = $sPassword2 = $oUser->oxuser__oxpassword->value;

        try {
            $newName = $aInvAddress['oxuser__oxusername'] ?? '';
            if (
                $this->isGuestUser($oUser)
                && $this->isUserNameUpdated($oUser->oxuser__oxusername->value ?? '', $newName)
            ) {
                $this->deleteExistingGuestUser($newName);
            }
            $oUser->changeUserData($sUserName, $sPassword, $sPassword2, $aInvAddress, $aDelAddress);
            // assigning to newsletter
            if (($blOptin = Registry::getRequest()->getRequestEscapedParameter('blnewssubscribed')) === null) {
                $blOptin = $oUser->getNewsSubscription()->getOptInStatus();
            }
            // check if email address changed, if so, force check newsletter subscription settings.
            $sBillingUsername = $aInvAddress['oxuser__oxusername'];
            $blForceCheckOptIn = ($sBillingUsername !== null && $sBillingUsername !== $sUserName);
            $blEmailParam = Registry::getConfig()->getConfigParam('blOrderOptInEmail');
            $this->_blNewsSubscriptionStatus = $oUser->setNewsSubscription($blOptin, $blEmailParam, $blForceCheckOptIn);
        } catch (UserException $oEx) { // errors in input
            // marking error code
            //TODO
            Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);

            return false;
        } catch (InputException $oEx) {
            Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);

            return false;
        } catch (ConnectionException $oEx) {
            //connection to external resource broken, change message and pass to the view
            Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);

            return false;
        } catch (Throwable $e) {
            Registry::getUtilsView()->addErrorToDisplay('ERROR_MESSAGE_USER_UPDATE_FAILED', false, true);
            return false;
        }

        $this->resetPermissions();

        // order remark
        $sOrderRemark = Registry::getRequest()->getRequestParameter('order_remark');

        if ($sOrderRemark) {
            Registry::getSession()->setVariable('ordrem', $sOrderRemark);
        } else {
            Registry::getSession()->deleteVariable('ordrem');
        }

        if ($oBasket = Registry::getSession()->getBasket()) {
            $oBasket->setBasketUser(null);
            $oBasket->onUpdate();
        }

        return true;
    }

    /**
     * Returns delivery address from request. Before returning array is checked if
     * all needed data is there
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDelAddressData" in next major
     */
    protected function _getDelAddressData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getDelAddressData();
    }

    /**
     * Returns delivery address from request. Before returning array is checked if
     * all needed data is there
     *
     * @return array
     */
    protected function getDelAddressData()
    {
        // if user company name, username and additional info has special chars
        $blShowShipAddressParameter = Registry::getRequest()->getRequestEscapedParameter('blshowshipaddress');
        $blShowShipAddressVariable = Registry::getSession()->getVariable('blshowshipaddress');
        $sDeliveryAddressParameter = Registry::getRequest()->getRequestParameter('deladr');
        $aDeladr = ($blShowShipAddressParameter || $blShowShipAddressVariable) ? $sDeliveryAddressParameter : [];
        $aDelAddress = $aDeladr;

        if (is_array($aDeladr)) {
            // checking if data is filled
            if (isset($aDeladr['oxaddress__oxsal'])) {
                unset($aDeladr['oxaddress__oxsal']);
            }
            if (!count($aDeladr) || implode('', $aDeladr) == '') {
                // resetting to avoid empty records
                $aDelAddress = [];
            }
        }

        return $aDelAddress;
    }

    /**
     * Returns logout link with additional params
     *
     * @return string $sLogoutLink
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLogoutLink" in next major
     */
    protected function _getLogoutLink() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLogoutLink();
    }

    /**
     * Returns logout link with additional params
     *
     * @return string $sLogoutLink
     */
    protected function getLogoutLink()
    {
        $oConfig = Registry::getConfig();
        $oRequest = Registry::getRequest();

        $sLogoutLink = $oConfig->isSsl() ? $oConfig->getShopSecureHomeUrl() : $oConfig->getShopHomeUrl();
        $sLogoutLink .= 'cl=' . $oConfig->getRequestControllerId() . $this->getParent()->getDynUrlParams();
        if ($sParam = $oRequest->getRequestEscapedParameter('anid')) {
            $sLogoutLink .= '&amp;anid=' . $sParam;
        }
        if ($sParam = $oRequest->getRequestEscapedParameter('cnid')) {
            $sLogoutLink .= '&amp;cnid=' . $sParam;
        }
        if ($sParam = $oRequest->getRequestEscapedParameter('mnid')) {
            $sLogoutLink .= '&amp;mnid=' . $sParam;
        }
        if ($sParam = basename($oRequest->getRequestEscapedParameter('tpl'))) {
            $sLogoutLink .= '&amp;tpl=' . $sParam;
        }
        if ($sParam = $oRequest->getRequestEscapedParameter('oxloadid')) {
            $sLogoutLink .= '&amp;oxloadid=' . $sParam;
        }
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($sParam = $oRequest->getRequestEscapedParameter('recommid')) {
            $sLogoutLink .= '&amp;recommid=' . $sParam;
        }
        // END deprecated

        return $sLogoutLink . '&amp;fnc=logout';
    }

    /**
     * Sets user login state
     *
     * @param int $iStatus login state (USER_LOGIN_SUCCESS/USER_LOGIN_FAIL/USER_LOGOUT)
     */
    public function setLoginStatus($iStatus)
    {
        $this->_iLoginStatus = $iStatus;
    }

    /**
     * Returns user login state marker:
     *  - USER_LOGIN_SUCCESS - user successfully logged in;
     *  - USER_LOGIN_FAIL - login failed;
     *  - USER_LOGOUT - user logged out.
     *
     * @return int
     */
    public function getLoginStatus()
    {
        return $this->_iLoginStatus;
    }

    /**
     * Sets invitor id to session from URL
     */
    public function getInvitor()
    {
        $sSu = Registry::getSession()->getVariable('su');

        if (!$sSu && ($sSuNew = Registry::getRequest()->getRequestEscapedParameter('su'))) {
            Registry::getSession()->setVariable('su', $sSuNew);
        }
    }

    /**
     * sets from URL invitor id
     */
    public function setRecipient()
    {
        $sRe = Registry::getSession()->getVariable('re');
        if (!$sRe && ($sReNew = Registry::getRequest()->getRequestEscapedParameter('re'))) {
            Registry::getSession()->setVariable('re', $sReNew);
        }
    }

    /**
     * @param array                   $address
     * @param AbstractUpdatableFields $updatableFields
     *
     * @return array
     */
    private function cleanAddress($address, $updatableFields)
    {
        if (is_array($address)) {
            /** @var UpdatableFieldsConstructor $updatableFieldsConstructor */
            $updatableFieldsConstructor = oxNew(UpdatableFieldsConstructor::class);
            $cleaner = $updatableFieldsConstructor->getAllowedFieldsCleaner($updatableFields);
            return $cleaner->filterByUpdatableFields($address);
        }

        return $address;
    }

    /**
     * Returns trimmed address.
     *
     * @param array $address
     *
     * @return array
     */
    private function trimAddress($address)
    {
        if (is_array($address)) {
            $fields = oxNew(FormFields::class, $address);
            $trimmer = oxNew(FormFieldsTrimmer::class);

            $address = (array)$trimmer->trim($fields);
        }

        return $address;
    }

    /**
     * @param User $user
     * @return bool
     */
    private function isGuestUser(User $user): bool
    {
        return empty($user->oxuser__oxpassword->value);
    }

    /**
     * @param string $currentName
     * @param string $newName
     * @return bool
     */
    private function isUserNameUpdated(string $currentName, string $newName): bool
    {
        return $currentName && $newName && $currentName !== $newName;
    }

    /**
     * @param string $newName
     * @throws Exception
     */
    private function deleteExistingGuestUser(string $newName): void
    {
        $existingUser = oxNew(User::class);
        $existingUser->load($existingUser->getIdByUserName($newName));
        if ($this->isGuestUser($existingUser)) {
            $existingUser->delete();
        }
    }
}
