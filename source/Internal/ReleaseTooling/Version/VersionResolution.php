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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version;

/**
 * The outcome of Algorithm Step 3 for a single candidate package.
 *
 *   CASE_UNCHANGED      no commits/tags newer than from_pin → reuse from_pin
 *   CASE_USABLE_TAG     latest_tag > from_pin AND stability matches → use latest_tag
 *   CASE_NEEDS_NEW_TAG  commits beyond latest_tag, OR stability mismatch → Step 4 cuts
 *
 * For CASE_NEEDS_NEW_TAG the chosenVersion is `null` here; Step 4
 * (Section 7) computes the new tag and produces a follow-up
 * VersionResolution with the chosen value filled in.
 */
final class VersionResolution
{
    public const CASE_UNCHANGED = 1;
    public const CASE_USABLE_TAG = 2;
    public const CASE_NEEDS_NEW_TAG = 3;

    private string $package;
    private int $case;
    private ?string $chosenVersion;
    private ?string $latestTag;
    /** @var array<int,string> human-readable diagnostic notes */
    private array $notes;

    /**
     * @param array<int,string> $notes
     */
    public function __construct(
        string $package,
        int $case,
        ?string $chosenVersion,
        array $notes = [],
        ?string $latestTag = null
    ) {
        if (!in_array($case, [self::CASE_UNCHANGED, self::CASE_USABLE_TAG, self::CASE_NEEDS_NEW_TAG], true)) {
            throw new \InvalidArgumentException("Unknown VersionResolution case '{$case}'");
        }
        if ($case !== self::CASE_NEEDS_NEW_TAG && ($chosenVersion === null || $chosenVersion === '')) {
            throw new \InvalidArgumentException(
                "VersionResolution case {$case} requires a non-empty chosen version"
            );
        }
        if ($case === self::CASE_NEEDS_NEW_TAG && $chosenVersion !== null) {
            throw new \InvalidArgumentException(
                'CASE_NEEDS_NEW_TAG must leave chosenVersion null until Step 4 fills it in'
            );
        }
        $this->package = $package;
        $this->case = $case;
        $this->chosenVersion = $chosenVersion;
        $this->notes = $notes;
        $this->latestTag = $latestTag;
    }

    public function latestTag(): ?string
    {
        return $this->latestTag;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function case(): int
    {
        return $this->case;
    }

    public function chosenVersion(): ?string
    {
        return $this->chosenVersion;
    }

    /** @return array<int,string> */
    public function notes(): array
    {
        return $this->notes;
    }

    public function needsNewTag(): bool
    {
        return $this->case === self::CASE_NEEDS_NEW_TAG;
    }
}
