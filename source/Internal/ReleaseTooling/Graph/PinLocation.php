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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph;

/**
 * One spot where a package is pinned in another package's composer.json.
 * Algorithm Step 5 (Constraint update) iterates these to know where
 * to write when a chosen version differs from the recorded constraint.
 */
final class PinLocation
{
    public const KEY_REQUIRE = 'require';
    public const KEY_REQUIRE_DEV = 'require-dev';

    private string $parentPackage;
    private string $key;
    private string $constraint;

    public function __construct(string $parentPackage, string $key, string $constraint)
    {
        if ($key !== self::KEY_REQUIRE && $key !== self::KEY_REQUIRE_DEV) {
            throw new \InvalidArgumentException(
                "Unknown composer.json key '{$key}'; expected 'require' or 'require-dev'"
            );
        }
        $this->parentPackage = $parentPackage;
        $this->key = $key;
        $this->constraint = $constraint;
    }

    public function parentPackage(): string
    {
        return $this->parentPackage;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }
}
