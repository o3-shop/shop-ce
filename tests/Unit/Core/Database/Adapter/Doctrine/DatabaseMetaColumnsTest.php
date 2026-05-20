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
use OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database;

/**
 * Pure-logic helpers feeding metaColumns(): getMetaColumnValueByKey reads the
 * column row by string or numeric key depending on shape; getColumnMaxLengthAndScale
 * pulls (length, scale) from the MySQL type string; metaColumns itself maps
 * the result rows into property objects.
 */
class DatabaseMetaColumnsTest extends \OxidTestCase
{
    private function newAdapter(): Database
    {
        return new Database();
    }

    private function invokeProtected(Database $adapter, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod(Database::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($adapter, $args);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getMetaColumnValueByKey
     */
    public function testGetMetaColumnValueByKeyReadsAssociativeRow(): void
    {
        $row = [
            'Field' => 'oxid',
            'Type' => 'varchar(32)',
            'Null' => 'NO',
            'Key' => 'PRI',
            'Default' => null,
            'Extra' => '',
            'Comment' => 'primary key',
            'CharacterSet' => 'latin1',
            'Collation' => 'latin1_swedish_ci',
        ];

        $adapter = $this->newAdapter();
        foreach (['Field', 'Type', 'Null', 'Key', 'Default', 'Extra', 'Comment', 'CharacterSet', 'Collation'] as $k) {
            $this->assertSame($row[$k], $this->invokeProtected($adapter, 'getMetaColumnValueByKey', [$row, $k]));
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getMetaColumnValueByKey
     */
    public function testGetMetaColumnValueByKeyReadsNumericRow(): void
    {
        // When the row doesn't have the 'Field' string key (e.g. fetched in
        // numeric mode), the helper falls back to positional indexing.
        $row = ['oxid', 'varchar(32)', 'NO', 'PRI', null, '', 'primary key', 'latin1', 'latin1_swedish_ci'];

        $adapter = $this->newAdapter();
        $this->assertSame('oxid', $this->invokeProtected($adapter, 'getMetaColumnValueByKey', [$row, 'Field']));
        $this->assertSame('PRI', $this->invokeProtected($adapter, 'getMetaColumnValueByKey', [$row, 'Key']));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getColumnMaxLengthAndScale
     */
    public function testGetColumnMaxLengthAndScalePullsPrecisionAndScaleForDecimal(): void
    {
        $row = ['Field' => 'oxprice', 'Type' => 'DECIMAL(5,2)'];
        $adapter = $this->newAdapter();
        [$max, $scale] = $this->invokeProtected($adapter, 'getColumnMaxLengthAndScale', [$row, 'DECIMAL']);
        $this->assertSame(5, $max);
        $this->assertSame(2, $scale);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getColumnMaxLengthAndScale
     */
    public function testGetColumnMaxLengthAndScalePullsLengthForVarchar(): void
    {
        $row = ['Field' => 'oxname', 'Type' => 'varchar(255)'];
        $adapter = $this->newAdapter();
        [$max, $scale] = $this->invokeProtected($adapter, 'getColumnMaxLengthAndScale', [$row, 'VARCHAR']);
        $this->assertSame(255, $max);
        $this->assertSame(-1, $scale);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getColumnMaxLengthAndScale
     */
    public function testGetColumnMaxLengthAndScaleHandlesEnumTypeAsLongestMember(): void
    {
        $row = ['Field' => 'oxstate', 'Type' => "enum('A','BB','CCCC')"];
        $adapter = $this->newAdapter();
        [$max, $scale] = $this->invokeProtected($adapter, 'getColumnMaxLengthAndScale', [$row, 'ENUM']);
        // 'CCCC' has 4 chars + 2 quotes - 2 = 4
        $this->assertSame(4, $max);
        $this->assertSame(-1, $scale);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::getColumnMaxLengthAndScale
     */
    public function testGetColumnMaxLengthAndScaleReturnsMinusOneForNoMatch(): void
    {
        $row = ['Field' => 'oxdate', 'Type' => 'datetime'];
        $adapter = $this->newAdapter();
        [$max, $scale] = $this->invokeProtected($adapter, 'getColumnMaxLengthAndScale', [$row, 'DATETIME']);
        $this->assertSame(-1, $max);
        $this->assertSame(-1, $scale);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database::metaColumns
     */
    public function testMetaColumnsBuildsColumnObjectsFromAssociativeRows(): void
    {
        $statement = new class () {
            public function fetchAll(): array
            {
                return [
                    [
                        'Field' => 'oxid',
                        'Type' => 'varchar(32)',
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => '',
                        'Comment' => 'primary key',
                        'CharacterSet' => 'latin1',
                        'Collation' => 'latin1_swedish_ci',
                    ],
                    [
                        'Field' => 'oxprice',
                        'Type' => 'decimal(9,2) unsigned',
                        'Null' => 'YES',
                        'Key' => '',
                        'Default' => '0.00',
                        'Extra' => '',
                        'Comment' => '',
                        'CharacterSet' => null,
                        'Collation' => null,
                    ],
                    [
                        'Field' => 'oxorderdate',
                        'Type' => 'datetime',
                        'Null' => 'NO',
                        'Key' => '',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Comment' => '',
                        'CharacterSet' => null,
                        'Collation' => null,
                    ],
                ];
            }
        };

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDatabase', 'executeQuery'])
            ->getMock();
        $connection->method('getDatabase')->willReturn('o3shop_test');
        $connection->method('executeQuery')->willReturn($statement);

        $adapter = new Database();
        $reflection = new \ReflectionProperty(Database::class, 'connection');
        $reflection->setAccessible(true);
        $reflection->setValue($adapter, $connection);

        $columns = $adapter->metaColumns('oxorder');

        $this->assertArrayHasKey('oxid', $columns);
        $this->assertArrayHasKey('oxprice', $columns);
        $this->assertArrayHasKey('oxorderdate', $columns);

        // oxid: varchar(32) primary key, NOT NULL
        $oxid = $columns['oxid'];
        $this->assertSame('oxid', $oxid->name);
        $this->assertSame('varchar', $oxid->type);
        $this->assertTrue($oxid->not_null);
        $this->assertTrue($oxid->primary_key);
        $this->assertFalse($oxid->auto_increment);
        $this->assertFalse($oxid->binary);
        $this->assertFalse($oxid->unsigned);

        // oxprice: decimal(9,2) unsigned, default '0.00'
        $oxprice = $columns['oxprice'];
        $this->assertTrue($oxprice->unsigned);
        $this->assertTrue($oxprice->has_default);
        $this->assertSame('0.00', $oxprice->default_value);
        $this->assertSame('9', $oxprice->max_length);
        $this->assertSame('2', $oxprice->scale);

        // oxorderdate: datetime with auto_increment extra (synthetic, but
        // exercises that branch)
        $this->assertTrue($columns['oxorderdate']->auto_increment);
    }
}
