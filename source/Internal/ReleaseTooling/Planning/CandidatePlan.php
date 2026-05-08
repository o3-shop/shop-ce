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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutResult;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\VersionResolution;

/**
 * Per-candidate plan: what version this repo will ship at and how the
 * decision was reached. Carries enough info for the dry-run printer
 * to render a single line per repo.
 */
final class CandidatePlan
{
    private string $package;
    private string $fromPin;
    private string $chosenVersion;
    private VersionResolution $resolution;
    private ?TagCutResult $tagCut;

    public function __construct(
        string $package,
        string $fromPin,
        string $chosenVersion,
        VersionResolution $resolution,
        ?TagCutResult $tagCut
    ) {
        $this->package = $package;
        $this->fromPin = $fromPin;
        $this->chosenVersion = $chosenVersion;
        $this->resolution = $resolution;
        $this->tagCut = $tagCut;
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

    public function resolution(): VersionResolution
    {
        return $this->resolution;
    }

    public function tagCut(): ?TagCutResult
    {
        return $this->tagCut;
    }

    public function isChanged(): bool
    {
        return $this->chosenVersion !== $this->fromPin;
    }

    public function caseLabel(): string
    {
        switch ($this->resolution->case()) {
            case VersionResolution::CASE_UNCHANGED:
                return 'unchanged';
            case VersionResolution::CASE_USABLE_TAG:
                return 'reuse-latest-tag';
            case VersionResolution::CASE_NEEDS_NEW_TAG:
                return 'cut-new-tag';
        }
        return 'unknown';
    }
}
