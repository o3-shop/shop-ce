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
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck;

interface UpdateCheckServiceInterface
{
    /**
     * @param bool $forceRefresh Bypass session cache when true
     *
     * @return UpdateCheckResult
     */
    public function check(bool $forceRefresh = false): UpdateCheckResult;

    /**
     * Read-only access to the most recent cached result, if any. Does not
     * trigger network I/O. Returns null when nothing is cached yet or the
     * cache has expired. Used by render paths (e.g. header.tpl) that need
     * the last known state without paying the cost of a fresh check.
     *
     * @return UpdateCheckResult|null
     */
    public function getCachedResult(): ?UpdateCheckResult;
}
