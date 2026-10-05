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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\ViewHelper;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator;

class StyleRegistratorTest extends \OxidTestCase
{
    /** @var array */
    private $globalParams = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->globalParams = [];
    }

    private function installConfigStub(string $resourceUrl = '', string $resourcePath = '/tmp/x.css'): Config
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getGlobalParameter', 'setGlobalParameter', 'getResourceUrl', 'getResourcePath', 'isAdmin', 'getConfigParam'])
            ->getMock();

        $store = & $this->globalParams;
        $config->method('getGlobalParameter')->willReturnCallback(function ($key) use (&$store) {
            return $store[$key] ?? null;
        });
        $config->method('setGlobalParameter')->willReturnCallback(function ($key, $value) use (&$store) {
            $store[$key] = $value;
        });
        $config->method('getResourceUrl')->willReturn($resourceUrl);
        $config->method('getResourcePath')->willReturn($resourcePath);
        $config->method('isAdmin')->willReturn(false);
        $config->method('getConfigParam')->willReturn(0);

        Registry::set(Config::class, $config);
        return $config;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::addFile
     */
    public function testAddFileStoresAbsoluteUrlUnderStylesParameter(): void
    {
        $this->installConfigStub();

        $registrator = new StyleRegistrator();
        $registrator->addFile('https://cdn.example/site.css', '', false);

        $this->assertSame(['https://cdn.example/site.css'], $this->globalParams[StyleRegistrator::STYLES_PARAMETER_NAME]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::addFile
     */
    public function testAddFileDeduplicatesEntries(): void
    {
        $this->installConfigStub();

        $registrator = new StyleRegistrator();
        $registrator->addFile('https://cdn.example/site.css', '', false);
        $registrator->addFile('https://cdn.example/site.css', '', false);

        $this->assertSame(
            ['https://cdn.example/site.css'],
            array_values($this->globalParams[StyleRegistrator::STYLES_PARAMETER_NAME])
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::addFile
     */
    public function testAddFileWithDynamicSuffixStoresUnderDynamicKey(): void
    {
        $this->installConfigStub();

        $registrator = new StyleRegistrator();
        $registrator->addFile('https://cdn.example/site.css', '', true);

        $this->assertArrayHasKey(StyleRegistrator::STYLES_PARAMETER_NAME . '_dynamic', $this->globalParams);
        $this->assertArrayNotHasKey(StyleRegistrator::STYLES_PARAMETER_NAME, $this->globalParams);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::addFile
     */
    public function testAddFileWithConditionStoresUnderConditionalStylesParameter(): void
    {
        $this->installConfigStub();

        $registrator = new StyleRegistrator();
        $registrator->addFile('https://cdn.example/site.css', 'lt IE 9', false);

        $this->assertSame(
            ['https://cdn.example/site.css' => 'lt IE 9'],
            $this->globalParams[StyleRegistrator::CONDITIONAL_STYLES_PARAMETER_NAME]
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::addFile
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::formLocalFileUrl
     */
    public function testAddFileSkipsStorageWhenLocalFileResolvesToEmptyUrl(): void
    {
        $this->installConfigStub('', '/tmp/missing.css');

        $registrator = new StyleRegistrator();
        $registrator->addFile('local/missing.css', '', false);

        $this->assertSame([], $this->globalParams);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::formLocalFileUrl
     */
    public function testFormLocalFileUrlAppendsFilemtimeWhenNoQueryProvided(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'styleReg_');
        file_put_contents($tmp, 'body{}');
        $mtime = filemtime($tmp);

        $this->installConfigStub('https://shop.example/css/site.css', $tmp);

        $registrator = new StyleRegistrator();
        $reflection = new \ReflectionMethod($registrator, 'formLocalFileUrl');
        $reflection->setAccessible(true);
        $url = $reflection->invoke($registrator, 'css/site.css');

        $this->assertSame("https://shop.example/css/site.css?$mtime", $url);
        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::formLocalFileUrl
     */
    public function testFormLocalFileUrlPreservesExplicitQueryString(): void
    {
        $this->installConfigStub('https://shop.example/css/site.css', '/tmp/whatever');

        $registrator = new StyleRegistrator();
        $reflection = new \ReflectionMethod($registrator, 'formLocalFileUrl');
        $reflection->setAccessible(true);
        $url = $reflection->invoke($registrator, 'css/site.css?v=42');

        $this->assertSame('https://shop.example/css/site.css?v=42', $url);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator::formLocalFileUrl
     */
    public function testFormLocalFileUrlReturnsEmptyStringWhenResourceUrlIsEmpty(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'styleReg_');
        $this->installConfigStub('', $tmp);

        $registrator = new StyleRegistrator();
        $reflection = new \ReflectionMethod($registrator, 'formLocalFileUrl');
        $reflection->setAccessible(true);

        $this->assertSame('', $reflection->invoke($registrator, 'css/site.css'));
        unlink($tmp);
    }
}
