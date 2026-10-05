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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint;

/**
 * Output of `ConstraintUpdater::update()`. Captures whether the
 * existing composer.json constraint needs to be rewritten and what
 * the new value should be.
 *
 * SHAPE_UNCHANGED        existing constraint already satisfies chosen → no edit
 * SHAPE_EXACT_REPLACED   existing was an exact pin → replaced verbatim with chosen
 * SHAPE_CARET_WIDENED    existing was caret/tilde → re-anchored at chosen
 * SHAPE_FALLBACK_REPLACED everything else → replaced verbatim with chosen
 */
final class ConstraintUpdate
{
    public const SHAPE_UNCHANGED = 'unchanged';
    public const SHAPE_EXACT_REPLACED = 'exact-replaced';
    public const SHAPE_CARET_WIDENED = 'caret-widened';
    public const SHAPE_TILDE_WIDENED = 'tilde-widened';
    public const SHAPE_FALLBACK_REPLACED = 'fallback-replaced';

    private string $oldConstraint;
    private string $newConstraint;
    private string $shape;

    public function __construct(string $oldConstraint, string $newConstraint, string $shape)
    {
        $this->oldConstraint = $oldConstraint;
        $this->newConstraint = $newConstraint;
        $this->shape = $shape;
    }

    public function oldConstraint(): string
    {
        return $this->oldConstraint;
    }

    public function newConstraint(): string
    {
        return $this->newConstraint;
    }

    public function shape(): string
    {
        return $this->shape;
    }

    public function changed(): bool
    {
        return $this->shape !== self::SHAPE_UNCHANGED;
    }
}
