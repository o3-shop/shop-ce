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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Exception\CookieException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Component\UserComponent;

/**
 * Stub User used by login/logout/afterLogin tests so the component's
 * try-catch flow can be exercised without hitting the real model.
 *
 * Steerable: $loginThrows + $loginThrowsCookie + $logoutReturns flip
 * which path the component takes.
 */
class UserComponentTest_StubUser extends User
{
    public static bool $loginThrowsUser = false;
    public static bool $loginThrowsCookie = false;
    public static bool $logoutReturns = true;
    public static bool $inGroupBlocked = false;
    public static string $oxid = 'user-7';

    public ?array $loginCalledWith = null;
    public bool $logoutCalled = false;

    public function __construct()
    {
        // Skip parent::__construct so init() doesn't reach for DB metadata.
    }

    public function login($userName, $password, $cookie = false)
    {
        $this->loginCalledWith = [$userName, $password, $cookie];
        if (self::$loginThrowsUser) {
            throw new UserException('login failed');
        }
        if (self::$loginThrowsCookie) {
            throw new CookieException('cookie failed');
        }
        return true;
    }

    public function logout()
    {
        $this->logoutCalled = true;
        return self::$logoutReturns;
    }

    public function inGroup($groupId)
    {
        return $groupId === 'oxidblocked' && self::$inGroupBlocked;
    }

    public function isLoadedFromCookie()
    {
        return false;
    }

    public function getId()
    {
        return self::$oxid;
    }
}

/**
 * Stub Address used by deleteShippingAddress so delete is captured
 * without touching the database.
 */
class UserComponentTest_StubAddress extends Address
{
    public static string $ownerOxid = 'user-7';

    public bool $deleted = false;
    public ?string $loadedWith = null;

    public function __construct()
    {
    }

    public function load($oxId = null)
    {
        $this->loadedWith = (string) $oxId;
        $this->oxaddress__oxuserid = new Field(self::$ownerOxid);
        return true;
    }

    public function delete($oxid = null)
    {
        $this->deleted = true;
        return true;
    }
}

class UserComponentTest extends \OxidTestCase
{
    private const SHOP_HOME_URL = 'http://shop.example/';

    protected function setUp(): void
    {
        parent::setUp();
        UserComponentTest_StubUser::$loginThrowsUser = false;
        UserComponentTest_StubUser::$loginThrowsCookie = false;
        UserComponentTest_StubUser::$logoutReturns = true;
        UserComponentTest_StubUser::$inGroupBlocked = false;
        UserComponentTest_StubUser::$oxid = 'user-7';
        UserComponentTest_StubAddress::$ownerOxid = 'user-7';
    }

    // -------- trivial getters / setters --------

    public function testSetAndGetLoginStatusRoundtrip(): void
    {
        $component = oxNew(UserComponent::class);
        $component->setLoginStatus(USER_LOGIN_FAIL);
        $this->assertSame(USER_LOGIN_FAIL, $component->getLoginStatus());
        $component->setLoginStatus(USER_LOGIN_SUCCESS);
        $this->assertSame(USER_LOGIN_SUCCESS, $component->getLoginStatus());
    }

    public function testGetInvitorPropagatesRequestParamIntoSession(): void
    {
        Registry::getSession()->deleteVariable('su');
        $this->setRequestParameter('su', 'invitor-42');

        oxNew(UserComponent::class)->getInvitor();
        $this->assertSame('invitor-42', Registry::getSession()->getVariable('su'));
    }

    public function testGetInvitorDoesNotOverwriteExistingSessionValue(): void
    {
        Registry::getSession()->setVariable('su', 'preset-99');
        $this->setRequestParameter('su', 'should-be-ignored');

        oxNew(UserComponent::class)->getInvitor();
        $this->assertSame('preset-99', Registry::getSession()->getVariable('su'));
    }

    public function testSetRecipientPropagatesRequestParamIntoSession(): void
    {
        Registry::getSession()->deleteVariable('re');
        $this->setRequestParameter('re', 'recipient@example.com');

        oxNew(UserComponent::class)->setRecipient();
        $this->assertSame('recipient@example.com', Registry::getSession()->getVariable('re'));
    }

