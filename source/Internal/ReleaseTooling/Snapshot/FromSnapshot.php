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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot;

/**
 * Result of Algorithm Step 1 — the anchor map for a given `--from`
 * tag. The map is recursive across the o3-shop subset of the
 * dependency tree, so it includes `shop-metapackage-ce` (a first-class
 * release candidate) alongside the packages it pins.
 */
final class FromSnapshot
{
    /** @var array<string,string> repo-slug => version constraint as recorded in composer.json */
    private array $fromPin;

    /**
     * @param array<string,string> $fromPin
     */
    public function __construct(array $fromPin)
    {
        ksort($fromPin);
        $this->fromPin = $fromPin;
    }

    /** @return array<string,string> */
    public function fromPin(): array
    {
        return $this->fromPin;
    }
}
