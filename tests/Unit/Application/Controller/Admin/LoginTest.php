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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use \oxField;
use \oxException;
use OxidEsales\Eshop\Application\Model\User;
use \stdClass;
use \oxConnectionException;
use \oxUserException;
use \oxCookieException;
use \oxDb;
use \oxRegistry;
use \oxTestModules;

/**
 * Testing login class.
 */
class LoginTest extends \OxidTestCase
{
    public function setup(): void
    {
        parent::setUp();

        $this->setAdminMode(true);
        $this->getSession()->setVariable("blIsAdmin", true);
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxuser');
        oxDb::getDb()->execute("delete from oxremark where oxparentid = '_testUserId'");
        oxDb::getDb()->execute("delete from oxnewssubscribed where oxuserid = '_testUserId'");
        parent::tearDown();
    }

    /**
     *  Check if login with special characters in login name and
     *  passworod works fine
     *
     * M#1386
     *
     * @return null
     */
    public function testLogin()
    {
        $oUser = $this
            ->getMockBuilder(User::class)
            ->setMethods(['_getUserRights'])
            ->getMock();
        $oUser->method('_getUserRights')->willReturn('malladmin');
        $oUser->setId("_testUserId");
        $oUser->oxuser__oxactive = new oxField("1");
        $oUser->oxuser__oxusername = new oxField("&\"\'\\<>adminname", oxField::T_RAW);
        $oUser->oxuser__oxshopid = new oxField($this->getConfig()->getShopId());

        $oUser->setPassword("&\"\'\\<>adminpsw");
        $oUser->save();

        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{ throw new oxException($aA[0]); }');
        oxTestModules::addFunction('oxUtilsServer', 'getOxCookie', '{ return array(\'test\'); }');

        $_SERVER['REQUEST_METHOD'] = "POST";
        $this->setRequestParameter("user", "&\"\'\\<>adminname");
        $this->setRequestParameter("pwd", "&\"\'\\<>adminpsw");


        $oLogin = $this->getProxyClass('login');
        $this->assertEquals("admin_start", $oLogin->checklogin());
    }

    /**
     *  Check if login with special characters in login name and
     *  passworod works fine
     *
     *  M#3680
     *
     * @return null
     */
    public function testLoginNotAdmin()
    {
        $this->expectException('oxException');
        $this->expectExceptionMessage('ERROR_MESSAGE_USER_NOVALIDLOGIN');

        $oUser = oxNew("oxUser");
        $oUser->setId("_testUserId");
        $oUser->oxuser__oxactive = new oxField("1");
        $oUser->oxuser__oxusername = new oxField("&\"\'\\<>adminname", oxField::T_RAW);
        $oUser->setPassword("&\"\'\\<>adminpsw");
        $oUser->save();

        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{ throw new oxException($aA[0]); }');
        oxTestModules::addFunction('oxUtilsServer', 'getOxCookie', '{ return array(\'test\'); }');

        $_SERVER['REQUEST_METHOD'] = "POST";
        $this->setRequestParameter("user", "&\"\'\\<>adminname");
        $this->setRequestParameter("pwd", "&\"\'\\<>adminpsw");

        $oLogin = $this->getProxyClass('login');
        $this->assertEquals("admin_start", $oLogin->checklogin());
    }

    /**
     *  Check getting browser language abbervation
     *
     * @return null
     */
    public function testGetBrowserLanguage()
    {
        $oLogin = $this->getProxyClass('login');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = "en-US,en;q=0.8,fr-ca;q=0.5,fr;q=0.3;";
        $this->assertEquals("en", $oLogin->UNITgetBrowserLanguage());
    }

    /**
     *  Check getting available admin interface languages
     *  when selected lang ID is not setted to cookie. Selected lang
     *  should be selected by detected lang in browser.
     *
     * @return null
     */
    public function testGetAvailableLanguages_withoutCookies_DE()
    {
        oxTestModules::addFunction('oxUtilsServer', 'getOxCookie', '{ return null; }');

        $oLang = new stdClass();
        $oLang->id = 0;
        $oLang->oxid = "de";
        $oLang->abbr = "de";
        $oLang->name = "Deutsch";
        $oLang->selected = 1;

        $aLanguages[] = $oLang;

        $oLang = new stdClass();
        $oLang->id = 1;
        $oLang->oxid = "en";
        $oLang->abbr = "en";
        $oLang->name = "English";
        $oLang->selected = 0;

        $aLanguages[] = $oLang;

        $oLogin = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LoginController::class, array('_getBrowserLanguage'));
        $oLogin->expects($this->once())->method('_getBrowserLanguage')->will($this->returnValue('de'));

