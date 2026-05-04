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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Config;
use OxidEsales\EshopCommunity\Core\FileCache;
use OxidEsales\EshopCommunity\Core\ShopIdCalculator;

class ShopIdCalculatorTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset the static map between runs so cached state from a previous
        // test doesn't bleed into the next.
        $ref = new \ReflectionProperty(ShopIdCalculator::class, 'urlMap');
        $ref->setAccessible(true);
        $ref->setValue(null);
    }

    public function testBaseShopIdConstant(): void
    {
        $this->assertSame(1, ShopIdCalculator::BASE_SHOP_ID);
    }

    public function testGetShopIdReturnsBaseShopIdInCommunityEdition(): void
    {
        $cache = $this->createMock(FileCache::class);
        $calculator = new ShopIdCalculator($cache);
        $this->assertSame(ShopIdCalculator::BASE_SHOP_ID, $calculator->getShopId());
    }

    public function testGetShopUrlMapReturnsCachedMapWhenAvailable(): void
    {
        $cachedMap = [
            'https://shop.example/' => 1,
            'https://other.example/' => 2,
        ];

        $cache = $this->createMock(FileCache::class);
        $cache->expects($this->once())
            ->method('getFromCache')
            ->with('urlMap')
            ->willReturn($cachedMap);

        $calculator = new ShopIdCalculator($cache);
        $method = new \ReflectionMethod($calculator, '_getShopUrlMap');
        $method->setAccessible(true);
        $this->assertSame($cachedMap, $method->invoke($calculator));
    }

    public function testGetShopUrlMapShortCircuitsToStaticCacheOnSecondCall(): void
    {
        // Pre-populate the static cache via reflection — simulates a prior call.
        $ref = new \ReflectionProperty(ShopIdCalculator::class, 'urlMap');
        $ref->setAccessible(true);
        $ref->setValue(['https://example.com/' => 3]);

        $cache = $this->createMock(FileCache::class);
        // Static cache is checked first → file cache must NOT be consulted.
        $cache->expects($this->never())->method('getFromCache');

        $calculator = new ShopIdCalculator($cache);
        $method = new \ReflectionMethod($calculator, '_getShopUrlMap');
        $method->setAccessible(true);
        $this->assertSame(['https://example.com/' => 3], $method->invoke($calculator));
    }

    public function testGetConfKeyUsesExistingConfigFileInstanceWhenAvailable(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->expects($this->once())
            ->method('getVar')
            ->with('sConfigKey')
            ->willReturn('custom-key');
        Registry::set(ConfigFile::class, $configFile);

        $calculator = new ShopIdCalculator($this->createMock(FileCache::class));
        $method = new \ReflectionMethod($calculator, '_getConfKey');
        $method->setAccessible(true);
        $this->assertSame('custom-key', $method->invoke($calculator));
    }

    public function testGetConfKeyFallsBackToDefaultWhenConfigFileVarIsEmpty(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->expects($this->any())->method('getVar')->willReturn('');
        Registry::set(ConfigFile::class, $configFile);

        $calculator = new ShopIdCalculator($this->createMock(FileCache::class));
        $method = new \ReflectionMethod($calculator, '_getConfKey');
        $method->setAccessible(true);
        $this->assertSame(Config::DEFAULT_CONFIG_KEY, $method->invoke($calculator));
    }

    public function testGetVariablesCacheReturnsInjectedInstance(): void
    {
        $cache = $this->createMock(FileCache::class);
        $calculator = new ShopIdCalculator($cache);
        $method = new \ReflectionMethod($calculator, 'getVariablesCache');
        $method->setAccessible(true);
        $this->assertSame($cache, $method->invoke($calculator));
    }

    public function testGetShopUrlMapPersistsResultToBothCachesOnFirstFetch(): void
    {
        $captured = null;
        $cache = $this->createMock(FileCache::class);
        $cache->expects($this->once())
            ->method('getFromCache')
            ->with('urlMap')
            ->willReturn(null); // not in file cache
        $cache->expects($this->once())
            ->method('setToCache')
            ->willReturnCallback(function ($key, $value) use (&$captured) {
                $captured = ['key' => $key, 'value' => $value];
            });

        $calculator = new ShopIdCalculator($cache);
        $method = new \ReflectionMethod($calculator, '_getShopUrlMap');
        $method->setAccessible(true);
        $result = $method->invoke($calculator);

        // The computed map (possibly empty for a fresh test DB) lands in the
        // file cache under the same key.
        $this->assertSame('urlMap', $captured['key'] ?? null);
        $this->assertSame($result, $captured['value'] ?? null);
        $this->assertIsArray($result);
    }
}
