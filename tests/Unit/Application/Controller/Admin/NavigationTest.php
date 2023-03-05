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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\NavigationController;
use OxidEsales\Eshop\Core\Config;
use oxRegistry;
use oxTestModules;
use stdClass;

/**
 * Tests for Navigation class
 */
class NavigationTest extends \OxidTestCase
{

    /**
     * Navigation::chshp() test case
     *
     * @return null
     */
    public function testChshpPE()
    {
        $this->setRequestParameter("listview", "testlistview");
        $this->setRequestParameter("editview", "testeditview");
        $this->setRequestParameter("actedit", "testactedit");

        $oView = oxNew('Navigation');
        $oView->chshp();

        $this->assertEquals("testlistview", $oView->getViewDataElement("listview"));
        $this->assertEquals("testeditview", $oView->getViewDataElement("editview"));
        $this->assertEquals("testactedit", $oView->getViewDataElement("actedit"));
        $this->assertEquals(true, $oView->getViewDataElement("loadbasefrm"));
    }

    /**
     * Navigation::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction('oxUtilsServer', 'setOxCookie', '{}');
        $this->setRequestParameter("favorites", array(0, 1, 2));

        // testing..
        $oView = oxNew('Navigation');
        $this->assertEquals('nav_frame.tpl', $oView->render());
    }

    /**
     * Navigation::Render() test case
     *
     * @return null
     */
    public function testRenderPassingTemplateName()
    {
        oxTestModules::addFunction('oxUtilsServer', 'setOxCookie', '{}');
        oxTestModules::addFunction('oxUtilsServer', 'getOxCookie', '{return "a|b";}');
        $this->setRequestParameter("item", "home.tpl");
        $this->setRequestParameter("favorites", array(0, 1, 2));
        $this->setRequestParameter("navReload", false);
        $this->setRequestParameter("openHistory", true);

        $oDom = new stdClass();
        $oDom->documentElement = new stdClass();
        $oDom->documentElement->childNodes = 'testNodes';

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, array("getDomXml", "getListNodes"));
        $oNavigation->expects($this->once())->method('getDomXml')->will($this->returnValue($oDom));
        $oNavigation->expects($this->any())->method('getListNodes')->will($this->returnValue("testNodes"));

