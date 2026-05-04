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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database;
use Psr\Log\NullLogger;

/**
 * Covers the Doctrine Database adapter methods that don't require an actual
 * MySQL connection: connection parameter assembly, fetch-mode mapping, command
 * detection, and the simple guard methods.
 */
class DatabaseAdditionalTest extends \OxidTestCase
{
    private function newAdapter(): Database
    {
        return new Database();
    }

    private function defaultConnectionParameters(): array
    {
        return [
            'default' => [
                'databaseHost' => 'db.example',
                'databaseName' => 'shop',
                'databaseUser' => 'shopuser',
                'databasePassword' => 's3cret',
                'databasePort' => 3306,
                'databaseDriverOptions' => [],
                'connectionCharset' => 'utf8',
            ],
        ];
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::setConnectionParameters
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getPdoMysqlConnectionParameters
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::addDriverOptions
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::addConnectionCharset
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getConnectionParameters
     */
    public function testSetConnectionParametersBuildsPdoMysqlParameterArray(): void
    {
        $database = $this->newAdapter();
        $database->setConnectionParameters($this->defaultConnectionParameters());

        $params = $this->invokeProtected($database, 'getConnectionParameters');

        $this->assertSame('pdo_mysql', $params['driver']);
        $this->assertSame('db.example', $params['host']);
        $this->assertSame('shop', $params['dbname']);
        $this->assertSame('shopuser', $params['user']);
        $this->assertSame('s3cret', $params['password']);
        $this->assertSame(3306, $params['port']);
        $this->assertSame('utf8', $params['charset']);
        $this->assertArrayHasKey('driverOptions', $params);
        // addDriverOptions injects the init-command and stringify-fetches defaults.
        $this->assertSame("SET @@SESSION.sql_mode=''", $params['driverOptions'][\PDO::MYSQL_ATTR_INIT_COMMAND]);
        $this->assertTrue($params['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::setConnectionParameters
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getPdoMysqlConnectionParameters
     */
    public function testSetConnectionParametersHonoursUnixSocketAndDropsHostPort(): void
    {
        $database = $this->newAdapter();
        $params = $this->defaultConnectionParameters();
        $params['default']['databaseUnixSocket'] = '/tmp/mysql.sock';
        $database->setConnectionParameters($params);

        $built = $this->invokeProtected($database, 'getConnectionParameters');
        $this->assertSame('/tmp/mysql.sock', $built['unix_socket']);
        $this->assertArrayNotHasKey('host', $built);
        $this->assertArrayNotHasKey('port', $built);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::addConnectionCharset
     */
    public function testAddConnectionCharsetSkipsEmptyOrWhitespaceValues(): void
    {
        $database = $this->newAdapter();
        $params = ['driver' => 'pdo_mysql'];

        $reflection = new \ReflectionMethod(Database::class, 'addConnectionCharset');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($database, [&$params, '']);
        $this->assertArrayNotHasKey('charset', $params);

        $reflection->invokeArgs($database, [&$params, '   ']);
        $this->assertArrayNotHasKey('charset', $params);

        $reflection->invokeArgs($database, [&$params, 'UTF8MB4']);
        $this->assertSame('utf8mb4', $params['charset']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::addDriverOptions
     */
    public function testAddDriverOptionsKeepsConfiguredValuesAndAddsDefaults(): void
    {
        $database = $this->newAdapter();
        $params = [
            'driverOptions' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ],
        ];

        $reflection = new \ReflectionMethod(Database::class, 'addDriverOptions');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($database, [&$params]);

        // Configured init-command wins over the default.
        $this->assertSame('SET NAMES utf8mb4', $params['driverOptions'][\PDO::MYSQL_ATTR_INIT_COMMAND]);
        // Default for ATTR_STRINGIFY_FETCHES is added.
        $this->assertTrue($params['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getMySqlInitCommand
     */
    public function testGetMySqlInitCommandReturnsSqlModeReset(): void
    {
        $database = $this->newAdapter();
        $this->assertSame("SET @@SESSION.sql_mode=''", $this->invokeProtected($database, 'getMySqlInitCommand'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::checkIfSqlIsReadOnly
     */
    public function testCheckIfSqlIsReadOnlyAcceptsSelectAndShow(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'checkIfSqlIsReadOnly');
        $reflection->setAccessible(true);

        $reflection->invoke($database, 'SELECT * FROM oxarticles');
        $reflection->invoke($database, '   show tables');
        $reflection->invoke($database, "(\nSELECT 1\n)");
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::checkIfSqlIsReadOnly
     */
    public function testCheckIfSqlIsReadOnlyRejectsWriteStatements(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'checkIfSqlIsReadOnly');
        $reflection->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke($database, 'UPDATE oxarticles SET oxprice = 0');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::checkForMultipleQueries
     */
    public function testCheckForMultipleQueriesReturnsQueryUnchangedWhenSingle(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'checkForMultipleQueries');
        $reflection->setAccessible(true);

        $this->assertSame(
            'SELECT 1',
            $reflection->invoke($database, 'SELECT 1', [])
        );
        // Trailing semicolon → still single statement.
        $this->assertSame(
            'SELECT 1;',
            $reflection->invoke($database, 'SELECT 1;', [])
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::checkForMultipleQueries
     */
    public function testCheckForMultipleQueriesReturnsQueryWhenParametersArePresent(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'checkForMultipleQueries');
        $reflection->setAccessible(true);

        $this->assertSame(
            'SELECT 1; DROP TABLE oxarticles',
            $reflection->invoke($database, 'SELECT 1; DROP TABLE oxarticles', ['p'])
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::checkForMultipleQueries
     */
    public function testCheckForMultipleQueriesTrimsAfterFirstStatement(): void
    {
        // The method logs an ERROR-level entry when it detects two statements,
        // and the o3-shop testing library fails any test that emits one. Swap
        // the logger out for a NullLogger so we can exercise this branch.
        Registry::set('logger', new NullLogger());

        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'checkForMultipleQueries');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($database, 'SELECT 1; DROP TABLE evil;', []);
        $this->assertSame('SELECT 1;', $result);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::doesStatementProduceOutput
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getFirstCommandInStatement
     */
    public function testDoesStatementProduceOutputReturnsTrueForReadCommands(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'doesStatementProduceOutput');
        $reflection->setAccessible(true);

        foreach (['SELECT 1', 'show columns', 'EXPLAIN SELECT 1', 'DESCRIBE oxarticles'] as $query) {
            $this->assertTrue($reflection->invoke($database, $query), $query);
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::doesStatementProduceOutput
     */
    public function testDoesStatementProduceOutputReturnsFalseForMutations(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'doesStatementProduceOutput');
        $reflection->setAccessible(true);

        foreach (['UPDATE oxarticles SET oxprice = 0', 'INSERT INTO t VALUES (1)', 'DELETE FROM t'] as $query) {
            $this->assertFalse($reflection->invoke($database, $query), $query);
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getFirstCommandInStatement
     */
    public function testGetFirstCommandInStatementStripsBlockCommentAndUppercases(): void
    {
        $database = $this->newAdapter();
        $reflection = new \ReflectionMethod(Database::class, 'getFirstCommandInStatement');
        $reflection->setAccessible(true);

        $this->assertSame('SELECT', $reflection->invoke($database, '/* block comment */ select 1'));
        $this->assertSame('SHOW', $reflection->invoke($database, '  show tables'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::selectLimit
     */
    public function testSelectLimitRejectsNegativeOffset(): void
    {
        $database = $this->newAdapter();
        $this->expectException(\InvalidArgumentException::class);
        $database->selectLimit('SELECT 1', 10, -1);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::selectLimit
     */
    public function testSelectLimitTriggersDeprecationForNonNumericArguments(): void
    {
        $database = $this->newAdapter();

        $captured = null;
        set_error_handler(function ($type, $message) use (&$captured) {
            $captured = ['type' => $type, 'message' => $message];
        }, E_USER_DEPRECATED);

        try {
            // Will eventually throw because the connection isn't established —
            // but the deprecation must fire first.
            $database->selectLimit('SELECT 1', 'abc', 0);
        } catch (\Throwable $ignored) {
            // expected: TypeError / NullPointer once selectLimit reaches select().
        } finally {
            restore_error_handler();
        }

        $this->assertNotNull($captured, 'expected E_USER_DEPRECATED to be triggered');
        $this->assertSame(E_USER_DEPRECATED, $captured['type']);
        $this->assertStringContainsString('numeric', $captured['message']);
    }

    /**
     * Pulls a protected method's return value out — used for connection-param
     * helpers that don't need real DB access.
     *
     * @return mixed
     */
    private function invokeProtected(Database $target, string $method)
    {
        $reflection = new \ReflectionMethod(Database::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($target);
    }
}
