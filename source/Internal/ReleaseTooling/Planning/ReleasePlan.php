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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightReport;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshot;

/**
 * Whole-run output of `ReleasePlanner::plan()`. Contains everything
 * the dry-run printer needs to render the per-repo plan, plus the
 * pre-flight report (when local repo paths were supplied).
 */
final class ReleasePlan
{
    private string $fromTag;
    private string $toTag;
    private FromSnapshot $fromSnapshot;
    /** @var array<int,CandidatePlan> in topological order; leaves first */
    private array $candidates;
    /** @var array<int,ConstraintEditPlan> */
    private array $constraintEdits;
    private string $aggregatedNotes;
    /** @var array<string,PreFlightReport> package => report (empty when no local repo paths supplied) */
    private array $preFlightReports;
    /** @var array<int,array{from:string,to:string}> back-edges from `DepTreeWalker` (informational) */
    private array $backEdges;

    /**
     * @param array<int,CandidatePlan>                  $candidates
     * @param array<int,ConstraintEditPlan>             $constraintEdits
     * @param array<string,PreFlightReport>             $preFlightReports
     * @param array<int,array{from:string,to:string}>   $backEdges
     */
    public function __construct(
        string $fromTag,
        string $toTag,
        FromSnapshot $fromSnapshot,
        array $candidates,
        array $constraintEdits,
        string $aggregatedNotes,
        array $preFlightReports,
        array $backEdges = []
    ) {
        $this->fromTag = $fromTag;
        $this->toTag = $toTag;
        $this->fromSnapshot = $fromSnapshot;
        $this->candidates = $candidates;
        $this->constraintEdits = $constraintEdits;
        $this->aggregatedNotes = $aggregatedNotes;
        $this->preFlightReports = $preFlightReports;
        $this->backEdges = $backEdges;
    }

    public function fromTag(): string
    {
        return $this->fromTag;
    }

    public function toTag(): string
    {
        return $this->toTag;
    }

    public function fromSnapshot(): FromSnapshot
    {
        return $this->fromSnapshot;
    }

    /** @return array<int,CandidatePlan> */
    public function candidates(): array
    {
        return $this->candidates;
    }

    /** @return array<int,ConstraintEditPlan> */
    public function constraintEdits(): array
    {
        return $this->constraintEdits;
    }

    public function aggregatedNotes(): string
    {
        return $this->aggregatedNotes;
    }

    /** @return array<string,PreFlightReport> */
    public function preFlightReports(): array
    {
        return $this->preFlightReports;
    }

    /** @return array<int,array{from:string,to:string}> */
    public function backEdges(): array
    {
        return $this->backEdges;
    }

    public function shouldAbort(): bool
    {
        foreach ($this->preFlightReports as $report) {
            if ($report->shouldAbort()) {
                return true;
            }
        }
        return false;
    }
}