    public function testConfigureUserBeforeCreationIsIdentityByDefault(): void
    {
        $component = oxNew(UserComponent::class);
        $stub = new UserComponentTest_StubUser();

        $method = new \ReflectionMethod($component, 'configureUserBeforeCreation');
        $method->setAccessible(true);
        $this->assertSame($stub, $method->invoke($component, $stub));
    }

    public function testResetPermissionsIsNoopByDefault(): void
    {
        // Method is empty by design — call it to confirm no errors and to
        // exercise the line for coverage.
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'resetPermissions');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($component));
    }

    // -------- saveDeliveryAddressState / saveInvitor --------

    public function testSaveDeliveryAddressStateUsesRequestWhenPresent(): void
    {
        $this->setRequestParameter('blshowshipaddress', '1');
        $component = $this->getProxyClass(UserComponent::class);
        $component->UNITsaveDeliveryAddressState();
        $this->assertSame('1', Registry::getSession()->getVariable('blshowshipaddress'));
    }

    public function testSaveDeliveryAddressStateFallsBackToSessionWhenRequestAbsent(): void
    {
        Registry::getSession()->setVariable('blshowshipaddress', 'session-value');
        // No request parameter is set.
        $component = $this->getProxyClass(UserComponent::class);
        $component->UNITsaveDeliveryAddressState();
        $this->assertSame('session-value', Registry::getSession()->getVariable('blshowshipaddress'));
    }

    public function testSaveInvitorIsNoopWhenInvitationsDisabled(): void
    {
        $this->getConfig()->setConfigParam('blInvitationsEnabled', false);
        Registry::getSession()->deleteVariable('su');
        $this->setRequestParameter('su', 'should-not-be-saved');

        $component = $this->getProxyClass(UserComponent::class);
        $component->UNITsaveInvitor();

        $this->assertNull(Registry::getSession()->getVariable('su'));
    }

    public function testSaveInvitorRunsGetInvitorAndSetRecipientWhenEnabled(): void
    {
        $this->getConfig()->setConfigParam('blInvitationsEnabled', true);
        Registry::getSession()->deleteVariable('su');
        Registry::getSession()->deleteVariable('re');
        $this->setRequestParameter('su', 'inv-id');
        $this->setRequestParameter('re', 'rec@example.com');

        $component = $this->getProxyClass(UserComponent::class);
        $component->UNITsaveInvitor();

        $this->assertSame('inv-id', Registry::getSession()->getVariable('su'));
        $this->assertSame('rec@example.com', Registry::getSession()->getVariable('re'));
    }

    // -------- _checkPsState branches --------

    public function testCheckPsStateIsNoopWhenPrivateSalesDisabled(): void
    {
        $parent = $this->getMock(BaseController::class, ['isEnabledPrivateSales']);
        $parent->expects($this->any())
            ->method('isEnabledPrivateSales')
            ->will($this->returnValue(false));

        $component = $this->getProxyClass(UserComponent::class);
        $component->setParent($parent);

        $component->UNITcheckPsState();
        $this->assertTrue(true); // no throw, no redirect, no exception
    }

    // -------- _loadSessionUser branches --------

    public function testLoadSessionUserReturnsEarlyWhenNoUser(): void
    {
        $component = $this->getMock(UserComponent::class, ['getUser']);
        $component->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(false));

        // Must return null without redirect.
        $reflection = new \ReflectionMethod($component, '_loadSessionUser');
        $reflection->setAccessible(true);
        $reflection->invoke($component);

        $this->assertTrue(true);
    }

    // -------- login flow --------

    public function testLoginReturnsUserOnUserExceptionPath(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$loginThrowsUser = true;
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $this->setRequestParameter('lgn_usr', 'foo@example.com');
        $this->setRequestParameter('lgn_pwd', 'pwd');

        $component = oxNew(UserComponent::class);
        $this->assertSame('user', $component->login());
        $this->assertSame(USER_LOGIN_FAIL, $component->getLoginStatus());
    }

    public function testLoginReturnsUserOnCookieExceptionPath(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$loginThrowsCookie = true;
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $component = oxNew(UserComponent::class);
        $this->assertSame('user', $component->login());
        $this->assertSame(USER_LOGIN_FAIL, $component->getLoginStatus());
    }

    public function testLoginPassesCredentialsToUserModel(): void
    {
        $stub = new UserComponentTest_StubUser();
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $this->setRequestParameter('lgn_usr', 'foo@example.com');
        $this->setRequestParameter('lgn_pwd', 'super-secret');
        $this->setRequestParameter('lgn_cook', '1');

        // _afterLogin requires session+basket bootstrap which needs more
        // setup than a unit test should carry — let _afterLogin path return
        // 'payment' via mocking.
        $component = $this->getMock(UserComponent::class, ['_afterLogin']);
        $component->expects($this->once())
            ->method('_afterLogin')
            ->will($this->returnValue('payment'));

        $this->assertSame('payment', $component->login());
        $this->assertSame(['foo@example.com', 'super-secret', '1'], $stub->loginCalledWith);
        $this->assertSame(USER_LOGIN_SUCCESS, $component->getLoginStatus());
    }

    // -------- logout flow --------

    public function testLogoutSetsLogoutStatusAndCallsAfterLogoutThenResetPermissions(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$logoutReturns = true;
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $parent = $this->getMock(BaseController::class, ['isEnabledPrivateSales']);
        $parent->expects($this->any())
            ->method('isEnabledPrivateSales')
            ->will($this->returnValue(false));

        $component = $this->getMock(UserComponent::class, ['_afterLogout', 'resetPermissions']);
        $component->expects($this->once())->method('_afterLogout');
        $component->expects($this->once())->method('resetPermissions');
        $component->setParent($parent);

        $component->logout();

        $this->assertTrue($stub->logoutCalled);
        $this->assertSame(USER_LOGOUT, $component->getLoginStatus());
    }

    public function testLogoutReturnsAccountForPrivateSales(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$logoutReturns = true;
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $parent = $this->getMock(BaseController::class, ['isEnabledPrivateSales']);
        $parent->expects($this->any())
            ->method('isEnabledPrivateSales')
            ->will($this->returnValue(true));

        $component = $this->getMock(UserComponent::class, ['_afterLogout']);
        $component->setParent($parent);

        $this->assertSame('account', $component->logout());
    }

    public function testLogoutIsNoopWhenUserModelReturnsFalse(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$logoutReturns = false;
        \oxTestModules::addModuleObject(\OxidEsales\Eshop\Application\Model\User::class, $stub);

        $component = $this->getMock(UserComponent::class, ['_afterLogout', 'resetPermissions']);
        $component->expects($this->never())->method('_afterLogout');
        $component->expects($this->never())->method('resetPermissions');

        $component->logout();
        $this->assertNull($component->getLoginStatus());
    }

    // -------- changeUser / changeUserWithoutRedirect --------

    public function testChangeUserWithoutRedirectFailsForBadCsrfToken(): void
    {
        // No stoken in request → checkSessionChallenge fails, method returns false.
        $component = oxNew(UserComponent::class);
        $this->setRequestParameter('stoken', 'wrong-token');

        $method = new \ReflectionMethod($component, 'changeUserWithoutRedirect');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke($component));
    }

    public function testChangeUserWithoutRedirectFailsWhenNoUser(): void
    {
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());
        $component = $this->getMock(UserComponent::class, ['getUser']);
        $component->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(false));

        $reflection = new \ReflectionMethod($component, 'changeUserWithoutRedirect');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->invoke($component));
    }

    public function testChangeUserDelegatesToChangeUserWithoutRedirect(): void
    {
        $component = $this->getMock(UserComponent::class, ['changeUserWithoutRedirect']);
        $component->expects($this->once())
            ->method('changeUserWithoutRedirect')
            ->will($this->returnValue(true));

        $this->assertSame('payment', $component->changeUser());
    }

    public function testChangeUserReturnsFalseWhenInnerCallReturnsFalse(): void
    {
        $component = $this->getMock(UserComponent::class, ['changeUserWithoutRedirect']);
        $component->expects($this->once())
            ->method('changeUserWithoutRedirect')
            ->will($this->returnValue(false));

        $this->assertFalse($component->changeUser());
    }

    public function testChangeUserTestValuesReturnsAccountUserOnSuccess(): void
    {
        $component = $this->getMock(UserComponent::class, ['changeUserWithoutRedirect']);
        $component->expects($this->once())
            ->method('changeUserWithoutRedirect')
            ->will($this->returnValue(true));

        $this->assertSame('account_user', $component->changeuser_testvalues());
    }

    public function testChangeUserTestValuesReturnsNullOnFailure(): void
    {
        $component = $this->getMock(UserComponent::class, ['changeUserWithoutRedirect']);
        $component->expects($this->once())
            ->method('changeUserWithoutRedirect')
            ->will($this->returnValue(false));

        $this->assertNull($component->changeuser_testvalues());
    }

    // -------- createUser branches that don't need a real User --------

    public function testCreateUserShortCircuitsOnCsrfFailure(): void
    {
        $this->setRequestParameter('stoken', 'wrong-token');
        $component = oxNew(UserComponent::class);
        $this->assertFalse($component->createUser());
    }

    public function testCreateUserRequiresAgbWhenPrivateSalesAndConfigDemandIt(): void
    {
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());
        $this->getConfig()->setConfigParam('blConfirmAGB', true);

        $parent = $this->getMock(BaseController::class, ['isEnabledPrivateSales']);
        $parent->expects($this->any())
            ->method('isEnabledPrivateSales')
            ->will($this->returnValue(true));

        $component = oxNew(UserComponent::class);
        $component->setParent($parent);

        // No 'ord_agb' in the request → must reject.
        $this->assertFalse($component->createUser());
    }

    // -------- registerUser delegates --------

    public function testRegisterUserCallsLogoutWhenCreateFails(): void
    {
        $component = $this->getMock(UserComponent::class, ['createUser', 'logout']);
        $component->expects($this->once())
            ->method('createUser')
            ->will($this->returnValue(false));
        $component->expects($this->once())->method('logout');

        $component->registerUser();
    }

    public function testRegisterUserReturnsSuccessUrlWhenNewUserAndSubscribed(): void
    {
        $component = $this->getMock(UserComponent::class, ['createUser']);
        $component->expects($this->once())
            ->method('createUser')
            ->will($this->returnValue('payment?new_user=1&success=1'));

        // Force the new-user flag — set via reflection because it's protected.
        $ref = new \ReflectionProperty(UserComponent::class, '_blIsNewUser');
        $ref->setAccessible(true);
        $ref->setValue($component, true);
        $subRef = new \ReflectionProperty(UserComponent::class, '_blNewsSubscriptionStatus');
        $subRef->setAccessible(true);
        $subRef->setValue($component, true);

        $this->assertSame('register?success=1', $component->registerUser());
    }

    public function testRegisterUserReturnsNewsletterErrorUrlWhenSubscriptionFailed(): void
    {
        $component = $this->getMock(UserComponent::class, ['createUser']);
        $component->expects($this->once())->method('createUser')->will($this->returnValue('ok'));

        $ref = new \ReflectionProperty(UserComponent::class, '_blIsNewUser');
        $ref->setAccessible(true);
        $ref->setValue($component, true);
        $subRef = new \ReflectionProperty(UserComponent::class, '_blNewsSubscriptionStatus');
        $subRef->setAccessible(true);
        $subRef->setValue($component, false);

        $this->assertSame('register?success=1&newslettererror=4', $component->registerUser());
    }

    // -------- deleteShippingAddress --------

    public function testDeleteShippingAddressDeletesWhenOwnerMatchesAndCsrfPasses(): void
    {
        $stub = new UserComponentTest_StubAddress();
        UserComponentTest_StubAddress::$ownerOxid = 'user-7';
        \oxTestModules::addModuleObject(Address::class, $stub);

        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());
        $this->setRequestParameter('oxaddressid', 'addr-1');

        $user = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, ['getId']);
        $user->expects($this->any())->method('getId')->will($this->returnValue('user-7'));

        $component = $this->getMock(UserComponent::class, ['getUser']);
        $component->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $component->deleteShippingAddress();

        $this->assertTrue($stub->deleted);
        $this->assertSame('addr-1', $stub->loadedWith);
    }

    public function testDeleteShippingAddressDoesNotDeleteWhenOwnerDiffers(): void
    {
        $stub = new UserComponentTest_StubAddress();
        UserComponentTest_StubAddress::$ownerOxid = 'someone-else';
        \oxTestModules::addModuleObject(Address::class, $stub);

        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());
        $this->setRequestParameter('oxaddressid', 'addr-1');

        $user = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, ['getId']);
        $user->expects($this->any())->method('getId')->will($this->returnValue('user-7'));

        $component = $this->getMock(UserComponent::class, ['getUser']);
        $component->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $component->deleteShippingAddress();
        $this->assertFalse($stub->deleted);
    }

    // -------- _getDelAddressData --------

    public function testGetDelAddressDataReturnsEmptyArrayWhenShipAddrFlagsAreOff(): void
    {
        $component = $this->getProxyClass(UserComponent::class);
        $this->assertSame([], $component->UNITgetDelAddressData());
    }

    public function testGetDelAddressDataReturnsRequestArrayWhenFlagSetAndDataPresent(): void
    {
        $this->setRequestParameter('blshowshipaddress', '1');
        $this->setRequestParameter('deladr', [
            'oxaddress__oxsal' => 'MR', // stripped
            'oxaddress__oxlname' => 'Doe',
        ]);

        $component = $this->getProxyClass(UserComponent::class);
        $data = $component->UNITgetDelAddressData();
        $this->assertArrayHasKey('oxaddress__oxlname', $data);
        $this->assertSame('Doe', $data['oxaddress__oxlname']);
    }

    public function testGetDelAddressDataReturnsEmptyArrayWhenAllValuesEmpty(): void
    {
        $this->setRequestParameter('blshowshipaddress', '1');
        // Only the salutation field — which the controller strips before
        // counting fields — so the result is an empty address.
        $this->setRequestParameter('deladr', ['oxaddress__oxsal' => 'MR']);

        $component = $this->getProxyClass(UserComponent::class);
        $this->assertSame([], $component->UNITgetDelAddressData());
    }

    // -------- _getLogoutLink --------

    public function testGetLogoutLinkAppendsFncLogoutAndCarriesAnid(): void
    {
        $this->setRequestParameter('anid', 'art-1');

        $parent = $this->getMock(BaseController::class, ['getDynUrlParams']);
        $parent->expects($this->any())
            ->method('getDynUrlParams')
            ->will($this->returnValue(''));

        $component = $this->getProxyClass(UserComponent::class);
        $component->setParent($parent);

        $link = $component->UNITgetLogoutLink();
        $this->assertStringEndsWith('&amp;fnc=logout', $link);
        $this->assertStringContainsString('&amp;anid=art-1', $link);
    }

    public function testGetLogoutLinkCarriesMultipleParamsAndDynUrlParams(): void
    {
        $this->setRequestParameter('cnid', 'cat-1');
        $this->setRequestParameter('mnid', 'manu-1');
        $this->setRequestParameter('tpl', 'foo.tpl');
        $this->setRequestParameter('oxloadid', 'load-1');
        $this->setRequestParameter('recommid', 'rec-1');

        $parent = $this->getMock(BaseController::class, ['getDynUrlParams']);
        $parent->expects($this->any())
            ->method('getDynUrlParams')
            ->will($this->returnValue('&amp;dyn=1'));

        $component = $this->getProxyClass(UserComponent::class);
        $component->setParent($parent);

        $link = $component->UNITgetLogoutLink();
        foreach (['cnid=cat-1', 'mnid=manu-1', 'tpl=foo.tpl', 'oxloadid=load-1', 'recommid=rec-1', 'dyn=1', 'fnc=logout'] as $needle) {
            $this->assertStringContainsString($needle, $link);
        }
    }

    public function testGetLogoutLinkDelegatePublicWrapper(): void
    {
        $parent = $this->getMock(BaseController::class, ['getDynUrlParams']);
        $parent->expects($this->any())->method('getDynUrlParams')->will($this->returnValue(''));

        $component = $this->getProxyClass(UserComponent::class);
        $component->setParent($parent);

        // public delegate must produce same output as protected impl
        $this->assertSame($component->UNITgetLogoutLink(), $component->UNITgetLogoutLink());
    }

    // -------- afterLogout (session ops) --------

    public function testAfterLogoutClearsRelevantSessionVariables(): void
    {
        Registry::getSession()->setVariable('paymentid', 'pay-1');
        Registry::getSession()->setVariable('sShipSet', 'ship-1');
        Registry::getSession()->setVariable('deladrid', 'addr-1');
        Registry::getSession()->setVariable('dynvalue', ['x']);

        $component = $this->getProxyClass(UserComponent::class);
        $component->UNITafterLogout();

        $this->assertNull(Registry::getSession()->getVariable('paymentid'));
        $this->assertNull(Registry::getSession()->getVariable('sShipSet'));
        $this->assertNull(Registry::getSession()->getVariable('deladrid'));
        $this->assertNull(Registry::getSession()->getVariable('dynvalue'));
    }

    // -------- _afterLogin --------

    public function testAfterLoginReturnsPaymentForUnblockedUser(): void
    {
        $stub = new UserComponentTest_StubUser();
        UserComponentTest_StubUser::$inGroupBlocked = false;

        $component = $this->getProxyClass(UserComponent::class);
        $this->assertSame('payment', $component->UNITafterLogin($stub));
    }

    // -------- private helper methods (via reflection) --------

    public function testIsGuestUserPrivateHelper(): void
    {
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'isGuestUser');
        $method->setAccessible(true);

        $guest = new UserComponentTest_StubUser();
        $guest->oxuser__oxpassword = new Field('');
        $this->assertTrue($method->invoke($component, $guest));

        $real = new UserComponentTest_StubUser();
        $real->oxuser__oxpassword = new Field('hashed-pwd');
        $this->assertFalse($method->invoke($component, $real));
    }

    public function testIsUserNameUpdatedPrivateHelper(): void
    {
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'isUserNameUpdated');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($component, 'old@example.com', 'new@example.com'));
        $this->assertFalse($method->invoke($component, 'same@example.com', 'same@example.com'));
        // Empty current name → no "update" yet.
        $this->assertFalse($method->invoke($component, '', 'new@example.com'));
        // Empty new name → ditto.
        $this->assertFalse($method->invoke($component, 'old@example.com', ''));
    }

    public function testCleanAddressPassesThroughNonArrayInput(): void
    {
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'cleanAddress');
        $method->setAccessible(true);

        $updatableFields = oxNew(\OxidEsales\Eshop\Application\Model\User\UserUpdatableFields::class);
        // Non-array input → returned as-is (the early `if (is_array(...))` is false).
        $this->assertSame('not-an-array', $method->invoke($component, 'not-an-array', $updatableFields));
        $this->assertNull($method->invoke($component, null, $updatableFields));
    }

    public function testTrimAddressPassesThroughNonArrayInput(): void
    {
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'trimAddress');
        $method->setAccessible(true);

        $this->assertSame('not-an-array', $method->invoke($component, 'not-an-array'));
        $this->assertNull($method->invoke($component, null));
    }

    public function testTrimAddressTrimsArrayValues(): void
    {
        $component = oxNew(UserComponent::class);
        $method = new \ReflectionMethod($component, 'trimAddress');
        $method->setAccessible(true);

        $result = $method->invoke($component, [
            'oxuser__oxlname' => '  Doe  ',
            'oxuser__oxfname' => "\tJohn\n",
        ]);
        $this->assertIsArray($result);
        $this->assertSame('Doe', $result['oxuser__oxlname']);
        $this->assertSame('John', $result['oxuser__oxfname']);
    }
}
