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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdater;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightRunner;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\DepTreeWalker;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\CandidateState;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesAggregator;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshotBuilder;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\CandidateVersionResolver;

/**
 * Orchestrates Sections 4-10 into a single `plan()` call. Pure
 * data-flow: produces a `ReleasePlan` value object the dry-run
 * printer (or a future live-run executor) consumes.
 *
 * No state-changing actions are invoked here — the planner only
 * reads (composer.json fetches, tag/branch lookups, gh pr list).
 * Pre-flight gates run when `repoPaths` is supplied.
 */
class ReleasePlanner
{
    public const O3_SHOP_PROJECT = 'o3-shop/o3-shop';
    public const O3_SHOP_PREFIX = 'o3-shop/';

    private FromSnapshotBuilder $snapshotBuilder;
    private DepTreeWalker $walker;
    private CandidateVersionResolver $versionResolver;
    private TagCutter $tagCutter;
    private ConstraintUpdater $constraintUpdater;
    private ReleaseNotesAggregator $notesAggregator;
    private ?PreFlightRunner $preFlightRunner;

    /** @var callable(string):string */
    private $branchResolver;

    /**
     * @param callable(string):string $branchResolver maps a package to the release branch its composer.json lives on
     */
    public function __construct(
        FromSnapshotBuilder $snapshotBuilder,
        DepTreeWalker $walker,
        CandidateVersionResolver $versionResolver,
        TagCutter $tagCutter,
        ConstraintUpdater $constraintUpdater,
        ReleaseNotesAggregator $notesAggregator,
        callable $branchResolver,
        ?PreFlightRunner $preFlightRunner = null
    ) {
        $this->snapshotBuilder = $snapshotBuilder;
        $this->walker = $walker;
        $this->versionResolver = $versionResolver;
        $this->tagCutter = $tagCutter;
        $this->constraintUpdater = $constraintUpdater;
        $this->notesAggregator = $notesAggregator;
        $this->branchResolver = $branchResolver;
        $this->preFlightRunner = $preFlightRunner;
    }

    /**
     * @param array<string,string> $bumpFlags  short-slug => raw level
     * @param array<string,string> $repoPaths  package => local clone path (empty: skip pre-flight)
     */
    public function plan(string $fromTag, string $toTag, array $bumpFlags, array $repoPaths = []): ReleasePlan
    {
        // Step 1
        $fromSnapshot = $this->snapshotBuilder->build($fromTag);

        // Step 2
        $walkResult = $this->walker->walk(self::O3_SHOP_PROJECT);

        // Steps 3 + 4 — per candidate (skip the o3-shop project root; it is the orchestrator, not a candidate)
        $candidates = [];
        foreach ($walkResult->topologicalOrder() as $package) {
            if ($package === self::O3_SHOP_PROJECT) {
                continue;
            }
            $shortSlug = $this->slug($package);
            if (!isset($fromSnapshot->fromPin()[$shortSlug])) {
                // Package appeared in the walk but not in from_pin — new dep
                // since --from. Treat fromPin as empty so the planner doesn't
                // misclassify it as unchanged.
                $fromPin = '';
            } else {
                $fromPin = $fromSnapshot->fromPin()[$shortSlug];
            }

            $packageRef = ($this->branchResolver)($package);
            $resolution = $this->versionResolver->resolve($package, $fromPin, $toTag, $packageRef);

            if ($resolution->needsNewTag()) {
                $tagCut = $this->tagCutter->cut(
                    $package,
                    $resolution->latestTag(),
                    $toTag,
                    $bumpFlags,
                    $packageRef
                );
                $chosenVersion = $tagCut->newTag();
                $candidates[] = new CandidatePlan($package, $fromPin, $chosenVersion, $resolution, $tagCut);
            } else {
                $chosenVersion = (string) $resolution->chosenVersion();
                $candidates[] = new CandidatePlan($package, $fromPin, $chosenVersion, $resolution, null);
            }
        }

        // Step 5 — constraint updates per pin location.
        //
        // Skip unchanged candidates entirely: their `chosenVersion` is the
        // existing constraint string (e.g. `^v1.2.0`), not a version, so
        // running it through `ConstraintUpdater::update()` would falsely
        // detect a non-satisfaction (Semver::satisfies expects a version
        // on the left) and wrap the existing caret in another caret —
        // producing nonsense like `^^v1.2.0`. The candidate is unchanged
        // by definition, so no pin-location's constraint needs rewriting.
        $constraintEdits = [];
        foreach ($candidates as $candidate) {
            if (!$candidate->isChanged()) {
                continue;
            }
            foreach ($walkResult->pinLocations($candidate->package()) as $pin) {
                $update = $this->constraintUpdater->update(
                    $pin->constraint(),
                    $candidate->chosenVersion()
                );
                if (!$update->changed()) {
                    continue;
                }
                $constraintEdits[] = new ConstraintEditPlan(
                    $pin->parentPackage(),
                    $pin->key(),
                    $candidate->package(),
                    $update
                );
            }
        }

        // Step 6 — aggregated notes
        $candidateStates = [];
        foreach ($candidates as $candidate) {
            $candidateStates[] = new CandidateState(
                $candidate->package(),
                $candidate->fromPin(),
                $candidate->chosenVersion()
            );
        }
        $aggregatedNotes = $this->notesAggregator->aggregate($candidateStates);

        // Pre-flight (optional)
        $preFlightReports = [];
        if ($this->preFlightRunner !== null && $repoPaths !== []) {
            foreach ($candidates as $candidate) {
                $package = $candidate->package();
                if (!isset($repoPaths[$package])) {
                    continue;
                }
                $preFlightReports[$package] = $this->preFlightRunner->runFor(
                    $repoPaths[$package],
                    ($this->branchResolver)($package),
                    $package
                );
            }
            // Also run for o3-shop itself if path supplied
            if (isset($repoPaths[self::O3_SHOP_PROJECT])) {
                $preFlightReports[self::O3_SHOP_PROJECT] = $this->preFlightRunner->runFor(
                    $repoPaths[self::O3_SHOP_PROJECT],
                    ($this->branchResolver)(self::O3_SHOP_PROJECT),
                    self::O3_SHOP_PROJECT
                );
            }
        }

        return new ReleasePlan(
            $fromTag,
            $toTag,
            $fromSnapshot,
            $candidates,
            $constraintEdits,
            $aggregatedNotes,
            $preFlightReports,
            $walkResult->backEdges()
        );
    }

    private function slug(string $package): string
    {
        if (strncmp($package, self::O3_SHOP_PREFIX, strlen(self::O3_SHOP_PREFIX)) === 0) {
            return substr($package, strlen(self::O3_SHOP_PREFIX));
        }
        return $package;
    }
}
