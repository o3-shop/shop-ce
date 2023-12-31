<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Smarty;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyContext;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;

class SmartyContextTest extends \PHPUnit\Framework\TestCase
{
    public function getTemplateEngineDebugModeDataProvider()
    {
        return [
            [1, true],
            [3, true],
            [4, true],
            [6, false],
            ['two', false],
            ['5', false]
        ];
    }

    /**
     * @param mixed $configValue
     * @param bool  $debugMode
     *
     * @dataProvider getTemplateEngineDebugModeDataProvider
     */
    public function testGetTemplateEngineDebugMode($configValue, $debugMode)
    {
        $config = $this->getConfigMock();
        $config->method('getConfigParam')
            ->with('iDebug')
            ->will($this->returnValue($configValue));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame($debugMode, $smartyContext->getTemplateEngineDebugMode());
    }

    public function showTemplateNamesDataProvider()
    {
        return [
            [8, false, true],
            [8, true, false],
            [5, false, false],
            [5, false, false],
        ];
    }

    /**
     * @param mixed $configValue
     * @param bool  $adminMode
     * @param bool  $result
     *
     * @dataProvider showTemplateNamesDataProvider
     */
    public function testShowTemplateNames($configValue, $adminMode, $result)
    {
        $config = $this->getConfigMock();
        $config->method('getConfigParam')
            ->with('iDebug')
            ->will($this->returnValue($configValue));
        $config->method('isAdmin')
            ->will($this->returnValue($adminMode));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame($result, $smartyContext->showTemplateNames());
    }

    public function testGetTemplateSecurityMode()
    {
        $config = $this->getConfigMock();
        $config->method('isDemoShop')
            ->will($this->returnValue(true));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame(true, $smartyContext->getTemplateSecurityMode());
    }

    public function testGetTemplateCompileCheckMode()
    {
        $config = $this->getConfigMock();
        $config->method('getConfigParam')
            ->with('blCheckTemplates')
            ->will($this->returnValue(true));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame(true, $smartyContext->getTemplateCompileCheckMode());
    }

    public function testGetTemplateCompileCheckModeInProductiveMode()
    {
        $config = $this->getConfigMock();
        $config->method('getConfigParam')
            ->with('blCheckTemplates')
            ->will($this->returnValue(true));
        $config->method('isProductiveMode')
            ->will($this->returnValue(true));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertFalse($smartyContext->getTemplateCompileCheckMode());
    }

    public function testGetTemplatePhpHandlingMode()
    {
        $config = $this->getConfigMock();
        $config->method('getConfigParam')
            ->with('iSmartyPhpHandling')
            ->will($this->returnValue(1));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame(1, $smartyContext->getTemplatePhpHandlingMode());
    }

    public function testGetSmartyPluginDirectories()
    {
        $config = $this->getConfigMock();

        $utilsView = $this->getUtilsViewMock();
        $utilsView->method('getSmartyPluginDirectories')
            ->will($this->returnValue(['CoreDir/Smarty/Plugin']));

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame(['CoreDir/Smarty/Plugin'], $smartyContext->getSmartyPluginDirectories());
    }

    public function testGetTemplatePath()
    {
        $config = $this->getConfigMock();
        $config->method('isAdmin')
            ->will($this->returnValue(false));
        $config->method('getTemplatePath')
            ->with('testTemplate', false)
            ->will($this->returnValue('templatePath'));

        $utilsView = $this->getUtilsViewMock();

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame('templatePath', $smartyContext->getTemplatePath('testTemplate'));
    }

    public function testGetTemplateCompileDirectory()
    {
        $config = $this->getConfigMock();
        $utilsView = $this->getUtilsViewMock();
        $utilsView->method('getSmartyDir')
        ->will($this->returnValue('testCompileDir'));

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame('testCompileDir', $smartyContext->getTemplateCompileDirectory());
    }

    public function testGetTemplateDirectories()
    {
        $config = $this->getConfigMock();
        $utilsView = $this->getUtilsViewMock();
        $utilsView->method('getTemplateDirs')
            ->will($this->returnValue(['testTemplateDir']));

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame(['testTemplateDir'], $smartyContext->getTemplateDirectories());
    }

    public function testGetTemplateCompileId()
    {
        $config = $this->getConfigMock();
        $utilsView = $this->getUtilsViewMock();
        $utilsView->method('getTemplateCompileId')
            ->will($this->returnValue('testCompileId'));

        $smartyContext = new SmartyContext(new BasicContextStub(), $config, $utilsView);
        $this->assertSame('testCompileId', $smartyContext->getTemplateCompileId());
    }

    public function testGetSourcePath()
    {
        $config = $this->getConfigMock();
        $utilsView = $this->getUtilsViewMock();
        $basicContext = new BasicContextStub();
        $basicContext->setSourcePath('testSourcePath');

        $smartyContext = new SmartyContext($basicContext, $config, $utilsView);
        $this->assertSame('testSourcePath', $smartyContext->getSourcePath());
    }

    /**
     * @return Config
     */
    private function getConfigMock()
    {
        $configMock = $this
            ->getMockBuilder(Config::class)
            ->getMock();

        return $configMock;
    }

    /**
     * @return UtilsView
     */
    private function getUtilsViewMock()
    {
        $utilsViewMock = $this
            ->getMockBuilder(UtilsView::class)
            ->getMock();

        return $utilsViewMock;
    }
}
