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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\SystemRequirements;

/**
 * Covers the trivial PHP-extension checks and the URL-parsing helpers in
 * SystemRequirements that the existing test does not exercise.
 */
class SystemRequirementsCoverageTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkCurl
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkMbString
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkBcMath
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkOpenSsl
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkSoap
     */
    public function testCheckExtensionLoadedReturns2When1WhenLoaded(): void
    {
        $sr = new SystemRequirements();

        $this->assertSame(extension_loaded('curl') ? 2 : 1, $sr->checkCurl());
        $this->assertSame(extension_loaded('mbstring') ? 2 : 1, $sr->checkMbString());
        $this->assertSame(extension_loaded('bcmath') ? 2 : 1, $sr->checkBcMath());
        $this->assertSame(extension_loaded('openssl') ? 2 : 1, $sr->checkOpenSsl());
        $this->assertSame(extension_loaded('soap') ? 2 : 1, $sr->checkSoap());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkPhpXml
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkJSon
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkIConv
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkTokenizer
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkMysqlConnect
     */
    public function testCheckExtensionLoadedReturns2Or0(): void
    {
        $sr = new SystemRequirements();

        $this->assertSame(extension_loaded('dom') ? 2 : 0, $sr->checkPhpXml());
        $this->assertSame(extension_loaded('json') ? 2 : 0, $sr->checkJSon());
        $this->assertSame(extension_loaded('iconv') ? 2 : 0, $sr->checkIConv());
        $this->assertSame(extension_loaded('tokenizer') ? 2 : 0, $sr->checkTokenizer());
        $this->assertSame(extension_loaded('pdo_mysql') ? 2 : 0, $sr->checkMysqlConnect());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::getPhpVersion
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkPhpVersion
     */
    public function testGetPhpVersionAndCheckPhpVersion(): void
    {
        $sr = new SystemRequirements();
        $this->assertSame(PHP_VERSION, $sr->getPhpVersion());

        // Whatever PHP we're running has to result in one of the three
        // recognised SystemRequirements::MODULE_STATUS_* constants.
        $status = $sr->checkPhpVersion();
        $this->assertContains($status, [
            SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
            SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS,
            SystemRequirements::MODULE_STATUS_OK,
        ]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkRequestUri
     */
    public function testCheckRequestUriReturns2WhenServerVarPresent(): void
    {
        $previous = $_SERVER['REQUEST_URI'] ?? null;
        $previousScript = $_SERVER['SCRIPT_URI'] ?? null;

        try {
            unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_URI']);
            $this->assertSame(0, (new SystemRequirements())->checkRequestUri());

            $_SERVER['REQUEST_URI'] = '/foo';
            $this->assertSame(2, (new SystemRequirements())->checkRequestUri());
        } finally {
            if ($previous === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previous;
            }
            if ($previousScript === null) {
                unset($_SERVER['SCRIPT_URI']);
            } else {
                $_SERVER['SCRIPT_URI'] = $previousScript;
            }
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::checkAllowUrlFopen
     */
    public function testCheckAllowUrlFopenReturnsAtLeast1(): void
    {
        $result = (new SystemRequirements())->checkAllowUrlFopen();
        $this->assertContains($result, [1, 2]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::_getShopHostInfoFromConfig
     */
    public function testGetShopHostInfoFromConfigParsesHttpsUrl(): void
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $config->method('getConfigParam')
            ->with('sShopURL')
            ->willReturn('https://shop.example.com:8443/sub/');
        Registry::set(Config::class, $config);

        $sr = new SystemRequirements();
        $reflection = new \ReflectionMethod(SystemRequirements::class, '_getShopHostInfoFromConfig');
        $reflection->setAccessible(true);
        $info = $reflection->invoke($sr);

        $this->assertSame('shop.example.com', $info['host']);
        $this->assertSame(8443, $info['port']);
        $this->assertSame('/sub/', $info['dir']);
        $this->assertTrue($info['ssl']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::_getShopHostInfoFromConfig
     */
    public function testGetShopHostInfoFromConfigDefaultsToPort80OnHttp(): void
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $config->method('getConfigParam')
            ->with('sShopURL')
            ->willReturn('http://shop.example.com/sub/');
        Registry::set(Config::class, $config);

        $sr = new SystemRequirements();
        $reflection = new \ReflectionMethod(SystemRequirements::class, '_getShopHostInfoFromConfig');
        $reflection->setAccessible(true);
        $info = $reflection->invoke($sr);

        $this->assertSame(80, $info['port']);
        $this->assertFalse($info['ssl']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::_getShopHostInfoFromConfig
     */
    public function testGetShopHostInfoFromConfigReturnsFalseForUnparsableUrl(): void
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $config->method('getConfigParam')->willReturn('');
        Registry::set(Config::class, $config);

        $sr = new SystemRequirements();
        $reflection = new \ReflectionMethod(SystemRequirements::class, '_getShopHostInfoFromConfig');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->invoke($sr));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\SystemRequirements::_getShopSSLHostInfoFromConfig
     */
    public function testGetShopSSLHostInfoFromConfigDefaultsToPort443ForHttps(): void
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getConfigParam'])
            ->getMock();
        $config->method('getConfigParam')
            ->with('sSSLShopURL')
            ->willReturn('https://secure.example.com/');
        Registry::set(Config::class, $config);

        $sr = new SystemRequirements();
        $reflection = new \ReflectionMethod(SystemRequirements::class, '_getShopSSLHostInfoFromConfig');
        $reflection->setAccessible(true);
        $info = $reflection->invoke($sr);

        $this->assertSame('secure.example.com', $info['host']);
        $this->assertSame(443, $info['port']);
        $this->assertTrue($info['ssl']);
    }
}
