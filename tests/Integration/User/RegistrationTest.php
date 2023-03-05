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
namespace OxidEsales\EshopCommunity\Tests\Integration\User;

use oxcmp_user;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Application\Component\UserComponent;
use oxRegistry;

require_once 'UserTestCase.php';

class RegistrationTest extends UserTestCase
{

    /** @var bool */
    protected $_blSkipCustomTearDown = true;

    public function testRegisterNewUser()
    {
        $userName = $this->_sDefaultUserName;
        $userPassword = $this->_sDefaultUserPassword;

        $cmpUser = $this->_createCmpUserObject();

        $session = $this->createPartialMock(Session::class, ['checkSessionChallenge']);
        Registry::set(Session::class, $session);
        $session->expects($this->once())->method('checkSessionChallenge')->willReturn(true);

        $this->_setUserRegistrationParametersToRequest($userName, $userPassword);
        $this->assertSame('register?success=1', $cmpUser->registerUser());

        return $cmpUser->getUser()->getId();
    }

    /**
     * @param string $sUserId
     *
     * @depends testRegisterNewUser
     */
    public function testLoginWithNewUser($sUserId)
    {
        $oCmpUser = $this->_createCmpUserObject();
        $oCmpUser->logout();
        $this->assertNull(oxRegistry::getSession()->getVariable('usr'), 'User ID should not be in session after logout.');

        $this->_login();

        $this->assertSame($sUserId, oxRegistry::getSession()->getVariable('usr'), 'User ID is missing in session after log in.');
    }

    public function testRegisterWithoutCsrf()
    {
        $session = $this->createPartialMock(Session::class, ['checkSessionChallenge']);
        $session->method('checkSessionChallenge')->willReturn(False);

        $utilsView = $this->createPartialMock(UtilsView::class, ['addErrorToDisplay']);
        $utilsView->expects($this->once())->method('addErrorToDisplay')->with('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');

        Registry::set(UtilsView::class, $utilsView);
        Registry::set(Session::class, $session);

        $userView = oxNew(UserComponent::class);
        $userView->createUser();
    }

    /**
     * @param string $sUserName
     * @param string $sUserPassword
     */
    private function _setUserRegistrationParametersToRequest($sUserName, $sUserPassword)
    {
        $sGermanyId = 'a7c40f631fc920687.20179984';

        $this->setRequestParameter('userLoginName', $sUserName);
        $this->setRequestParameter('lgn_usr', $sUserName);

        $this->setRequestParameter('lgn_pwd', $sUserPassword);
        $this->setRequestParameter('lgn_pwd2', $sUserPassword);
        $this->setRequestParameter('passwordLength', $sUserPassword);
        $this->setRequestParameter('userPasswordConfirm', $sUserPassword);

        $this->setRequestParameter(
            'invadr',
            array(
                 'oxuser__oxsal'       => 'Mr',
                 'oxuser__oxfname'     => 'SomeTestName',
                 'oxuser__oxlname'     => 'SomeTestSurname',
                 'oxuser__oxstreet'    => 'SomeTestStreet',
                 'oxuser__oxstreetnr'  => '23',
                 'oxuser__oxzip'       => '44444',
                 'oxuser__oxcity'      => 'SomeTestCoty',
                 'oxuser__oxcountryid' => $sGermanyId
            )
        );
    }

    /**
     * @return oxcmp_user
     */
    private function _createCmpUserObject()
    {
        $oRegister = oxNew('Register');
        $oCmpUser = oxNew('oxcmp_user');
        $oCmpUser->setParent($oRegister);

        return $oCmpUser;
    }
}
