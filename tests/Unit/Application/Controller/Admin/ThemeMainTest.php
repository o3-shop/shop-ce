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
use OxidEsales\Eshop\Application\Controller\Admin\ThemeMain;
use OxidEsales\EshopCommunity\Core\Theme;
use OxidTestCase;
use oxTestModules;

/**
 * Tests for Shop_Config class
 */
class ThemeMainTest extends OxidTestCase
{
    /**
     * The §356a `blShowRevocationForm` row is seeded into oxconfig by
     * `initial_data.sql` (task 1.7). Reset it to `false` for each test in
     * this class so the theme-switch `setTheme()` flow is not gated by
     * the unrelated revocation feature — gate-specific behaviour is
     * asserted by the `testRevocationGate*` tests added below.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getConfig()->setConfigParam('blShowRevocationForm', false);
    }

    /**
     * Theme_Main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->getConfig()->setConfigParam('sTheme', 'o3-theme');

        // testing..
        $oView = oxNew('Theme_Main');
        $this->assertEquals('theme_main.tpl', $oView->render());

        $aViewData = $oView->getViewData();

        $this->assertTrue(isset($aViewData['oTheme']));
        $this->assertTrue($aViewData['oTheme'] instanceof Theme);
        $this->assertEquals('o3-theme', $aViewData['oTheme']->getInfo('id'));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSetTheme()
    {
        $oTM = $this->getMock(ThemeMain::class, ['getEditObjectId']);
        $oTM->expects($this->any())->method('getEditObjectId')->will($this->returnValue('azure'));

        oxTestModules::addFunction('oxTheme', 'load($name)', '{if ($name != "azure") throw new Exception("FAIL TO LOAD"); return true;}');
        oxTestModules::addFunction('oxTheme', 'activate', '{throw new Exception("OK");}');

        try {
            $oTM->setTheme();
            $this->fail('should have called overriden activate');
        } catch (Exception $e) {
            $this->assertEquals('OK', $e->getMessage());
        }
    }

    /**
     * Test if theme in config checking was called.
     */
    public function testThemeConfigExceptionInRender()
    {
        $oTM = $this->getMock(ThemeMain::class, ['themeInConfigFile']);
        $oTM->expects($this->once())->method('themeInConfigFile');
        $oTM->render();
    }

    /**
     * Check if theme checking works correct.
     */
    public function testThemeConfigException()
    {
        $oView = oxNew('Theme_Main');
        $this->assertEquals(false, $oView->themeInConfigFile(), 'Should not be theme in config file by default.');
    }

    /**
     * Check if theme checking works correct when only sTheme is set in config.
     */
    public function testThemeConfigExceptionSTheme()
    {
        // Production code uses Registry::getConfig()->sTheme, so set it on the real config
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $oConfig->sTheme = 'azure';
        unset($oConfig->sCustomTheme);

        $oView = oxNew('Theme_Main');
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sTheme.');
    }

    /**
     * Check if theme checking works correct when only sCustomTheme is set in config.
     */
    public function testThemeConfigExceptionSCustomTheme()
    {
        // Production code uses Registry::getConfig()->sCustomTheme, so set it on the real config
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        unset($oConfig->sTheme);
        $oConfig->sCustomTheme = 'someTheme';

        $oView = oxNew('Theme_Main');
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sCustomTheme.');
    }

    /**
     * Check if theme checking works correct when sTheme and sCustomTheme is set in config.
     */
    public function testThemeConfigExceptionSThemeSCustomTheme()
    {
        // Production code uses Registry::getConfig(), so set properties on the real config
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $oConfig->sTheme = 'azure';
        $oConfig->sCustomTheme = 'someTheme';

        $oView = oxNew('Theme_Main');
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sTheme and sCustomTheme.');
    }

    /**
     * §356a template-presence gate (phase 8.3): when the revocation form
     * is OFF, the gate always passes — even if the prospective theme is
     * missing revocation assets. Validator must NOT be consulted.
     */
    public function testRevocationGateFeatureOffPasses()
    {
        $this->getConfig()->setConfigParam('blShowRevocationForm', false);

        $validator = $this->createMock(\OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator::class);
        $validator->expects($this->never())->method('validate');

        $controller = oxNew(ThemeMain::class);
        $controller->setRevocationTemplateValidator($validator);

        $r = new \ReflectionMethod(ThemeMain::class, 'revocationActivationGatePasses');
        $r->setAccessible(true);
        $this->assertTrue($r->invoke($controller, 'wave'));
    }

    /**
     * §356a gate: feature on, validator returns no missing assets — gate
     * passes; theme activation proceeds.
     */
    public function testRevocationGateNoMissingAssetsPasses()
    {
        $this->getConfig()->setConfigParam('blShowRevocationForm', true);

        $validator = $this->createMock(\OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator::class);
        $validator->expects($this->once())->method('validate')->willReturn([]);

        $controller = oxNew(ThemeMain::class);
        $controller->setRevocationTemplateValidator($validator);

        $r = new \ReflectionMethod(ThemeMain::class, 'revocationActivationGatePasses');
        $r->setAccessible(true);
        $this->assertTrue($r->invoke($controller, 'wave'));
    }

