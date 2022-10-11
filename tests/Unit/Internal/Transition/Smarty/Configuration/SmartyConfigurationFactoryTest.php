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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Smarty\Configuration;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartyPluginsDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartyPrefiltersDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartyResourcesDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartySecuritySettingsDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartySettingsDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\SmartyContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Configuration\SmartyConfigurationFactory;

class SmartyConfigurationFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigurationWithSecuritySettingsOff()
    {
        $factory = new SmartyConfigurationFactory(
            $this->getSmartyContextMock(false),
            $this->getSmartySettingsDataProviderMock(),
            $this->getSmartySecuritySettingsDataProviderMock(),
            $this->getSmartyResourcesDataProviderMock(),
            $this->getSmartyPrefiltersDataProviderMock(),
            $this->getSmartyPluginsDataProviderMock()
        );
        $configuration = $factory->getConfiguration();

        $this->assertSame(['testSetting'], $configuration->getSettings());
        $this->assertSame([], $configuration->getSecuritySettings());
        $this->assertSame(['testResources'], $configuration->getResources());
        $this->assertSame(['testPlugins'], $configuration->getPlugins());
        $this->assertSame(['testPrefilters'], $configuration->getPrefilters());
    }

    public function testGetConfigurationWithSecuritySettingsOn()
    {
        $factory = new SmartyConfigurationFactory(
            $this->getSmartyContextMock(true),
            $this->getSmartySettingsDataProviderMock(),
            $this->getSmartySecuritySettingsDataProviderMock(),
            $this->getSmartyResourcesDataProviderMock(),
            $this->getSmartyPrefiltersDataProviderMock(),
            $this->getSmartyPluginsDataProviderMock()
        );
        $configuration = $factory->getConfiguration();

        $this->assertSame(['testSetting'], $configuration->getSettings());
        $this->assertSame(['testSecuritySetting'], $configuration->getSecuritySettings());
        $this->assertSame(['testResources'], $configuration->getResources());
        $this->assertSame(['testPlugins'], $configuration->getPlugins());
        $this->assertSame(['testPrefilters'], $configuration->getPrefilters());
    }

    private function getSmartyContextMock($securityMode = false): SmartyContextInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartyContextInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getTemplateSecurityMode')
            ->willReturn($securityMode);

        return $smartyContextMock;
    }

    private function getSmartySettingsDataProviderMock(): SmartySettingsDataProviderInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartySettingsDataProviderInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getSettings')
            ->willReturn(['testSetting']);

        return $smartyContextMock;
    }

    private function getSmartySecuritySettingsDataProviderMock(): SmartySecuritySettingsDataProviderInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartySecuritySettingsDataProviderInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getSecuritySettings')
            ->willReturn(['testSecuritySetting']);

        return $smartyContextMock;
    }

    private function getSmartyResourcesDataProviderMock(): SmartyResourcesDataProviderInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartyResourcesDataProviderInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getResources')
            ->willReturn(['testResources']);

        return $smartyContextMock;
    }

    private function getSmartyPluginsDataProviderMock(): SmartyPluginsDataProviderInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartyPluginsDataProviderInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getPlugins')
            ->willReturn(['testPlugins']);

        return $smartyContextMock;
    }

    private function getSmartyPrefiltersDataProviderMock(): SmartyPrefiltersDataProviderInterface
    {
        $smartyContextMock = $this
            ->getMockBuilder(SmartyPrefiltersDataProviderInterface::class)
            ->getMock();

        $smartyContextMock
            ->method('getPrefilterPlugins')
            ->willReturn(['testPrefilters']);

        return $smartyContextMock;
    }
}
