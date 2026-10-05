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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Application\Controller\Admin\SystemInfoController;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;
use oxTestModules;
use Psr\Container\ContainerInterface;

class SysteminfoTest extends \OxidTestCase
{
    public function testRenderShowsAccessDeniedForNonMallAdmin(): void
    {
        oxTestModules::addFunction('oxUtils', 'showMessageAndExit', '{ return "Access denied !"; }');
        oxTestModules::addFunction('oxuser', 'loadAdminUser', '{ $this->oxuser__oxrights = new oxField( "justadmin" ); }');

        $oView = oxNew('systeminfo');
        $this->assertEquals('Access denied !', $oView->render());
    }

    public function testRenderRendersSystemInfoForMallAdminOnNonDemoShop(): void
    {
        oxTestModules::addFunction('oxUtils', 'showMessageAndExit', '{ $aA = func_get_args(); throw new \\RuntimeException("CAPTURED:" . $aA[0]); }');
        oxTestModules::addFunction('oxuser', 'loadAdminUser', '{ $this->oxuser__oxrights = new oxField( "malladmin" ); }');

        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer->expects($this->once())
            ->method('renderTemplate')
            ->with(
                $this->equalTo('systeminfo.tpl'),
                $this->callback(static function ($context) {
                    return is_array($context)
                        && array_key_exists('aSystemInfo', $context)
                        && array_key_exists('isdemo', $context);
                })
            )
            ->willReturn('<rendered>');

        $bridge = $this->createMock(TemplateRendererBridgeInterface::class);
        $bridge->method('getTemplateRenderer')->willReturn($renderer);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with(TemplateRendererBridgeInterface::class)
            ->willReturn($bridge);

        $controller = $this->getMock(SystemInfoController::class, ['getContainer']);
        $controller->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($container));

        try {
            $controller->render();
            $this->fail('Expected showMessageAndExit to short-circuit via exception.');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('CAPTURED:', $e->getMessage());
            // Rendered systeminfo template contribution must be present.
            $this->assertStringContainsString('<rendered>', $e->getMessage());
        }
    }

    public function testIsClassVariableVisibleHidesSensitiveFields(): void
    {
        $controller = $this->getProxyClass(SystemInfoController::class);

        // Sensitive vars are filtered out so they never reach the rendered output.
        $this->assertFalse($controller->isClassVariableVisible('oDB'));
        $this->assertFalse($controller->isClassVariableVisible('dbUser'));
        $this->assertFalse($controller->isClassVariableVisible('dbPwd'));
        $this->assertFalse($controller->isClassVariableVisible('aSerials'));
        $this->assertFalse($controller->isClassVariableVisible('sSerialNr'));

        // Anything else passes through.
        $this->assertTrue($controller->isClassVariableVisible('aLanguages'));
        $this->assertTrue($controller->isClassVariableVisible('sShopUrl'));
    }
}