        // testing..
        $oView = $this->getMock(NavigationController::class, array("getNavigation", "_doStartUpChecks"));
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));
        $oView->expects($this->once())->method('_doStartUpChecks')->will($this->returnValue("check"));
        $this->assertEquals('home.tpl', $oView->render());

        // checking vew data
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData["menustructure"]));
        $this->assertTrue(isset($aViewData["sVersion"]));
        $this->assertTrue(isset($aViewData["aMessage"]));
        $this->assertTrue(isset($aViewData["menufavorites"]));
        $this->assertTrue(isset($aViewData["aFavorites"]));
        $this->assertTrue(isset($aViewData["menuhistory"]));
        $this->assertTrue(isset($aViewData["blOpenHistory"]));
    }

    /**
     * Navigation::Render() test case
     *
     * @return null
     */
    public function testRenderForceRequirementsCheckingNextTime()
    {
        oxTestModules::addFunction('oxUtilsServer', 'setOxCookie', '{}');
        oxTestModules::addFunction('oxUtilsServer', 'getOxCookie', '{return "a|b";}');
        $this->setRequestParameter("item", "home.tpl");
        $this->setRequestParameter("favorites", array(0, 1, 2));
        $this->setRequestParameter("navReload", true);
        $this->setRequestParameter("openHistory", true);
        $this->getSession()->setVariable("navReload", "true");

        $oDom = new stdClass();
        $oDom->documentElement = new stdClass();
        $oDom->documentElement->childNodes = 'testNodes';

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, array("getDomXml", "getListNodes"));
        $oNavigation->expects($this->once())->method('getDomXml')->will($this->returnValue($oDom));
        $oNavigation->expects($this->any())->method('getListNodes')->will($this->returnValue("testNodes"));

        // testing..
        $oView = $this->getMock(NavigationController::class, array("getNavigation", "_doStartUpChecks"));
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));
        $oView->expects($this->never())->method('_doStartUpChecks')->will($this->returnValue("check"));
        $this->assertEquals('home.tpl', $oView->render());

        // checking vew data
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData["menustructure"]));
        $this->assertTrue(isset($aViewData["sVersion"]));
        $this->assertFalse(isset($aViewData["aMessage"]));
        $this->assertTrue(isset($aViewData["menufavorites"]));
        $this->assertTrue(isset($aViewData["aFavorites"]));
        $this->assertTrue(isset($aViewData["menuhistory"]));
        $this->assertTrue(isset($aViewData["blOpenHistory"]));
        $this->assertNull(oxRegistry::getSession()->getVariable("navReload"));
    }

    /**
     * Navigation::Logout() test case
     *
     * @return null
     */
    public function testLogout()
    {
        oxTestModules::addFunction('oxUtils', 'redirect', '{}');

        $this->getSession()->setVariable('usr', "testUsr");
        $this->getSession()->setVariable('auth', "testAuth");
        $this->getSession()->setVariable('dynvalue', "testDynValue");
        $this->getSession()->setVariable('paymentid', "testPaymentId");

        $oConfig = $this->getMock(Config::class, array("getConfigParam"));
        $oConfig->expects($this->once())->method('getConfigParam')->with($this->equalTo("blClearCacheOnLogout"))->will($this->returnValue(true));

        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, array("destroy", "getId"));
        $oSession->expects($this->once())->method('destroy');
        $oSession->expects($this->never())->method('getId');

        // testing..
        $oView = $this->getMock(NavigationController::class, array("getSession", "getConfig", "resetContentCache"), array(), '', false);
        $oView->expects($this->once())->method('getSession')->will($this->returnValue($oSession));
        $oView->expects($this->once())->method('getConfig')->will($this->returnValue($oConfig));
        $oView->expects($this->once())->method('resetContentCache');
        $oView->logout();

        // testing if these were unset from session
        $this->assertNull(oxRegistry::getSession()->getVariable('usr'));
        $this->assertNull(oxRegistry::getSession()->getVariable('auth'));
        $this->assertNull(oxRegistry::getSession()->getVariable('dynvalue'));
        $this->assertNull(oxRegistry::getSession()->getVariable('paymentid'));
    }

    /**
     * Navigation::Exturl() test case
     *
     * @return null
     */
    public function testExturl()
    {
        oxTestModules::addFunction('oxUtils', 'showMessageAndExit', '{ throw new Exception("showMessageAndExit"); }');
        $this->setRequestParameter("url", null);

        try {
            // testing..
            $oView = oxNew('Navigation');
            $oView->exturl();
        } catch (Exception $oExcp) {
            $this->assertEquals("showMessageAndExit", $oExcp->getMessage(), "Error in Navigation::exturl()");

            return;
        }
        $this->fail("Error in Navigation::exturl()");
    }

    /**
     * Navigation::DoStartUpChecks() test case
     *
     * @return null
     */
    public function testDoStartUpChecks()
    {
        $this->getConfig()->setConfigParam("blCheckForUpdates", true);

        // testing..
        $oView = $this->getMock(NavigationController::class, array("_checkVersion"));
        $oView->expects($this->once())->method('_checkVersion')->will($this->returnValue("versionnotice"));
        $aState = $oView->UNITdoStartUpChecks();
        $this->assertTrue(is_array($aState));
        $this->assertTrue(isset($aState['message']));
        $this->assertTrue(isset($aState['warning']));
    }

    public function testCheckVersion(): void
    {
        $currentVersion = '123';
        $latestVersion = '987';
        oxTestModules::addFunction('oxUtilsFile', 'readRemoteFileAsString', "{ return $latestVersion; }");
        oxTestModules::addFunction('oxLang', 'translateString', '{ return "current ver.: %s new ver.: %s"; }');
        $configMock = $this->createConfiguredMock(Config::class, ['getVersion' => $currentVersion]);
        $controllerMock = $this->getMock(
            NavigationController::class,
            ['getConfig'],
            [],
            '',
            false
        );
        $controllerMock->method('getConfig')
            ->willReturn($configMock);

        $actual =  $controllerMock->UNITcheckVersion();

        $this->assertStringContainsString($currentVersion, $actual);
        $this->assertStringContainsString($latestVersion, $actual);
    }
}
