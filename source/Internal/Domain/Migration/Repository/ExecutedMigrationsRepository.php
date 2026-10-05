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

namespace OxidEsales\EshopCommunity\Internal\Domain\Migration\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class ExecutedMigrationsRepository implements ExecutedMigrationsRepositoryInterface
{
    /**
     * Tracking table for CE migrations, as configured in
     * source/migration/migrations.yml (`table_name: oxmigrations_ce`).
     */
    public const MIGRATIONS_TABLE = 'oxmigrations_ce';

    private QueryBuilderFactoryInterface $queryBuilderFactory;

    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    public function tableExists(): bool
    {
        return $this->queryBuilderFactory
            ->create()
            ->getConnection()
            ->getSchemaManager()
            ->tablesExist([self::MIGRATIONS_TABLE]);
    }

    public function getExecutedVersions(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $queryBuilder = $this->queryBuilderFactory->create();
        $rows = $queryBuilder
            ->select('version')
            ->from(self::MIGRATIONS_TABLE)
            ->orderBy('version', 'ASC')
            ->execute()
            ->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['version'], $rows);
    }
}
