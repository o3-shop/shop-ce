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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use \oxTestModules;

class RegisterTest extends \OxidTestCase
{

    /**
     * oxScLoginRegister::render() test case
     *
     * @return null
     */
    public function testRenderForLoginFeature()
    {
        $this->getConfig()->setConfigParam("blPsLoginEnabled", true);

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\RegisterController::class, array("isConfirmed"));
        $oView->expects($this->once())->method('isConfirmed')->will($this->returnValue(true));
        $this->assertEquals('page/account/register_confirm.tpl', $oView->render());
    }

    /**
     * oxScLoginRegister::confirmRegistration() test case
     *
     * @return null
     */
    public function testConfirmRegistrationBadUserUpdateId()
    {
        oxTestModules::addFunction("oxuser", "loadUserByUpdateId", "{return false;}");
        oxTestModules::addFunction("oxUtilsView", "addErrorToDisplay", "{}");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\RegisterController::class, array("getUpdateId"));
        $oView->expects($this->once())->method('getUpdateId')->will($this->returnValue("testUpdateId"));
        $this->assertEquals('account', $oView->confirmRegistration());
    }

    /**
     * oxScLoginRegister::confirmRegistration() test case
     *
     * @return null
     */
    public function testConfirmRegistration()
    {
        oxTestModules::addFunction("oxuser", "loadUserByUpdateId", "{return true;}");
        oxTestModules::addFunction("oxuser", "setUpdateKey", "{return true;}");
        oxTestModules::addFunction("oxuser", "save", "{return true;}");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\RegisterController::class, array("getUpdateId"));
        $oView->expects($this->once())->method('getUpdateId')->will($this->returnValue("testUpdateId"));
        $this->assertEquals('register?confirmstate=1', $oView->confirmRegistration());
    }

    /**
     * oxScLoginRegister::getUpdateId() test case
     *
     * @return null
     */
    public function testGetUpdateId()
    {
        $this->setRequestParameter('uid', "testUid");

        $oView = oxNew('register');
        $this->assertEquals("testUid", $oView->getUpdateId());
    }

    /**
     * oxScLoginRegister::isConfirmed() test case
     *
     * @return null
     */
    public function testIsConfirmed()
    {
        $oView = oxNew('register');

        $this->setRequestParameter("confirmstate", 0);
        $this->assertFalse($oView->isConfirmed());

        $this->setRequestParameter("confirmstate", 1);
        $this->assertTrue($oView->isConfirmed());
    }

    public function testGetRegistrationError()
    {
        $oRegister = $this->getProxyClass('register');
        $this->setRequestParameter('newslettererror', 'testError');

        $this->assertEquals('testError', $oRegister->getRegistrationError());
    }

    public function testGetRegistrationStatus()
    {
        $oRegister = $this->getProxyClass('register');
        $this->setRequestParameter('success', 'success');

        $this->assertEquals('success', $oRegister->getRegistrationStatus());
    }

    /**
     * Testing if method returns correct value
     *
     * @return null
     */
    public function testIsFieldRequired()
    {
        $oRegister = $this->getMock(\OxidEsales\Eshop\Application\Controller\RegisterController::class, array('getMustFillFields'));
        $oRegister->expects($this->any())->method('getMustFillFields')->will($this->returnValue(array("testValue1" => 1, "testValue2" => 1)));

        $this->assertTrue($oRegister->isFieldRequired("testValue1"));
        $this->assertFalse($oRegister->isFieldRequired("testValue5"));
    }

    public function testRenderNoRStat()
    {
        $oRegister = oxNew('register');
        $this->assertEquals('page/account/register.tpl', $oRegister->render());
    }

    public function testRenderRStat()
    {
        $oRegister = $this->getMock(\OxidEsales\Eshop\Application\Controller\RegisterController::class, array('getRegistrationStatus', 'getRegistrationError'));
        $oRegister->expects($this->exactly(2))->method('getRegistrationStatus')->will($this->returnValue('rst'));
        $oRegister->expects($this->once())->method('getRegistrationError')->will($this->returnValue('rer'));

        $this->assertEquals('page/account/register_success.tpl', $oRegister->render());
        $this->assertEquals('rst', $oRegister->getRegistrationStatus());
        $this->assertEquals('rer', $oRegister->getRegistrationError());
    }

    public function testGetBreadCrumb()
    {
        $oRegister = oxNew('register');

        $this->assertEquals(1, count($oRegister->getBreadCrumb()));
    }
}
