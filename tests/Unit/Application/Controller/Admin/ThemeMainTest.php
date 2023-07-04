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

use OxidEsales\Eshop\Application\Controller\Admin\ThemeMain;
use OxidEsales\EshopCommunity\Core\Theme;

use Exception;
use OxidTestCase;
use oxTestModules;

/**
 * Tests for Shop_Config class
 */
class ThemeMainTest extends OxidTestCase
{
    /**
     * Theme_Main::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $this->getConfig()->setConfigParam('sTheme', 'wave');

        // testing..
        $oView = oxNew('Theme_Main');
        $this->assertEquals('theme_main.tpl', $oView->render());

        $aViewData = $oView->getViewData();

        $this->assertTrue(isset($aViewData['oTheme']));
        $this->assertTrue($aViewData['oTheme'] instanceof Theme);
        $this->assertEquals('wave', $aViewData['oTheme']->getInfo('id'));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSetTheme()
    {
        $oTM = $this->getMock(ThemeMain::class, array('getEditObjectId'));
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
        $oTM = $this->getMock(ThemeMain::class, array('themeInConfigFile'));
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
        $oConfig               = oxNew('oxConfig');
        $oConfig->sTheme       = 'azure';
        $oConfig->sCustomTheme = null;

        $oView = oxNew('Theme_Main');
        $oView->setConfig($oConfig);
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sTheme.');
    }

    /**
     * Check if theme checking works correct when only sCustomTheme is set in config.
     */
    public function testThemeConfigExceptionSCustomTheme()
    {
        $oConfig               = oxNew('oxConfig');
        $oConfig->sTheme       = null;
        $oConfig->sCustomTheme = 'someTheme';

        $oView = oxNew('Theme_Main');
        $oView->setConfig($oConfig);
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sCustomTheme.');
    }

    /**
     * Check if theme checking works correct when sTheme and sCustomTheme is set in config.
     */
    public function testThemeConfigExceptionSThemeSCustomTheme()
    {
        $oConfig               = oxNew('oxConfig');
        $oConfig->sTheme       = 'azure';
        $oConfig->sCustomTheme = 'someTheme';

        $oView = oxNew('Theme_Main');
        $oView->setConfig($oConfig);
        $this->assertEquals(true, $oView->themeInConfigFile(), 'Should return true as there is sTheme and sCustomTheme.');
    }
}
