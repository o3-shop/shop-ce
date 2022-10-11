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

use \oxRegistry;

/**
 * Tests for content class
 */
class ClearcookiesTest extends \OxidTestCase
{
    protected $_oObj = null;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test view render.
     *
     * @return null
     */
    public function testRender()
    {
        $_SERVER['HTTP_COOKIE'] = "shop=1";

        $oView = oxNew('ClearCookies');

        $oUtilsServer = $this->getMock(\OxidEsales\Eshop\Core\UtilsServer::class, array('setOxCookie'));
        $oUtilsServer->expects($this->at(0))->method('setOxCookie')->with($this->equalTo('shop'));
        $oUtilsServer->expects($this->at(1))->method('setOxCookie')->with($this->equalTo('language'));
        $oUtilsServer->expects($this->at(2))->method('setOxCookie')->with($this->equalTo('displayedCookiesNotification'));
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsServer::class, $oUtilsServer);

        $this->assertEquals('page/info/clearcookies.tpl', $oView->render());
    }

    /**
     * Testing Contact::getBreadCrumb()
     *
     * @return null
     */
    public function testGetBreadCrumb()
    {
        $oView = oxNew('ClearCookies');
        $this->assertEquals(1, count($oView->getBreadCrumb()));
    }
}
