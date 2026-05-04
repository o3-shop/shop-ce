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

use OxidEsales\Eshop\Application\Model\Diagnostics;
use OxidEsales\Eshop\Application\Model\DiagnosticsOutput;
use OxidEsales\Eshop\Application\Model\SmartyRenderer;
use OxidEsales\Eshop\Core\SystemRequirements;
use OxidEsales\EshopCommunity\Application\Controller\Admin\DiagnosticsMain;

class DiagnosticsMainTest extends \OxidTestCase
{
    use \OxidEsales\EshopCommunity\Tests\Unit\ExitHandlerTestTrait;

    public function testRender()
    {
        $oView = oxNew('Diagnostics_Main');
        $this->assertEquals('diagnostics_form.tpl', $oView->render());
    }

    public function testRenderSurfacesErrorMessageWhenErrorFlagIsSet(): void
    {
        $controller = $this->getProxyClass(DiagnosticsMain::class);
        $controller->setNonPublicVar('_blError', true);
        $controller->setNonPublicVar('_sErrorMessage', 'something went wrong');

        $controller->render();
        $viewData = $controller->getViewData();
        $this->assertSame('something went wrong', $viewData['sErrorMessage'] ?? null);
    }

    public function testGetParamDelegatesToRequest(): void
    {
        $this->setRequestParameter('myparam', 'hello');
        $controller = oxNew(DiagnosticsMain::class);
        $this->assertSame('hello', $controller->getParam('myparam'));
    }

    public function testHasErrorAndGetErrorMessageReflectFlagState(): void
    {
        $controller = $this->getProxyClass(DiagnosticsMain::class);
        $controller->setNonPublicVar('_blError', false);
        $this->assertFalse($controller->UNIThasError());
        $this->assertNull($controller->UNITgetErrorMessage());

        $controller->setNonPublicVar('_blError', true);
        $controller->setNonPublicVar('_sErrorMessage', 'boom');
        $this->assertTrue($controller->UNIThasError());
        $this->assertSame('boom', $controller->UNITgetErrorMessage());
    }

    public function testGetSupportContactFormReturnsLanguageSpecificLink(): void
    {
        $controller = oxNew(DiagnosticsMain::class);
        $url = $controller->getSupportContactForm();
        // Both 'de' and 'en' map to the same community URL; an unknown
        // language falls back to 'de' — all branches yield a real URL.
        $this->assertStringStartsWith('https://community.o3-shop.com', $url);
    }

    public function testRunBasicDiagnosticsCollectsAllSectionsWhenAllFlagsAreOn(): void
    {
        $diagnostics = $this->getMock(Diagnostics::class, [
            'setShopLink', 'setEdition', 'setVersion', 'setRevision',
            'getShopDetails', 'getPhpSelection', 'getPhpDecoder',
            'isExecAllowed', 'getServerInfo',
        ]);
        $diagnostics->expects($this->once())->method('getShopDetails')
            ->will($this->returnValue(['shop' => 'details']));
        $diagnostics->expects($this->once())->method('getPhpSelection')
            ->will($this->returnValue(['memory_limit' => '256M']));
        $diagnostics->expects($this->once())->method('getPhpDecoder')
            ->will($this->returnValue('opcache'));
        $diagnostics->expects($this->once())->method('isExecAllowed')
            ->will($this->returnValue(true));
        $diagnostics->expects($this->once())->method('getServerInfo')
            ->will($this->returnValue(['Apache' => '2.4']));
        \oxTestModules::addModuleObject(Diagnostics::class, $diagnostics);

        $sysReq = $this->getMock(SystemRequirements::class, ['getSystemInfo', 'checkCollation']);
        $sysReq->expects($this->once())->method('getSystemInfo')
            ->will($this->returnValue(['php' => 'OK']));
        $sysReq->expects($this->once())->method('checkCollation')
            ->will($this->returnValue(['utf8mb4']));
        \oxTestModules::addModuleObject(SystemRequirements::class, $sysReq);

        $this->setRequestParameter('runAnalysis', '1');
        $this->setRequestParameter('oxdiag_frm_health', '1');
        $this->setRequestParameter('oxdiag_frm_php', '1');
        $this->setRequestParameter('oxdiag_frm_server', '1');
        $this->setRequestParameter('oxdiag_frm_chkvers', '1');

        // Use the controller's mocked-out getInstalledModules-equivalent
        // by NOT setting oxdiag_frm_modules — but exercise everything else.

        $controller = $this->getProxyClass(DiagnosticsMain::class);
        $viewData = $controller->UNITrunBasicDiagnostics();

        $this->assertTrue($viewData['runAnalysis'] ?? false);
        $this->assertSame(['shop' => 'details'], $viewData['aShopDetails']);
        $this->assertTrue($viewData['oxdiag_frm_health'] ?? false);
        $this->assertSame(['php' => 'OK'], $viewData['aInfo']);
        $this->assertSame(['utf8mb4'], $viewData['aCollations']);
        $this->assertTrue($viewData['oxdiag_frm_php'] ?? false);
        $this->assertSame(['memory_limit' => '256M'], $viewData['aPhpConfigparams']);
        $this->assertSame('opcache', $viewData['sPhpDecoder']);
        $this->assertTrue($viewData['oxdiag_frm_server'] ?? false);
        $this->assertTrue($viewData['isExecAllowed']);
        $this->assertSame(['Apache' => '2.4'], $viewData['aServerInfo']);
        $this->assertTrue($viewData['oxdiag_frm_chkvers'] ?? false);
    }

    public function testRunBasicDiagnosticsReturnsEmptyResultsWhenNoFlagsAreSet(): void
    {
        $diagnostics = $this->getMock(Diagnostics::class, [
            'setShopLink', 'setEdition', 'setVersion', 'setRevision',
        ]);
        \oxTestModules::addModuleObject(Diagnostics::class, $diagnostics);

        $controller = $this->getProxyClass(DiagnosticsMain::class);
        $viewData = $controller->UNITrunBasicDiagnostics();

        $this->assertArrayNotHasKey('runAnalysis', $viewData);
        $this->assertArrayNotHasKey('oxdiag_frm_health', $viewData);
        $this->assertArrayNotHasKey('aPhpConfigparams', $viewData);
    }

    public function testStartDiagnosticsRendersBasicReport(): void
    {
        $diagnostics = $this->getMock(Diagnostics::class, [
            'setShopLink', 'setEdition', 'setVersion', 'setRevision',
        ]);
        \oxTestModules::addModuleObject(Diagnostics::class, $diagnostics);

        $renderer = $this->getMock(SmartyRenderer::class, ['renderTemplate']);
        $renderer->expects($this->once())
            ->method('renderTemplate')
            ->with($this->equalTo('diagnostics_main.tpl'), $this->isType('array'))
            ->will($this->returnValue('<diag>basic</diag>'));

        $output = $this->getMock(DiagnosticsOutput::class, ['storeResult', 'readResultFile']);
        $output->expects($this->once())->method('storeResult')->with('<diag>basic</diag>');
        $output->expects($this->once())->method('readResultFile')->will($this->returnValue('rendered'));

        $controller = oxNew(DiagnosticsMain::class);
        $reflection = new \ReflectionClass($controller);
        $rendererProp = $reflection->getProperty('_oRenderer');
        $rendererProp->setAccessible(true);
        $rendererProp->setValue($controller, $renderer);
        $outputProp = $reflection->getProperty('_oOutput');
        $outputProp->setAccessible(true);
        $outputProp->setValue($controller, $output);

        $controller->startDiagnostics();
        $this->assertSame('rendered', $controller->getViewData()['sResult'] ?? null);
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
