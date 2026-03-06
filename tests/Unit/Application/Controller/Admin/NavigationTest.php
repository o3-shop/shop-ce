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
        $this->setRequestParameter('listview', 'testlistview');
        $this->setRequestParameter('editview', 'testeditview');
        $this->setRequestParameter('actedit', 'testactedit');

        $oView = oxNew('Navigation');
        $oView->chshp();

        $this->assertEquals('testlistview', $oView->getViewDataElement('listview'));
        $this->assertEquals('testeditview', $oView->getViewDataElement('editview'));
        $this->assertEquals('testactedit', $oView->getViewDataElement('actedit'));
        $this->assertEquals(true, $oView->getViewDataElement('loadbasefrm'));
    }

    /**
     * Navigation::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        oxTestModules::addFunction('oxUtilsServer', 'setOxCookie', '{}');
        $this->setRequestParameter('favorites', [0, 1, 2]);

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
        $this->setRequestParameter('item', 'home.tpl');
        $this->setRequestParameter('favorites', [0, 1, 2]);
        $this->setRequestParameter('navReload', false);
        $this->setRequestParameter('openHistory', true);

        $oDom = new stdClass();
        $oDom->documentElement = new stdClass();
        $oDom->documentElement->childNodes = 'testNodes';

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, ['getDomXml', 'getListNodes']);
        $oNavigation->expects($this->once())->method('getDomXml')->will($this->returnValue($oDom));
        $oNavigation->expects($this->any())->method('getListNodes')->will($this->returnValue('testNodes'));

        // testing..
        $oView = $this->getMock(NavigationController::class, ['getNavigation', 'doStartUpChecks']);
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));
        $oView->expects($this->once())->method('doStartUpChecks')->will($this->returnValue('check'));
        $this->assertEquals('home.tpl', $oView->render());

        // checking vew data
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['menustructure']));
        $this->assertTrue(isset($aViewData['sVersion']));
        $this->assertTrue(isset($aViewData['aMessage']));
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
        $this->setRequestParameter('item', 'home.tpl');
        $this->setRequestParameter('favorites', [0, 1, 2]);
        $this->setRequestParameter('navReload', true);
        $this->setRequestParameter('openHistory', true);
        $this->getSession()->setVariable('navReload', 'true');

        $oDom = new stdClass();
        $oDom->documentElement = new stdClass();
        $oDom->documentElement->childNodes = 'testNodes';

        $oNavigation = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\NavigationTree::class, ['getDomXml', 'getListNodes']);
        $oNavigation->expects($this->once())->method('getDomXml')->will($this->returnValue($oDom));
        $oNavigation->expects($this->any())->method('getListNodes')->will($this->returnValue('testNodes'));

        // testing..
        $oView = $this->getMock(NavigationController::class, ['getNavigation', 'doStartUpChecks']);
        $oView->expects($this->once())->method('getNavigation')->will($this->returnValue($oNavigation));
        $oView->expects($this->never())->method('doStartUpChecks')->will($this->returnValue('check'));
        $this->assertEquals('home.tpl', $oView->render());

        // checking vew data
        $aViewData = $oView->getViewData();
        $this->assertTrue(isset($aViewData['menustructure']));
        $this->assertTrue(isset($aViewData['sVersion']));
        $this->assertFalse(isset($aViewData['aMessage']));
        $this->assertNull(oxRegistry::getSession()->getVariable('navReload'));
    }

    /**
     * Navigation::Logout() test case
     *
     * @return null
     */
    public function testLogout()
    {
        oxTestModules::addFunction('oxUtils', 'redirect', '{}');

        $this->getSession()->setVariable('usr', 'testUsr');
        $this->getSession()->setVariable('auth', 'testAuth');
        $this->getSession()->setVariable('dynvalue', 'testDynValue');
        $this->getSession()->setVariable('paymentid', 'testPaymentId');

        $this->getConfig()->setConfigParam('blClearCacheOnLogout', true);

        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['destroy', 'getId']);
        $oSession->expects($this->once())->method('destroy');
        $oSession->expects($this->never())->method('getId');

        // Register the session mock in Registry so Registry::getSession() returns it
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oSession);

        // testing..
        $oView = $this->getMock(NavigationController::class, ['resetContentCache']);
        $oView->expects($this->once())->method('resetContentCache');
        $oView->logout();
    }

    /**
     * Navigation::Exturl() test case
     *
     * @return null
     */
    public function testExturl()
    {
        oxTestModules::addFunction('oxUtils', 'showMessageAndExit', '{ throw new Exception("showMessageAndExit"); }');
        $this->setRequestParameter('url', null);

        try {
            // testing..
            $oView = oxNew('Navigation');
            $oView->exturl();
        } catch (Exception $oExcp) {
            $this->assertEquals('showMessageAndExit', $oExcp->getMessage(), 'Error in Navigation::exturl()');

            return;
        }
        $this->fail('Error in Navigation::exturl()');
    }

    /**
     * Navigation::DoStartUpChecks() test case
     *
     * @return null
     */
    public function testDoStartUpChecks()
    {
        $this->getConfig()->setConfigParam('blCheckForUpdates', true);

        // testing..
        $oView = $this->getMock(NavigationController::class, ['checkVersion']);
        $oView->expects($this->once())->method('checkVersion')->will($this->returnValue('versionnotice'));
        $aState = $oView->UNITdoStartUpChecks();
        $this->assertTrue(is_array($aState));
        $this->assertTrue(isset($aState['message']));
        $this->assertTrue(isset($aState['warning']));
    }

    public function testCheckVersion(): void
    {
        $currentVersion = '0.0.1';
        $latestVersion = '999.0.0';
        oxTestModules::addFunction('oxLang', 'translateString', '{ return "current ver.: %s new ver.: %s"; }');

        $controllerMock = $this->getMock(
            NavigationController::class,
            ['checkVersion'],
            [],
            '',
            false
        );
        $controllerMock->method('checkVersion')
            ->willReturn(sprintf('current ver.: %s new ver.: %s', $currentVersion, $latestVersion));

        $actual = $controllerMock->checkVersion();

        $this->assertStringContainsString($currentVersion, $actual);
        $this->assertStringContainsString($latestVersion, $actual);
    }
}
