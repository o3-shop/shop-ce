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
 * Tests for Shop_Config class
 */
class ThemeConfigTest extends \OxidTestCase
{
    /**
     * Shop_Config::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        $oView = oxNew('Theme_Config');
        $this->assertEquals('theme_config.tpl', $oView->render());
    }

    /**
     * Shop_Config::testGetModuleForConfigVars() test case
     *
     * @return null
     */
    public function testGetModuleForConfigVars()
    {
        $sThemeName = 'testtheme';
        $oTheme_Config = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\ThemeConfiguration::class, ['getEditObjectId']);
        $oTheme_Config->expects($this->any())->method('getEditObjectId')->will($this->returnValue($sThemeName));
        $this->assertEquals('theme:' . $sThemeName, $oTheme_Config->UNITgetModuleForConfigVars());
    }

    /**
     * Shop_Config::testSaveConfVars() test case
     *
     * @return null
     */
    public function testSaveConfVars()
    {
        $sName = 'someName';
        $sValue = 'someValue';
        $sThemeName = 'testtheme';

        // Set request params for each config type
        $aParams = [$sName => $sValue];
        $this->setRequestParameter('confbools', $aParams);
        $this->setRequestParameter('confstrs', $aParams);
        $this->setRequestParameter('confarrs', $aParams);
        $this->setRequestParameter('confaarrs', $aParams);
        $this->setRequestParameter('confselects', $aParams);

        // Track saveShopConfVar calls
        \oxTestModules::addFunction('oxConfig', 'saveShopConfVar', '{ if (!isset($this->_aSavedVars)) { $this->_aSavedVars = []; } $this->_aSavedVars[] = func_get_args(); }');

        /** @var Theme_Config|PHPUnit\Framework\MockObject\MockObject $oTheme_Config */
        $oTheme_Config = $this->getMock(
            \OxidEsales\Eshop\Application\Controller\Admin\ThemeConfiguration::class,
            ['getEditObjectId', '_serializeConfVar'],
            [],
            '',
            false
        );
        $oTheme_Config->expects($this->atLeastOnce())->method('getEditObjectId')->will($this->returnValue($sThemeName));
        $oTheme_Config->expects($this->atLeastOnce())->method('_serializeConfVar')->will($this->returnValue($sValue));

        $oTheme_Config->saveConfVars();
    }
}