    /**
     * §356a gate: feature on, validator reports a missing asset — gate
     * rejects. Each missing-asset hint is pushed to the admin error
     * display so the operator sees the concrete fix list.
     */
    public function testRevocationGateMissingAssetsRejects()
    {
        $this->getConfig()->setConfigParam('blShowRevocationForm', true);

        $missing = new \OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAsset(
            'page-template',
            'source/Application/views/azure/tpl/page/revocation/revocation.tpl',
            null,
            'Install the missing page template under the active theme.'
        );
        $validator = $this->createMock(\OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator::class);
        $validator->expects($this->once())->method('validate')->willReturn([$missing]);

        $controller = oxNew(ThemeMain::class);
        $controller->setRevocationTemplateValidator($validator);

        $r = new \ReflectionMethod(ThemeMain::class, 'revocationActivationGatePasses');
        $r->setAccessible(true);
        $this->assertFalse($r->invoke($controller, 'azure'));
    }

    /**
     * @return \OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProviderInterface
     */
    private function engineProvider(string $engine)
    {
        $provider = $this->createMock(\OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine\ThemeTemplateEngineProviderInterface::class);
        $provider->method('getEngineName')->willReturn($engine);

        return $provider;
    }

    private function controllerWithCheckers(string $engine, array $moduleIds, ?array $mismatch): ThemeMain
    {
        $moduleChecker = $this->createMock(\OxidEsales\EshopCommunity\Internal\Framework\Templating\Compatibility\ModuleTwigCompatibilityCheckerInterface::class);
        $moduleChecker->method('getModulesWithoutTwigTemplates')->willReturn($moduleIds);
        $mismatchChecker = $this->createMock(\OxidEsales\EshopCommunity\Internal\Framework\Templating\Compatibility\ThemeEngineMismatchCheckerInterface::class);
        $mismatchChecker->method('getMismatch')->willReturn($mismatch);

        $controller = oxNew(ThemeMain::class);
        $controller->setTwigCompatibilityServices($this->engineProvider($engine), $moduleChecker, $mismatchChecker);

        return $controller;
    }

    private function expectDisplayedMessages(array $needles): void
    {
        $messages = [];
        $utilsView = $this->createMock(\OxidEsales\Eshop\Core\UtilsView::class);
        $utilsView->method('addErrorToDisplay')->willReturnCallback(
            function ($exception) use (&$messages) {
                $messages[] = $exception->getOxMessage();
            }
        );
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\UtilsView::class, $utilsView);
        $this->messages = &$messages;
    }

    public function testRenderWarnsAboutModulesWithoutTwigTemplatesOnTwigTheme()
    {
        $this->getConfig()->setConfigParam('sTheme', 'o3-theme');
        $this->expectDisplayedMessages([]);
        $controller = $this->controllerWithCheckers('twig', ['smartyonly', 'other'], null);

        $this->assertSame('theme_main.tpl', $controller->render());
        $this->assertCount(1, $this->messages);
        $this->assertStringContainsString('smartyonly, other', $this->messages[0]);
    }

    public function testRenderDoesNotWarnOnSmartyTheme()
    {
        $this->getConfig()->setConfigParam('sTheme', 'o3-theme');
        $this->expectDisplayedMessages([]);
        $controller = $this->controllerWithCheckers('smarty', ['smartyonly'], null);

        $controller->render();
        $this->assertSame([], $this->messages);
    }

    public function testRenderWarnsAboutEngineMismatch()
    {
        $this->getConfig()->setConfigParam('sTheme', 'o3-theme');
        $this->expectDisplayedMessages([]);
        $controller = $this->controllerWithCheckers(
            'smarty',
            [],
            ['child' => 'mychild', 'parent' => 'o3-theme', 'childEngine' => 'smarty', 'parentEngine' => 'twig']
        );

        $controller->render();
        $this->assertCount(1, $this->messages);
        $this->assertStringContainsString('mychild', $this->messages[0]);
        $this->assertStringContainsString('o3-theme', $this->messages[0]);
    }

    public function testSetThemeStillActivatesAndWarnsWhenActivatingTwigThemeWithSmartyOnlyModules()
    {
        $this->expectDisplayedMessages([]);
        $controller = $this->controllerWithCheckers('twig', ['smartyonly'], null);
        $this->setRequestParameter('oxid', 'o3-theme');
        oxTestModules::addFunction('oxTheme', 'load($name)', '{return true;}');
        oxTestModules::addFunction('oxTheme', 'activate', '{$GLOBALS["themeActivated"] = true; return true;}');
        $GLOBALS['themeActivated'] = false;

        $controller->setTheme();
        $this->assertTrue($GLOBALS['themeActivated']);
        $this->assertCount(1, $this->messages);
        $this->assertStringContainsString('smartyonly', $this->messages[0]);
    }

    /** @var string[] */
    private $messages = [];
}
