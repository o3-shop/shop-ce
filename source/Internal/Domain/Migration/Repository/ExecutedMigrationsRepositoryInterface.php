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

/**
 * Reads the Doctrine migration tracking table for the Community Edition
 * (`oxmigrations_ce`). doctrine/migrations 2.x stores one row per applied
 * migration, keyed by the bare version timestamp (e.g. `20230322213324`).
 */
interface ExecutedMigrationsRepositoryInterface
{
    /**
     * Whether the CE migration tracking table exists at all. A pre-migration
     * OXID install (or a database that never ran the migrator) has none, and
     * that is a legitimate state the status/verify commands must report on
     * rather than crash over.
     */
    public function tableExists(): bool;

    /**
     * The bare version timestamps of every applied migration, ascending.
     * Returns an empty array when the tracking table does not exist yet.
     *
     * @return string[]
     */
    public function getExecutedVersions(): array;
}
