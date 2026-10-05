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

namespace OxidEsales\EshopCommunity\Internal\Domain\Migration\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Throwable;

class DatabaseViewsInspector implements DatabaseViewsInspectorInterface
{
    /**
     * A core view that exists in every o3-shop install and joins onto a
     * base table, so probing it exercises the generated view layer.
     */
    private const CORE_VIEW = 'oxv_oxarticles';

    private QueryBuilderFactoryInterface $queryBuilderFactory;

    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    public function getViewCount(): int
    {
        return count(
            $this->queryBuilderFactory
                ->create()
                ->getConnection()
                ->getSchemaManager()
                ->listViews()
        );
    }

    public function coreViewIsQueryable(): bool
    {
        $connection = $this->queryBuilderFactory->create()->getConnection();
        $view = $connection->quoteIdentifier(self::CORE_VIEW);

        try {
            $connection->executeQuery('SELECT 1 FROM ' . $view . ' LIMIT 1');
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }
}
