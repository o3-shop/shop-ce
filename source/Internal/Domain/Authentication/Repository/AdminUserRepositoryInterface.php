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

/**
 * Narrow DB-facing surface needed by the admin-user CLI commands
 * (oe:user:change-password / oe:user:create). Exists so the
 * commands stay trivially unit-testable without mocking Doctrine's
 * QueryBuilder internals — concrete impl uses Connection, tests
 * mock this interface.
 *
 * All operations are scoped to shop id 1 (the CE single-shop model;
 * the enterprise multi-shop concept is gone from O3-Shop CE).
 */
interface AdminUserRepositoryInterface
{
    /**
     * @return string|null OXID of the matching user, or null if none.
     */
    public function findIdByUsername(string $username): ?string;

    /**
     * Sets `OXPASSWORD` to the already-hashed value and clears
     * `OXPASSSALT` (matches the modern-hash semantic — salt is baked
     * into the hash itself). Caller is responsible for hashing.
     */
    public function updatePassword(string $oxid, string $hashedPassword): void;

    /**
     * Inserts a fresh `oxuser` row with `OXACTIVE = 1` and the
     * supplied `OXRIGHTS` value. The two values OXID's auth queries
     * accept are 'malladmin' (admin-panel login) and 'user'
     * (storefront login) — empty rights yields a row that's not
     * loginable from either side. Caller supplies an already-hashed
     * password and gets the generated OXID back. Other profile
     * columns (name, address, phone, etc.) are left at table defaults.
     *
     * @return string The OXID assigned to the new row.
     */
    public function insertUser(string $username, string $hashedPassword, string $oxrights): string;
}
