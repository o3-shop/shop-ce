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

/**
 * Tests for sysreq_main class
 */
class DiagnosticsMainTest extends \OxidTestCase
{
    use \OxidEsales\EshopCommunity\Tests\Unit\ExitHandlerTestTrait;
    /**
     * sysreq_main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Diagnostics_Main');
        $this->assertEquals('diagnostics_form.tpl', $oView->render());
    }

    public function testDownloadResultFileExitsWithZero()
    {
        $this->installFakeExitHandler();

        $output = $this->getMock(
            \OxidEsales\Eshop\Application\Model\DiagnosticsOutput::class,
            ['downloadResultFile']
        );
        $output->expects($this->once())->method('downloadResultFile');

        $controller = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\DiagnosticsMain::class);

        $refProp = new \ReflectionProperty(
            \OxidEsales\EshopCommunity\Application\Controller\Admin\DiagnosticsMain::class,
            '_oOutput'
        );
        $refProp->setAccessible(true);
        $refProp->setValue($controller, $output);

        try {
            $controller->downloadResultFile();
            $this->fail('Expected ExitCalledException');
        } catch (\OxidEsales\Eshop\Core\Exception\ExitCalledException $e) {
            $this->assertSame(0, $e->getCode());
        }
    }
}
