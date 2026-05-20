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

use Doctrine\DBAL\ConnectionException as DoctrineConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOException as DoctrinePDOException;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database;

/**
 * The convertException() decision tree maps native Doctrine exceptions to the
 * shop's StandardException subclasses. Each branch is exercised here.
 */
class DatabaseExceptionTest extends \OxidTestCase
{
    private function invokeConvertException(\Exception $exception): \Throwable
    {
        $database = new Database();
        $reflection = new \ReflectionMethod(Database::class, 'convertException');
        $reflection->setAccessible(true);
        return $reflection->invoke($database, $exception);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertException
     */
    public function testConvertExceptionMapsDoctrineConnectionExceptionToDatabaseConnectionException(): void
    {
        $original = new DoctrineConnectionException('unable to connect', 1045);
        $converted = $this->invokeConvertException($original);

        $this->assertInstanceOf(DatabaseConnectionException::class, $converted);
        $this->assertSame('unable to connect', $converted->getMessage());
        $this->assertSame($original, $converted->getPrevious());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertException
     */
    public function testConvertExceptionMapsExceptionWithMysqlConnectionRefusedCodeToDatabaseConnectionException(): void
    {
        // Doctrine doesn't recognise SQLSTATE[HY000] [2003] as a connection
        // error; the convertException special-cases it via getPrevious()->getCode() == 2003.
        $previous = new \RuntimeException('Cannot reach mysql', 2003);
        $original = new DBALException('SQLSTATE[HY000] [2003]', 0, $previous);
        $converted = $this->invokeConvertException($original);

        $this->assertInstanceOf(DatabaseConnectionException::class, $converted);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertException
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertErrorCode
     */
    public function testConvertExceptionRecoversSqlErrorCodeFromDbalExceptionWithPdoPrevious(): void
    {
        // PDOException carries the original (My)SQL error code/message in
        // errorInfo[1]/[2]; convertException recovers them so callers see the
        // shop's int error code rather than Doctrine's SQLSTATE string.
        $pdoException = $this->makePdoException(1062, 'Duplicate entry');
        $dbal = new DBALException('SQLSTATE[23000]', 0, $pdoException);

        $converted = $this->invokeConvertException($dbal);

        $this->assertInstanceOf(DatabaseErrorException::class, $converted);
        $this->assertSame(DatabaseInterface::DUPLICATE_KEY_ERROR_CODE, $converted->getCode());
        $this->assertSame('Duplicate entry', $converted->getMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertException
     */
    public function testConvertExceptionRecoversSqlInfoFromBarePdoException(): void
    {
        $pdoException = $this->makePdoException(1054, 'Unknown column');

        $converted = $this->invokeConvertException($pdoException);
        $this->assertInstanceOf(DatabaseErrorException::class, $converted);
        $this->assertSame(1054, $converted->getCode());
        $this->assertSame('Unknown column', $converted->getMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::convertException
     */
    public function testConvertExceptionFallsBackToZeroCodeWhenPdoErrorCodeIsNotInteger(): void
    {
        // errorInfo[1] = string SQLSTATE → not int → code coerced to 0.
        $pdoException = $this->makePdoException('HY000', 'general error');

        $converted = $this->invokeConvertException($pdoException);
        $this->assertInstanceOf(DatabaseErrorException::class, $converted);
        $this->assertSame(0, $converted->getCode());
    }

    /**
     * Build a DoctrinePDOException-like object with controllable errorInfo.
     * Doctrine\DBAL\Driver\PDOException keeps errorInfo on the wrapped PDO
     * exception, so we use an anonymous class that mimics the public API
     * convertException() reads.
     */
    private function makePdoException($sqlCode, string $sqlMessage): DoctrinePDOException
    {
        $stub = new class ($sqlCode, $sqlMessage) extends DoctrinePDOException {
            public function __construct($sqlCode, string $sqlMessage)
            {
                $base = new \PDOException($sqlMessage);
                $base->errorInfo = ['HY000', $sqlCode, $sqlMessage];
                parent::__construct($base);
            }
        };
        return $stub;
    }
}
