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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Dao;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet;
use OxidEsales\Eshop\Core\Database\Adapter\ResultSetInterface;
use OxidEsales\Eshop\Core\DataObject\ApplicationServer;
use OxidEsales\EshopCommunity\Core\Dao\ApplicationServerDao;

class ApplicationServerDaoTest extends \OxidTestCase
{
    private function makeConfig(int $shopId = 1): Config
    {
        $config = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getBaseShopId', 'getDecodeValueQuery'])
            ->getMock();
        $config->method('getBaseShopId')->willReturn($shopId);
        $config->method('getDecodeValueQuery')->willReturn('oxvarvalue');
        return $config;
    }

    private function makeDb(): DatabaseInterface
    {
        return $this->getMockBuilder(DatabaseInterface::class)
            ->getMock();
    }

    public function testConfigConstantValue(): void
    {
        $this->assertSame('aServersData_', ApplicationServerDao::CONFIG_NAME_FOR_SERVER_INFO);
    }

    public function testDeleteIssuesScopedDeleteAndDropsCachedEntry(): void
    {
        $db = $this->makeDb();
        $db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM oxconfig'),
                $this->callback(
                    static fn ($params) =>
                    isset($params[':oxvarname'], $params[':oxshopid'])
                    && $params[':oxvarname'] === 'aServersData_srv-7'
                    && $params[':oxshopid'] === 1
                )
            );

        $config = $this->makeConfig(1);
        $dao = new ApplicationServerDao($db, $config);

        // pre-load cached entry, then delete should drop it from the cache
        $appServer = oxNew(ApplicationServer::class);
        $appServer->setId('srv-7');
        $ref = new \ReflectionProperty($dao, 'appServer');
        $ref->setAccessible(true);
        $ref->setValue($dao, ['srv-7' => $appServer]);

        $dao->delete('srv-7');

