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

namespace OxidEsales\EshopCommunity\Internal\Domain\Authentication\Repository;

use Doctrine\DBAL\Connection;

final class AdminUserRepository implements AdminUserRepositoryInterface
{
    private const SHOP_ID = 1;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findIdByUsername(string $username): ?string
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('OXID')
            ->from('oxuser')
            ->where('OXUSERNAME = :username')
            ->andWhere('OXSHOPID = :shopId')
            ->setParameter('username', $username)
            ->setParameter('shopId', self::SHOP_ID)
            ->setMaxResults(1);

        $row = $qb->execute()->fetch();
        if (!is_array($row) || !isset($row['OXID'])) {
            return null;
        }
        return (string) $row['OXID'];
    }

    public function updatePassword(string $oxid, string $hashedPassword): void
    {
        $this->connection->update(
            'oxuser',
            ['OXPASSWORD' => $hashedPassword, 'OXPASSSALT' => ''],
            ['OXID' => $oxid]
        );
    }

    public function insertUser(string $username, string $hashedPassword, string $oxrights): string
    {
        $oxid = $this->generateOxid();
        $this->connection->insert('oxuser', [
            'OXID'       => $oxid,
            'OXACTIVE'   => 1,
            'OXRIGHTS'   => $oxrights,
            'OXSHOPID'   => self::SHOP_ID,
            'OXUSERNAME' => $username,
            'OXPASSWORD' => $hashedPassword,
            'OXPASSSALT' => '',
        ]);
        return $oxid;
    }

    private function generateOxid(): string
    {
        return md5(uniqid('', true) . '|' . microtime());
    }
}
