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
 * Reads the installed-package set from the shop root composer.lock. Used to
 * prove the OXID -> o3-shop package swap actually happened (no upstream
 * `oxid-esales/*` packages left behind after the migration).
 */
interface ComposerLockInspectorInterface
{
    /**
     * Whether a readable composer.lock is present at the shop root.
     */
    public function exists(): bool;

    /**
     * Installed package names whose name starts with the given prefix
     * (matches both `packages` and `packages-dev`). Returns an empty array
     * when no composer.lock is present.
     *
     * @return string[]
     */
    public function findInstalledPackagesByPrefix(string $prefix): array;
}
