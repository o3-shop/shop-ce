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

/**
 * Compares the migrations shipped in source/migration/data against the ones
 * recorded as applied in `oxmigrations_ce`, so an operator jumping an OXID
 * 6.4.3 install up to current o3-shop can see whether the cumulative
 * `migrations:migrate` step has fully landed.
 */
interface MigrationStateServiceInterface
{
    /**
     * Whether the CE migration tracking table exists yet.
     */
    public function isTracked(): bool;

    /**
     * Bare version timestamps of every migration file shipped with this
     * o3-shop source tree, ascending.
     *
     * @return string[]
     */
    public function getAvailableVersions(): array;

    /**
     * Bare version timestamps recorded as applied in the database, ascending.
     *
     * @return string[]
     */
    public function getExecutedVersions(): array;

    /**
     * Shipped migrations that have not been applied yet, ascending. An empty
     * array means the schema is up to date with this source tree.
     *
     * @return string[]
     */
    public function getPendingVersions(): array;

    /**
     * Versions recorded as applied that no longer exist in the source tree,
     * ascending. Usually the sign of a downgrade or a stray edition mix-up;
     * informational, not necessarily an error.
     *
     * @return string[]
     */
    public function getUnknownVersions(): array;

    /**
     * The most recent applied version, or null when nothing has been applied.
     */
    public function getCurrentVersion(): ?string;

    /**
     * The most recent version shipped by the source tree, or null when the
     * source ships no migrations at all.
     */
    public function getLatestAvailableVersion(): ?string;
}
