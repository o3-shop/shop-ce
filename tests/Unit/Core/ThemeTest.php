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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use \oxTheme;

use \another;

class ThemeTest extends \OxidTestCase
{
    public function setup(): void
    {
        parent::setUp();
    }

    public function testLoadAndGetInfo()
    {
        $oTheme = $this->getProxyClass('oxTheme');
        $this->assertTrue($oTheme->load('wave'));

        foreach (array('id', 'title', 'description', 'thumbnail', 'version', 'author', 'active', 'settings') as $key) {
            $this->assertNotNull($oTheme->getInfo($key));
        }
        $this->assertNull($oTheme->getInfo('asdasdasd'));
        $this->assertEquals('wave', $oTheme->getInfo('id'));
    }

    public function testGetList()
    {
        $this->markTestSkipped('Review with D.S. In source/Application/views/ there is still azure. Remove that?');
        // Count themes in themes folder except admin
        $iCount = count(glob(oxPATH . "/Application/views/*", GLOB_ONLYDIR)) - 1;

        $aThemeList = $this->getProxyClass('oxTheme')->getList();

        $this->assertEquals($iCount, count($aThemeList));
        foreach ($aThemeList as $oTheme) {
            $this->assertTrue($oTheme instanceof \OxidEsales\EshopCommunity\Core\Theme);
        }
    }

