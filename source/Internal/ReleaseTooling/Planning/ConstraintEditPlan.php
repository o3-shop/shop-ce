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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdate;

/**
 * Plan to rewrite one composer.json constraint. Produced when the
 * existing constraint does not satisfy the chosen version (Section 8).
 */
final class ConstraintEditPlan
{
    private string $parentPackage;
    private string $key;
    private string $depPackage;
    private ConstraintUpdate $update;

    public function __construct(
        string $parentPackage,
        string $key,
        string $depPackage,
        ConstraintUpdate $update
    ) {
        $this->parentPackage = $parentPackage;
        $this->key = $key;
        $this->depPackage = $depPackage;
        $this->update = $update;
    }

    public function parentPackage(): string
    {
        return $this->parentPackage;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function depPackage(): string
    {
        return $this->depPackage;
    }

    public function update(): ConstraintUpdate
    {
        return $this->update;
    }
}