        $this->assertEquals($aLanguages, $oLogin->UNITgetAvailableLanguages());
    }

    /**
     *  Check getting available admin interface languages
     *  when selected lang ID is not setted to cookie. Selected lang
     *  should be selected by detected lang in browser.
     *
     * @return null
     */
    public function testGetAvailableLanguages_withoutCookies_EN()
    {
        $oLang = new stdClass();
        $oLang->id = 0;
        $oLang->oxid = "de";
        $oLang->abbr = "de";
        $oLang->name = "Deutsch";
        $oLang->selected = 0;

        $aLanguages[] = $oLang;

        $oLang = new stdClass();
        $oLang->id = 1;
        $oLang->oxid = "en";
        $oLang->abbr = "en";
        $oLang->name = "English";
        $oLang->selected = 1;

        $aLanguages[] = $oLang;

        $oLogin = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LoginController::class, array('_getBrowserLanguage'));
        $oLogin->expects($this->once())->method('_getBrowserLanguage')->will($this->returnValue('en'));

        $this->assertEquals($aLanguages, $oLogin->UNITgetAvailableLanguages());
    }

    /**
     *  Check getting available admin interface languages
     *  when selected lang ID is setted to cookie. Selected lang
     *  should be selected by detected lang id in cookie.
     *
     * @return null
     */
    public function testGetAvailableLanguages_withCookies_DE()
    {
        $oLang = new stdClass();
        $oLang->id = 0;
        $oLang->oxid = "de";
        $oLang->abbr = "de";
        $oLang->name = "Deutsch";
        $oLang->selected = 0;

        $aLanguages[] = $oLang;

        $oLang = new stdClass();
        $oLang->id = 1;
        $oLang->oxid = "en";
        $oLang->abbr = "en";
        $oLang->name = "English";
        $oLang->selected = 1;

        $aLanguages[] = $oLang;

        // browser lang does not affect selected lang when cookie is set
        $oLogin = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LoginController::class, array('_getBrowserLanguage'));
        $oLogin->expects($this->once())->method('_getBrowserLanguage')->will($this->returnValue('en'));

        // DE lang id
        $_COOKIE["oxidadminlanguage"] = 0;
        $aLangs = $oLogin->UNITgetAvailableLanguages();
        $this->assertEquals($aLanguages, $aLangs);
    }

    /**
     * Testing login::getViewId()
     *
     * @return null
     */
    public function testGetViewId()
    {
        $oView = oxNew('Login');
        $this->assertEquals('login', $oView->getViewId());
    }

    /**
     * Testing login::_authorize()
     *
     * @return null
     */
    public function testAuthorize()
    {
        $oView = oxNew('Login');
        $this->assertTrue($oView->UNITauthorize());
    }

    /**
     * Testing login::checklogin()
     *
     * @return null
     */
    public function testCheckloginSettingProfile()
    {
        //We have no sesison started yet. When UtilsView::addErrorToDisplay starts a new session,
        //non persistent data is lost so better add a mock here.
        $utilsView = $this->getMockBuilder(\OxidEsales\Eshop\Core\UtilsView::class)
            ->setMethods(['addErrorToDisplay'])
            ->getMock();
        $utilsView->expects($this->once())->method('addErrorToDisplay');
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsView::class, $utilsView);

        oxTestModules::addFunction('oxuser', 'login', '{ throw new oxConnectionException(); }');
        oxTestModules::addFunction('oxUtils', 'logger', '{ return true; }');

        $this->setRequestParameter('profile', "testProfile");
        $this->getSession()->setVariable("aAdminProfiles", array("testProfile" => array("testValue")));

        $oView = oxNew('Login');
        $this->assertEquals("admin_start", $oView->checklogin());
        $this->assertEquals(array("testValue"), oxRegistry::getSession()->getVariable("profile"));
    }

    /**
     * Testing login::checklogin() exception cases
     *
     * @dataProvider checkLoginExceptionDataProvider
     */
    public function testCheckloginException($exception)
    {
        $userMock = $this->createPartialMock(User::class, ['login']);
        $userMock->expects($this->once())->method('login')->willThrowException($exception);
        \OxidEsales\Eshop\Core\Registry::getUtilsObject()::setClassInstance(User::class, $userMock);

        $utilsViewMock = $this->createPartialMock(\OxidEsales\Eshop\Core\UtilsView::class, ['addErrorToDisplay']);
        $utilsViewMock->expects($this->atLeastOnce())->method('addErrorToDisplay')->with($exception);
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsView::class, $utilsViewMock);

        $this->setRequestParameter('user', '\'"<^%&*aaa>');
        $this->setRequestParameter('pwd', '<^%&*aaa>\'"');
        $this->setRequestParameter('profile', '<^%&*aaa>\'"');
        $this->setAdminMode(true);
        $this->getSession()->setVariable("blIsAdmin", true);
        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LoginController::class, array("addTplParam"));
        $oView->expects($this->at(0))->method('addTplParam')->with($this->equalTo("user"), $this->equalTo('&#039;&quot;&lt;^%&amp;*aaa&gt;'));
        $oView->expects($this->at(1))->method('addTplParam')->with($this->equalTo("pwd"), $this->equalTo('&lt;^%&amp;*aaa&gt;&#039;&quot;'));
        $oView->expects($this->at(2))->method('addTplParam')->with($this->equalTo("profile"), $this->equalTo('&lt;^%&amp;*aaa&gt;&#039;&quot;'));
        $this->assertNull($oView->checklogin());
    }

    public function checkLoginExceptionDataProvider()
    {
        return [
            [new \OxidEsales\Eshop\Core\Exception\UserException('Message1')],
            [new \OxidEsales\Eshop\Core\Exception\CookieException('Message2')]
        ];
    }

    /**
     * Testing login::render()
     *
     * @return null
     */
    public function testRender()
    {
        $oLang = new stdClass();
        $oLang->blSelected = true;

        $aLanguages = array($oLang);

        $oViewConfig = $this->getMock(\OxidEsales\Eshop\Core\ViewConfig::class, array("setViewConfigParam"));
        $oViewConfig->expects($this->atLeastOnce())->method('setViewConfigParam');

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array("isDemoShop"));
        $oConfig->expects($this->atLeastOnce())->method('isDemoShop')->will($this->returnValue("true"));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LoginController::class, array("getConfig", "getViewConfig", "addTplParam", "_getAvailableLanguages"), array(), '', false);
        $oView->expects($this->atLeastOnce())->method('getViewConfig')->will($this->returnValue($oViewConfig));
        $oView->expects($this->atLeastOnce())->method('addTplParam');
        $oView->expects($this->atLeastOnce())->method('getConfig')->will($this->returnValue($oConfig));
        $oView->expects($this->once())->method('_getAvailableLanguages')->will($this->returnValue($aLanguages));

        $this->assertEquals("login.tpl", $oView->render());
    }
}