    public function testActivateError()
    {
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('checkForActivationErrors'));
        $oTheme->expects($this->once())->method('checkForActivationErrors')->will($this->returnValue('Error Message'));
        $this->expectException(\OxidEsales\Eshop\Core\Exception\StandardException::class);
        $this->expectExceptionMessage('Error Message');
        $oTheme->activate();
    }

    public function testActivateMain()
    {
        $oConfig = $this->getMock('stdClass', array('saveShopConfVar'));
        $oConfig->expects($this->any())
            ->method('saveShopConfVar')
            ->withConsecutive(
                [$this->equalTo('str'), $this->equalTo('sTheme'), $this->equalTo('currentT')],
                [$this->equalTo('str'), $this->equalTo('sCustomTheme'), $this->equalTo('')]
            )
            ->willReturn(null);

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('checkForActivationErrors', 'getConfig', 'getInfo'));
        $oTheme->expects($this->once())
            ->method('checkForActivationErrors')
            ->willReturn(false);
        $oTheme->expects($this->any())
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('parentTheme')],
                [$this->equalTo('id')]
            )
            ->willReturnOnConsecutiveCalls('', 'currentT');
        $oTheme->expects($this->any())
            ->method('getConfig')
            ->willReturn($oConfig);

        $oTheme->activate();
    }


    public function testActivateChild()
    {
        $oConfig = $this->getMock('stdClass', array('saveShopConfVar'));
        $oConfig->expects($this->exactly(2))
            ->method('saveShopConfVar')
            ->withConsecutive(
                [$this->equalTo('str'), $this->equalTo('sTheme'), $this->equalTo('parentT')],
                [$this->equalTo('str'), $this->equalTo('sCustomTheme'), $this->equalTo('currentT')]
            )
            ->willReturn(null);

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('checkForActivationErrors', 'getConfig', 'getInfo'));
        $oTheme->expects($this->once())
            ->method('checkForActivationErrors')
            ->willReturn(false);
        $oTheme->expects($this->any()) // Changed from 2 to 3
        ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('parentTheme')],
                [$this->equalTo('id')],
                [$this->equalTo('settings')] // Add the missing call
            )
            ->willReturnOnConsecutiveCalls('parentT', 'currentT', 'someSettingsValue');

        $oTheme->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn($oConfig);

        $oTheme->activate();
    }


    public function testGetActiveThemeIdCustom()
    {
        $oConfig = $this->getMock('stdClass', array('getConfigParam'));
        $oConfig->expects($this->once())
            ->method('getConfigParam')
            ->with($this->equalTo('sCustomTheme'))
            ->willReturn('custom');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getConfig'));
        $oTheme->expects($this->any())
            ->method('getConfig')
            ->willReturn($oConfig);

        $this->assertEquals('custom', $oTheme->getActiveThemeId());
    }


    public function testGetActiveThemeIdMain()
    {
        $oConfig = $this->getMock('stdClass', array('getConfigParam'));
        $oConfig->expects($this->exactly(2))
            ->method('getConfigParam')
            ->withConsecutive(
                [$this->equalTo('sCustomTheme')],
                [$this->equalTo('sTheme')]
            )
            ->willReturnOnConsecutiveCalls('', 'maint');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getConfig'));
        $oTheme->expects($this->any())
            ->method('getConfig')
            ->willReturn($oConfig);

        $this->assertEquals('maint', $oTheme->getActiveThemeId());
    }



    public function testGetParentNull()
    {
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oTheme->expects($this->any())->method('getInfo')->with($this->equalTo('parentTheme'))->will($this->returnValue(''));
        $this->assertNull($oTheme->getParent());
    }

    public function testGetParent()
    {
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oTheme->expects($this->any())->method('getInfo')->with($this->equalTo('parentTheme'))->will($this->returnValue('wave'));
        $oParent = $oTheme->getParent();
        $this->assertTrue($oParent instanceof \OxidEsales\EshopCommunity\Core\Theme);
        $this->assertEquals('wave', $oParent->getInfo('id'));
    }

    public function testGetSettingsFromActivatedTheme()
    {
        $this->assertEquals(null, $this->getConfigParam('configParamFromThemeSettings'));

        $theme = $this->getProxyClass('oxTheme');
        $theme->setNonPublicVar("_aTheme", [
            'id'          => 'testTheme',
            'settings'    => [
                [
                    'group' => 'someGroup',
                    'name'  => 'configParamFromThemeSettings',
                    'type'  => 'str',
                    'value' => 'foobar',
                ],
            ],
        ]);

        $theme->activate();

        $this->assertEquals('foobar', $this->getConfigParam('configParamFromThemeSettings'));
    }

    public function testOverrideShopSettings()
    {
        $this->setConfigParam('shopSetting', 'startValue');
        $this->assertEquals('startValue', $this->getConfigParam('shopSetting'));

        $themeA = $this->getProxyClass('oxTheme');
        $themeA->setNonPublicVar("_aTheme", [
            'id'          => 'themeA',
            'settings'    => [
                [
                    'group' => 'someGroup',
                    'name'  => 'shopSetting',
                    'type'  => 'str',
                    'value' => 'finalValue',
                ],
            ],
        ]);
        $themeA->activate();

        $this->assertEquals('finalValue', $this->getConfigParam('shopSetting'));
    }

    public function testDontOverrideAlreadyChangedSettings()
    {
        $this->assertEquals(null, $this->getConfigParam('configParamFromThemeSettings'));

        $themeA = $this->getProxyClass('oxTheme');
        $themeA->setNonPublicVar("_aTheme", [
            'id'          => 'themeA',
            'settings'    => [
                [
                    'group' => 'someGroup',
                    'name'  => 'configParamFromThemeSettings',
                    'type'  => 'str',
                    'value' => 'foobar',
                ],
            ],
        ]);
        $themeA->activate();

        $this->assertEquals('themeA', $this->getConfigParam('sTheme'));
        $this->assertEquals('foobar', $this->getConfigParam('configParamFromThemeSettings'));

        $themeA->setNonPublicVar("_aTheme", [
            'id'          => 'themeA',
            'settings'    => [
                [
                    'group' => 'someGroup',
                    'name'  => 'configParamFromThemeSettings',
                    'type'  => 'str',
                    'value' => 'otherValue',
                ],
            ],
        ]);
        $themeA->activate();

        $this->assertEquals('foobar', $this->getConfigParam('configParamFromThemeSettings'));
    }

    public function testCheckForActivationErrorsNoParent()
    {
        // Test case 1: Theme not loaded (empty id)
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oTheme->expects($this->any())
            ->method('getInfo')
            ->with($this->equalTo('id'))
            ->willReturn('');
        $this->assertEquals('EXCEPTION_THEME_NOT_LOADED', $oTheme->checkForActivationErrors());

        // Test case 2: Parent theme not found
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->exactly(2))
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('id')],
                [$this->equalTo('parentTheme')]
            )
            ->willReturnOnConsecutiveCalls('asd', 'asd');
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $this->assertEquals('EXCEPTION_PARENT_THEME_NOT_FOUND', $oTheme->checkForActivationErrors());

        // Test case 3: Success case (no parent theme required)
        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->exactly(2))
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('id')],
                [$this->equalTo('parentTheme')]
            )
            ->willReturnOnConsecutiveCalls('asd', '');
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $this->assertFalse($oTheme->checkForActivationErrors());
    }


    public function testCheckForActivationErrorsCheckParent()
    {
        // Test case 1: Parent version unspecified
        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oParent->expects($this->once())
            ->method('getInfo')
            ->with($this->equalTo('version'))
            ->willReturn('');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->once())
            ->method('getInfo')
            ->with($this->equalTo('id'))
            ->willReturn('asd');
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn($oParent);
        $this->assertEquals('EXCEPTION_PARENT_VERSION_UNSPECIFIED', $oTheme->checkForActivationErrors());

        // Test case 2: Unspecified parent versions
        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oParent->expects($this->once())
            ->method('getInfo')
            ->with($this->equalTo('version'))
            ->willReturn('5');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->exactly(2))
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('id')],
                [$this->equalTo('parentVersions')]
            )
            ->willReturnOnConsecutiveCalls('asd', '');
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn($oParent);
        $this->assertEquals('EXCEPTION_UNSPECIFIED_PARENT_VERSIONS', $oTheme->checkForActivationErrors());

        // Test case 3: Parent version mismatch
        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oParent->expects($this->once())
            ->method('getInfo')
            ->with($this->equalTo('version'))
            ->willReturn('5');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->exactly(2))
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('id')],
                [$this->equalTo('parentVersions')]
            )
            ->willReturnOnConsecutiveCalls('asd', array(1, 2));
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn($oParent);
        $this->assertEquals('EXCEPTION_PARENT_VERSION_MISMATCH', $oTheme->checkForActivationErrors());

        // Test case 4: Success case
        $oParent = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo'));
        $oParent->expects($this->once())
            ->method('getInfo')
            ->with($this->equalTo('version'))
            ->willReturn('5');

        $oTheme = $this->getMock(\OxidEsales\Eshop\Core\Theme::class, array('getInfo', 'getParent'));
        $oTheme->expects($this->exactly(2))
            ->method('getInfo')
            ->withConsecutive(
                [$this->equalTo('id')],
                [$this->equalTo('parentVersions')]
            )
            ->willReturnOnConsecutiveCalls('asd', array(1, 2, 5));
        $oTheme->expects($this->once())
            ->method('getParent')
            ->willReturn($oParent);
        $this->assertFalse($oTheme->checkForActivationErrors());
    }


    public function testGetId()
    {
        $oTheme = oxNew('oxTheme');
        $oTheme->load("wave");

        $this->assertEquals('wave', $oTheme->getId());
    }

    /**
     * Test if getActiveThemeList gives correct list in simple case - one theme without extending
     */
    public function testGetActiveThemesListSimple()
    {
        $this->setConfigParam('sTheme', 'someTheme');

        $theme = oxNew('oxTheme');
        $this->assertEquals(['someTheme'], $theme->getActiveThemesList());
    }

    /**
     * Test if getActiveThemeList gives correct list if there are a theme which extends another theme
     */
    public function testGetActiveThemesListExtended()
    {
        $this->setConfigParam('sTheme', 'someBasicTheme');
        $this->setConfigParam('sCustomTheme', 'someImprovedTheme');

        $theme = oxNew('oxTheme');
        $this->assertEquals(['someBasicTheme', 'someImprovedTheme'], $theme->getActiveThemesList());
    }

    /**
     * Test if getActiveThemeList gives correct list if being in admin case
     */
    public function testGetActiveThemesListAdmin()
    {
        $this->setAdminMode(true);
        $this->setConfigParam('sTheme', 'someTheme');
        $this->setConfigParam('sCustomTheme', 'someCustomTheme');

        $theme = oxNew('oxTheme');
        $this->assertEquals(array(), $theme->getActiveThemesList());
    }
}
