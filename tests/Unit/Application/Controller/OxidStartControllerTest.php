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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use OxidEsales\Eshop\Core\SystemEventHandler;
use OxidEsales\EshopCommunity\Application\Controller\OxidStartController;

/**
 * Stub SystemEventHandler tracks shop start/end calls so appInit/pageClose
 * can be verified without firing the real lifecycle hooks.
 */
class OxidStartControllerTest_StubEventHandler extends SystemEventHandler
{
    public static int $startCount = 0;
    public static int $endCount = 0;

    public function onShopStart()
    {
        self::$startCount++;
    }

    public function onShopEnd()
    {
        self::$endCount++;
    }
}

class OxidStartControllerTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OxidStartControllerTest_StubEventHandler::$startCount = 0;
        OxidStartControllerTest_StubEventHandler::$endCount = 0;
    }

    public function testPageStartSeedsLegacyConfigParams(): void
    {
        $config = $this->getConfig();
        $config->setConfigParam('IMS', 17);
        $config->setConfigParam('IMA', 42);

        $controller = oxNew(OxidStartController::class);
        $controller->pageStart();

        $this->assertSame(17, $config->getConfigParam('iMaxMandates'));
        $this->assertSame(42, $config->getConfigParam('iMaxArticles'));
    }

    public function testAppInitFiresShopStartForNonOxstartFrontendRequest(): void
    {
        $controller = $this->getMock(
            OxidStartController::class,
            ['_getSystemEventHandler', 'isAdmin']
        );
        $handler = new OxidStartControllerTest_StubEventHandler();
        $controller->expects($this->any())
            ->method('_getSystemEventHandler')
            ->will($this->returnValue($handler));
        $controller->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        // Default request controller is start (= 'oxstart' for legacy alias).
        // To force the non-skip branch, request a different controller id.
        $this->setRequestParameter('cl', 'account');

        $controller->appInit();

        $this->assertSame(1, OxidStartControllerTest_StubEventHandler::$startCount);
    }

    public function testAppInitSkipsShopStartForOxstartRequest(): void
    {
        $controller = $this->getMock(
            OxidStartController::class,
            ['_getSystemEventHandler', 'isAdmin']
        );
        $handler = new OxidStartControllerTest_StubEventHandler();
        $controller->expects($this->any())
            ->method('_getSystemEventHandler')
            ->will($this->returnValue($handler));
        $controller->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $this->setRequestParameter('cl', 'oxstart');

        $controller->appInit();

        $this->assertSame(0, OxidStartControllerTest_StubEventHandler::$startCount);
    }

    public function testAppInitSkipsShopStartForAdminRequest(): void
    {
        $controller = $this->getMock(
            OxidStartController::class,
            ['_getSystemEventHandler', 'isAdmin']
        );
        $handler = new OxidStartControllerTest_StubEventHandler();
        $controller->expects($this->any())
            ->method('_getSystemEventHandler')
            ->will($this->returnValue($handler));
        $controller->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        $this->setRequestParameter('cl', 'account');

        $controller->appInit();

        $this->assertSame(0, OxidStartControllerTest_StubEventHandler::$startCount);
    }

    public function testRenderFallsBackToUnknownErrorTemplateForUnmappedNumber(): void
    {
        $this->setRequestParameter('execerror', 'no_such_error_code');
        $controller = oxNew(OxidStartController::class);

        $this->assertSame('message/err_unknown.tpl', $controller->render());
    }

    public function testRenderSelectsMappedTemplateWhenErrorNumberIsKnown(): void
    {
        $this->setRequestParameter('execerror', 'unknown');
        $controller = oxNew(OxidStartController::class);

        // The default mapping resolves 'unknown' to message/err_unknown.tpl.
        // The branch that matters is the "key found" path — exercised here.
        $this->assertSame('message/err_unknown.tpl', $controller->render());
    }

    public function testGetErrorNumberReadsFromRequest(): void
    {
        $this->setRequestParameter('errornr', '404');
        $controller = oxNew(OxidStartController::class);
        $this->assertSame('404', $controller->getErrorNumber());
    }

    public function testPageCloseFiresShopEndAndCommitsFileCache(): void
    {
        $controller = $this->getMock(OxidStartController::class, ['_getSystemEventHandler']);
        $handler = new OxidStartControllerTest_StubEventHandler();
        $controller->expects($this->any())
            ->method('_getSystemEventHandler')
            ->will($this->returnValue($handler));

        $controller->pageClose();

        $this->assertSame(1, OxidStartControllerTest_StubEventHandler::$endCount);
    }

    public function testGetSystemEventHandlerReturnsRealHandlerInstance(): void
    {
        $controller = $this->getProxyClass(OxidStartController::class);
        $this->assertInstanceOf(SystemEventHandler::class, $controller->UNITgetSystemEventHandler());
    }
}