        $this->assertSame([], $ref->getValue($dao));
    }

    public function testStartCommitRollbackTransactionDelegateToDatabase(): void
    {
        $db = $this->makeDb();
        $db->expects($this->once())->method('startTransaction');
        $db->expects($this->once())->method('commitTransaction');
        $db->expects($this->once())->method('rollbackTransaction');

        $dao = new ApplicationServerDao($db, $this->makeConfig());

        $dao->startTransaction();
        $dao->commitTransaction();
        $dao->rollbackTransaction();
    }

    public function testFindAllReturnsEmptyArrayForEmptyResultSet(): void
    {
        $resultSet = $this->createMock(ResultSetInterface::class);
        $resultSet->method('count')->willReturn(0);

        $db = $this->makeDb();
        $db->method('select')->willReturn($resultSet);

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $this->assertSame([], $dao->findAll());
    }

    public function testFindAllRehydratesServersFromConfigRows(): void
    {
        // Two stored rows: one returned via getFields() (the first row),
        // and one fetched via fetchRow() (subsequent rows).
        $rowA = ['oxvarname' => 'aServersData_srvA', 'oxvarvalue' => serialize([
            'id' => 'srvA', 'timestamp' => 100, 'ip' => '10.0.0.1',
            'lastFrontendUsage' => 'fe-A', 'lastAdminUsage' => 'admin-A',
        ])];
        $rowB = ['oxvarname' => 'aServersData_srvB', 'oxvarvalue' => serialize([
            'id' => 'srvB', 'timestamp' => 200, 'ip' => '10.0.0.2',
            'lastFrontendUsage' => 'fe-B', 'lastAdminUsage' => 'admin-B',
        ])];

        $resultSet = $this->getMockBuilder(ResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count', 'getFields', 'fetchRow'])
            ->getMock();
        $resultSet->method('count')->willReturn(2);
        $resultSet->method('getFields')->willReturn($rowA);
        $resultSet->method('fetchRow')->willReturnOnConsecutiveCalls($rowB, false);

        $db = $this->makeDb();
        $db->method('select')->willReturn($resultSet);

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $list = $dao->findAll();

        $this->assertCount(2, $list);
        $this->assertArrayHasKey('srvA', $list);
        $this->assertArrayHasKey('srvB', $list);
        $this->assertSame('10.0.0.1', $list['srvA']->getIp());
        $this->assertSame('admin-B', $list['srvB']->getLastAdminUsage());
    }

    public function testFindAppServerReturnsNullForUnknownId(): void
    {
        $db = $this->makeDb();
        $db->method('getOne')->willReturn(false);

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $this->assertNull($dao->findAppServer('missing'));
    }

    public function testFindAppServerHydratesAndCachesByIdOnSecondCall(): void
    {
        $serialized = serialize([
            'id' => 'srv-X', 'timestamp' => 999, 'ip' => '127.0.0.1',
            'lastFrontendUsage' => 'fe', 'lastAdminUsage' => 'admin',
        ]);

        $db = $this->makeDb();
        $db->expects($this->once()) // only first call hits the DB
            ->method('getOne')
            ->willReturn($serialized);

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $first = $dao->findAppServer('srv-X');
        $this->assertNotNull($first);
        $this->assertSame('srv-X', $first->getId());
        $this->assertSame(999, $first->getTimestamp());

        // Second call must come from in-memory cache (asserted via expects(once)).
        $second = $dao->findAppServer('srv-X');
        $this->assertSame($first, $second);
    }

    public function testSaveCallsUpdateForExistingEntry(): void
    {
        // Existing entry → findAppServer returns non-null → update path.
        $db = $this->makeDb();
        $db->method('getOne')->willReturn(serialize([
            'id' => 'srv-X', 'timestamp' => 100, 'ip' => '1.2.3.4',
            'lastFrontendUsage' => 'fe', 'lastAdminUsage' => 'admin',
        ]));
        // First execute is the UPDATE. The save method calls update() which
        // calls execute() on the database mock.
        $db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('UPDATE oxconfig'));

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $appServer = oxNew(ApplicationServer::class);
        $appServer->setId('srv-X');
        $appServer->setTimestamp(200);

        $dao->save($appServer);
    }

    public function testSaveCallsInsertForNewEntry(): void
    {
        $db = $this->makeDb();
        // findAppServer returns null → insert path
        $db->method('getOne')->willReturn(false);
        $db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('insert into oxconfig'));

        $dao = new ApplicationServerDao($db, $this->makeConfig());
        $appServer = oxNew(ApplicationServer::class);
        $appServer->setId('srv-NEW');

        $dao->save($appServer);
    }

    public function testCreateServerHandlesPartialDataGracefully(): void
    {
        $dao = new ApplicationServerDao($this->makeDb(), $this->makeConfig());
        $method = new \ReflectionMethod($dao, 'createServer');
        $method->setAccessible(true);

        $server = $method->invoke($dao, [
            'id' => 'srv-Z',
            // omit timestamp / ip — getServerParameter must return null for missing keys.
        ]);
        $this->assertSame('srv-Z', $server->getId());
        $this->assertNull($server->getTimestamp());
        $this->assertNull($server->getIp());
    }

    public function testGetServerIdFromConfigStripsConfigPrefix(): void
    {
        $dao = new ApplicationServerDao($this->makeDb(), $this->makeConfig());
        $method = new \ReflectionMethod($dao, 'getServerIdFromConfig');
        $method->setAccessible(true);
        $this->assertSame('srv-A', $method->invoke($dao, 'aServersData_srv-A'));
        $this->assertSame('', $method->invoke($dao, 'aServersData_'));
    }

    public function testGetValueFromConfigUnserializesAndCastsToArray(): void
    {
        $dao = new ApplicationServerDao($this->makeDb(), $this->makeConfig());
        $method = new \ReflectionMethod($dao, 'getValueFromConfig');
        $method->setAccessible(true);
        $this->assertSame(
            ['key' => 'value'],
            $method->invoke($dao, serialize(['key' => 'value']))
        );
    }

    public function testConvertAppServerToConfigOptionRoundtripsThroughSerializeAndUnserialize(): void
    {
        $dao = new ApplicationServerDao($this->makeDb(), $this->makeConfig());
        $appServer = oxNew(ApplicationServer::class);
        $appServer->setId('srv-RT');
        $appServer->setTimestamp(42);
        $appServer->setIp('127.0.0.1');
        $appServer->setLastFrontendUsage('fe');
        $appServer->setLastAdminUsage('admin');

        $method = new \ReflectionMethod($dao, 'convertAppServerToConfigOption');
        $method->setAccessible(true);
        $serialized = $method->invoke($dao, $appServer);

        $unserialized = unserialize($serialized);
        $this->assertSame('srv-RT', $unserialized['id']);
        $this->assertSame(42, $unserialized['timestamp']);
        $this->assertSame('127.0.0.1', $unserialized['ip']);
        $this->assertSame('fe', $unserialized['lastFrontendUsage']);
        $this->assertSame('admin', $unserialized['lastAdminUsage']);
    }
}
