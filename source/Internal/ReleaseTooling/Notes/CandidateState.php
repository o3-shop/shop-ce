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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes;

/**
 * Per-candidate snapshot consumed by the release-notes aggregator.
 * Captures what shipped at --from and what's about to ship now, so
 * the aggregator can decide between "## <repo>" (changed) and the
 * "## Unchanged in this release" section.
 */
final class CandidateState
{
    private string $package;
    private string $fromPin;
    private string $chosenVersion;

    public function __construct(string $package, string $fromPin, string $chosenVersion)
    {
        $this->package = $package;
        $this->fromPin = $fromPin;
        $this->chosenVersion = $chosenVersion;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function fromPin(): string
    {
        return $this->fromPin;
    }

    public function chosenVersion(): string
    {
        return $this->chosenVersion;
    }

    public function isChanged(): bool
    {
        return $this->chosenVersion !== $this->fromPin;
    }
}
