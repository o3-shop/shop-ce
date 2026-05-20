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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Database;

use Doctrine\DBAL\Connection;
use OxidEsales\EshopCommunity\Internal\Framework\Database\TransactionService;
use PHPUnit\Framework\TestCase;

class TransactionServiceTest extends TestCase
{
    public function testBeginDelegatesToConnection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        (new TransactionService($connection))->begin();
    }

    public function testCommitDelegatesToConnection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('commit');
        (new TransactionService($connection))->commit();
    }

    public function testRollbackDelegatesToConnection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('rollBack');
        (new TransactionService($connection))->rollback();
    }
}
