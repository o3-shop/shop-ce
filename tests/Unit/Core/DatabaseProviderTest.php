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
use OxidEsales\Eshop\Core\Exception\DatabaseNotConfiguredException;
use OxidEsales\EshopCommunity\Core\DatabaseProvider;

class DatabaseProviderTest extends \OxidTestCase
{
    public function testFetchModeConstants(): void
    {
        // Numeric and associative constants must mirror the underlying adapter contract.
        $this->assertSame(\OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface::FETCH_MODE_NUM, DatabaseProvider::FETCH_MODE_NUM);
        $this->assertSame(\OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface::FETCH_MODE_ASSOC, DatabaseProvider::FETCH_MODE_ASSOC);
    }

    public function testGetInstanceReturnsSameInstanceAcrossCalls(): void
    {
        $a = DatabaseProvider::getInstance();
        $b = DatabaseProvider::getInstance();
        $this->assertSame($a, $b);
    }

    public function testCloneIsForbidden(): void
    {
        $instance = DatabaseProvider::getInstance();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('singleton');
        $clone = clone $instance;
    }

    public function testFlushTableDescriptionCacheClearsStaticCache(): void
    {
        // Pre-populate the static cache via reflection.
        $ref = new \ReflectionProperty(DatabaseProvider::class, 'tblDescCache');
        $ref->setAccessible(true);
        $ref->setValue(['oxuser' => ['column' => 'value']]);

        DatabaseProvider::getInstance()->flushTableDescriptionCache();

        $this->assertSame([], $ref->getValue());
    }

    public function testGetTableDescriptionUsesCacheBeforeFetching(): void
    {
        $cached = ['oxshop' => ['col1' => 'val1']];
        $ref = new \ReflectionProperty(DatabaseProvider::class, 'tblDescCache');
        $ref->setAccessible(true);
        $ref->setValue($cached);

        // Get a real provider but spy on fetchTableDescription — must NOT be called
        // when the entry is already cached.
        $provider = $this->getMockBuilder(DatabaseProvider::class)
            ->onlyMethods(['fetchTableDescription'])
            ->getMock();
        $provider->expects($this->never())->method('fetchTableDescription');

        $this->assertSame(['col1' => 'val1'], $provider->getTableDescription('oxshop'));
    }

    public function testGetTableDescriptionPopulatesCacheOnFirstCall(): void
    {
        $ref = new \ReflectionProperty(DatabaseProvider::class, 'tblDescCache');
        $ref->setAccessible(true);
        $ref->setValue([]);

        $provider = $this->getMockBuilder(DatabaseProvider::class)
            ->onlyMethods(['fetchTableDescription'])
            ->getMock();
        $provider->expects($this->once())
            ->method('fetchTableDescription')
            ->with('oxorder')
            ->willReturn(['col' => 'value']);

        $this->assertSame(['col' => 'value'], $provider->getTableDescription('oxorder'));

        // Cache populated → second call must NOT trigger another fetch.
        $provider2 = $this->getMockBuilder(DatabaseProvider::class)
            ->onlyMethods(['fetchTableDescription'])
            ->getMock();
        $provider2->expects($this->never())->method('fetchTableDescription');
        $this->assertSame(['col' => 'value'], $provider2->getTableDescription('oxorder'));
    }

    public function testValidateConfigFileThrowsWhenDbNotConfigured(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->expects($this->any())
            ->method('getVar')
            ->with('dbHost')
            ->willReturn('<dbHost>'); // unconfigured placeholder

        $provider = new DatabaseProvider();
        $method = new \ReflectionMethod($provider, 'validateConfigFile');
        $method->setAccessible(true);

        $this->expectException(DatabaseNotConfiguredException::class);
        $method->invoke($provider, $configFile);
    }

    public function testValidateConfigFileAcceptsConfiguredHost(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->with('dbHost')->willReturn('localhost');

        $provider = new DatabaseProvider();
        $method = new \ReflectionMethod($provider, 'validateConfigFile');
        $method->setAccessible(true);
        // Must not throw.
        $method->invoke($provider, $configFile);
        $this->assertTrue(true);
    }

    public function testIsDatabaseConfiguredReturnsFalseForPlaceholderHost(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->with('dbHost')->willReturn('<dbHost>');

        $provider = new DatabaseProvider();
        $method = new \ReflectionMethod($provider, 'isDatabaseConfigured');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke($provider, $configFile));
    }

    public function testIsDatabaseConfiguredReturnsTrueForRealHost(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->with('dbHost')->willReturn('mysql.example.com');

        $provider = new DatabaseProvider();
        $method = new \ReflectionMethod($provider, 'isDatabaseConfigured');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($provider, $configFile));
    }

    public function testGetConnectionParametersAssemblesAllExpectedKeys(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->willReturnMap([
            ['dbType', 'pdo_mysql'],
            ['dbHost', 'localhost'],
            ['dbPort', 3307],
            ['dbName', 'shop'],
            ['dbUser', 'root'],
            ['dbPwd', 'secret'],
            ['dbDriverOptions', null],   // exercises the "not array" branch
            ['dbUnixSocket', '/tmp/mysql.sock'],
            ['dbCharset', 'utf8mb4'],
        ]);

        $provider = new DatabaseProvider();
        $provider->setConfigFile($configFile);
        $method = new \ReflectionMethod($provider, 'getConnectionParameters');
        $method->setAccessible(true);
        $params = $method->invoke($provider)['default'];

        $this->assertSame('pdo_mysql', $params['databaseDriver']);
        $this->assertSame('localhost', $params['databaseHost']);
        $this->assertSame(3307, $params['databasePort']);
        $this->assertSame('shop', $params['databaseName']);
        $this->assertSame('root', $params['databaseUser']);
        $this->assertSame('secret', $params['databasePassword']);
        $this->assertSame([], $params['databaseDriverOptions']);
        $this->assertSame('/tmp/mysql.sock', $params['databaseUnixSocket']);
        $this->assertSame('utf8mb4', $params['connectionCharset']);
    }

    public function testGetConnectionParametersDefaultsPortToMysqlStandard(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVar'])
            ->getMock();
        $configFile->method('getVar')->willReturnCallback(static function ($name) {
            $map = [
                'dbType' => 'pdo_mysql', 'dbHost' => 'localhost',
                'dbPort' => '', // empty → defaults to 3306
                'dbName' => 'shop', 'dbUser' => 'u', 'dbPwd' => 'p',
                'dbDriverOptions' => [], 'dbUnixSocket' => '',
                'dbCharset' => 'utf8',
            ];
            return $map[$name] ?? null;
        });

        $provider = new DatabaseProvider();
        $provider->setConfigFile($configFile);
        $method = new \ReflectionMethod($provider, 'getConnectionParameters');
        $method->setAccessible(true);
        $params = $method->invoke($provider)['default'];

        $this->assertSame(3306, $params['databasePort']);
    }

    public function testFetchConfigFileReadsRegistryConfigFile(): void
    {
        $configFile = $this->getMockBuilder(ConfigFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        \OxidEsales\Eshop\Core\Registry::set(ConfigFile::class, $configFile);

        $provider = new DatabaseProvider();
        $method = new \ReflectionMethod($provider, 'fetchConfigFile');
        $method->setAccessible(true);
        $this->assertSame($configFile, $method->invoke($provider));
    }
}
