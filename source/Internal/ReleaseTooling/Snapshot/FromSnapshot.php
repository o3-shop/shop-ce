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
 * Result of Algorithm Step 1 — the per-tier-0 anchor map for a given
 * `--from` tag, plus metadata about whether pre-fold-in metapackage
 * indirection had to be applied to assemble it.
 */
final class FromSnapshot
{
    /** @var array<string,string> repo-slug => version constraint as recorded in composer.json */
    private array $fromPin;

    private bool $usedPreFoldInIndirection;

    private ?string $preFoldInMetapackageVersion;

    /**
     * @param array<string,string> $fromPin
     */
    public function __construct(
        array $fromPin,
        bool $usedPreFoldInIndirection = false,
        ?string $preFoldInMetapackageVersion = null
    ) {
        ksort($fromPin);
        $this->fromPin = $fromPin;
        $this->usedPreFoldInIndirection = $usedPreFoldInIndirection;
        $this->preFoldInMetapackageVersion = $preFoldInMetapackageVersion;
    }

    /** @return array<string,string> */
    public function fromPin(): array
    {
        return $this->fromPin;
    }

    public function usedPreFoldInIndirection(): bool
    {
        return $this->usedPreFoldInIndirection;
    }

    /** @return string|null tag of `o3-shop/shop-metapackage-ce` consulted for indirection, or null */
    public function preFoldInMetapackageVersion(): ?string
    {
        return $this->preFoldInMetapackageVersion;
    }
}
