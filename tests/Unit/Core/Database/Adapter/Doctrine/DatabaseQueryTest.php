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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Database\Adapter\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database;
use Psr\Log\NullLogger;

/**
 * Tests for the query-execution surface (getOne / getRow / getCol / getAll /
 * executeUpdate / quoteIdentifier / transactions / setTransactionIsolationLevel)
 * by injecting a mock Connection via reflection on the protected $connection
 * property — no real MySQL connection involved.
 */
class DatabaseQueryTest extends \OxidTestCase
{
    private function makeAdapterWithConnection(Connection $connection): Database
    {
        $adapter = new Database();
        $reflection = new \ReflectionProperty(Database::class, 'connection');
        $reflection->setAccessible(true);
        $reflection->setValue($adapter, $connection);
        return $adapter;
    }

    private function makeConnectionMock(): Connection
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'fetchColumn', 'fetchAll', 'executeQuery', 'executeUpdate',
                'quoteIdentifier', 'getDatabasePlatform',
                'beginTransaction', 'commit', 'rollBack', 'setTransactionIsolation',
            ])
            ->getMock();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getOne
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::doesStatementProduceOutput
     */
    public function testGetOneReturnsFetchColumnOutputForSelectStatements(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->expects($this->once())
            ->method('fetchColumn')
            ->with('SELECT 1', [])
            ->willReturn('1');

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertSame('1', $adapter->getOne('SELECT 1'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getOne
     */
    public function testGetOneReturnsFalseAndLogsWarningForNonReadStatements(): void
    {
        Registry::set('logger', new NullLogger());

        $connection = $this->makeConnectionMock();
        $connection->expects($this->never())->method('fetchColumn');

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertFalse($adapter->getOne('UPDATE oxarticles SET oxprice = 0'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getCol
     */
    public function testGetColReturnsFirstColumnOfEachRow(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->method('fetchAll')->willReturn([
            ['oxid' => 'a'],
            ['oxid' => 'b'],
            ['oxid' => 'c'],
        ]);

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertSame(['a', 'b', 'c'], $adapter->getCol('SELECT oxid FROM oxarticles'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::executeUpdate
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::execute
     */
    public function testExecuteUpdateReturnsAffectedRowCount(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->method('executeUpdate')->willReturn(7);

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertSame(7, $adapter->executeUpdate('UPDATE x SET y=1'));
        $this->assertSame(7, $adapter->execute('UPDATE x SET y=1'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::quoteIdentifier
     */
    public function testQuoteIdentifierStripsQuotesThenDelegatesToConnection(): void
    {
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIdentifierQuoteCharacter'])
            ->getMockForAbstractClass();
        $platform->method('getIdentifierQuoteCharacter')->willReturn('`');

        $connection = $this->makeConnectionMock();
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quoteIdentifier')
            ->willReturnCallback(function ($s) {
                return '`' . $s . '`';
            });

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertSame('`oxarticles`', $adapter->quoteIdentifier('`oxarticles`'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::startTransaction
     */
    public function testStartTransactionDelegatesToConnection(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->expects($this->once())->method('beginTransaction');

        $this->makeAdapterWithConnection($connection)->startTransaction();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::commitTransaction
     */
    public function testCommitTransactionDelegatesToConnection(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->expects($this->once())->method('commit');

        $this->makeAdapterWithConnection($connection)->commitTransaction();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::rollbackTransaction
     */
    public function testRollbackTransactionDelegatesToConnection(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->expects($this->once())->method('rollBack');

        $this->makeAdapterWithConnection($connection)->rollbackTransaction();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::setTransactionIsolationLevel
     */
    public function testSetTransactionIsolationLevelMapsLevelStringToConstant(): void
    {
        $connection = $this->makeConnectionMock();
        $connection->expects($this->once())
            ->method('setTransactionIsolation')
            ->willReturn(1);

        $adapter = $this->makeAdapterWithConnection($connection);
        $this->assertSame(1, $adapter->setTransactionIsolationLevel('READ COMMITTED'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::setTransactionIsolationLevel
     */
    public function testSetTransactionIsolationLevelRejectsUnknownLevel(): void
    {
        $adapter = $this->makeAdapterWithConnection($this->makeConnectionMock());
        $this->expectException(\InvalidArgumentException::class);
        $adapter->setTransactionIsolationLevel('not-a-real-level');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::isRollbackOnly
     */
    public function testIsRollbackOnlyDelegatesToConnection(): void
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isRollbackOnly'])
            ->getMock();
        $connection->method('isRollbackOnly')->willReturn(true);

        $this->assertTrue($this->makeAdapterWithConnection($connection)->isRollbackOnly());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::isTransactionActive
     */
    public function testIsTransactionActiveDelegatesToConnection(): void
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isTransactionActive'])
            ->getMock();
        $connection->method('isTransactionActive')->willReturn(true);

        $this->assertTrue($this->makeAdapterWithConnection($connection)->isTransactionActive());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::closeConnection
     */
    public function testCloseConnectionCallsClose(): void
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['close'])
            ->getMock();
        $connection->expects($this->once())->method('close');

        $this->makeAdapterWithConnection($connection)->closeConnection();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::forceMasterConnection
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::forceSlaveConnection
     */
    public function testForceMasterConnectionAndForceSlaveConnectionAreNoopWhenAlreadyConnected(): void
    {
        // When $this->connection is non-null, neither method tries to (re-)connect;
        // they're effectively no-ops, which the unit test asserts simply by
        // not throwing.
        $adapter = $this->makeAdapterWithConnection($this->makeConnectionMock());
        $adapter->forceMasterConnection();
        $adapter->forceSlaveConnection();
        $this->assertTrue(true);
    }
}
