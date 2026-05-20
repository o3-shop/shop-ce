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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ConstraintEditPlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlan;
use RuntimeException;
use Throwable;

/**
 * Executes a `ReleasePlan` against live origin: applies constraint
 * edits to local composer.json files, commits + pushes, cuts tags,
 * creates draft releases, and (for final shop releases) opens
 * merge-back PRs.
 *
 * Orchestration order, per spec §10.8 → §10.9 → §10.10 → §10.11:
 *
 *   1. Group constraint edits by parent package (the repo whose
 *      composer.json is being edited).
 *   2. Walk candidates in topological order (leaves first); for each
 *      that needs a new tag, run the per-repo flow.
 *   3. After all candidates, run the same flow for `o3-shop/o3-shop`
 *      (the orchestrator/project-root, never a candidate but always
 *      tagged with `--to`). Its draft release uses the aggregated
 *      cross-repo body from §9.
 *   4. After every tag is cut, open merge-back PRs (final releases
 *      only — `MergeBackPolicy::shouldOpenForShopTo()`).
 *
 * Per-repo failures bubble as `RuntimeException`. The orchestrator
 * does not roll back; the runbook in the wiki describes how to clean
 * up partial state by hand.
 */
class LiveExecutor
{
    public const O3_SHOP_PROJECT = 'o3-shop/o3-shop';

    private PerRepoActions $actions;
    private ComposerJsonConstraintWriter $writer;
    private ThemeFileVersionWriter $themeWriter;
    private DefaultBranchResolver $branchResolver;
    /** @var callable(string):void */
    private $progress;

    /** @var array<string,string> populated as draft releases are created */
    private array $releaseUrls = [];

    /** @var array<string,string> populated as merge-back PRs are opened */
    private array $mergeBackUrls = [];

    public function __construct(
        PerRepoActions $actions,
        ComposerJsonConstraintWriter $writer,
        DefaultBranchResolver $branchResolver,
        ?ThemeFileVersionWriter $themeWriter = null,
        ?callable $progress = null
    ) {
        $this->actions = $actions;
        $this->writer = $writer;
        $this->themeWriter = $themeWriter ?? new ThemeFileVersionWriter();
        $this->branchResolver = $branchResolver;
        $this->progress = $progress ?? static function (string $_msg): void {
        };
    }

    /**
     * @param array<string,string> $repoPaths package => local clone path; must include
     *                                        every package that has a constraint edit
     *                                        AND every candidate that needs a new tag
     *                                        AND the o3-shop project root
     */
    public function execute(ReleasePlan $plan, array $repoPaths): void
    {
        $editsByParent = $this->groupEditsByParent($plan->constraintEdits());

        $this->log(sprintf(
            'Live execution starting (--from %s --to %s).',
            $plan->fromTag(),
            $plan->toTag()
        ));

        // Walk candidates leaves-first; for each needing a new tag, run
        // commit → tag → draft.
        foreach ($plan->candidates() as $candidate) {
            $tagCut = $candidate->tagCut();
            if ($tagCut === null) {
                continue;
            }
            $this->processRepo(
                $candidate->package(),
                $candidate->chosenVersion(),
                $editsByParent[$candidate->package()] ?? [],
                $repoPaths,
                $plan->toTag(),
                null, // candidate repos use --generate-notes
                $tagCut->deleteNextBumpFile()
            );
        }

        // o3-shop is the orchestrator, never a candidate. Tag it with
        // --to and attach the aggregated cross-repo body. No .next-bump
        // file is ever consumed at the project root (the file convention
        // is per-released-package, not per-orchestrator).
        $this->processRepo(
            self::O3_SHOP_PROJECT,
            $plan->toTag(),
            $editsByParent[self::O3_SHOP_PROJECT] ?? [],
            $repoPaths,
            $plan->toTag(),
            $plan->aggregatedNotes(), // o3-shop carries the aggregated body
            false
        );

        // Merge-back PRs (final releases only). Run after all tags so
        // a partial-failure mid-walk doesn't leave stray PRs.
        if (MergeBackPolicy::shouldOpenForShopTo($plan->toTag())) {
            $this->openMergeBackPrs($plan, $repoPaths);
        } else {
            $this->log(sprintf(
                'Pre-release shop --to (%s); no merge-back PRs auto-opened.',
                $plan->toTag()
            ));
        }

        $this->log('Live execution complete.');
    }

    /** @return array<string,string> package => GitHub draft release URL */
    public function releaseUrls(): array
    {
        return $this->releaseUrls;
    }

    /** @return array<string,string> package => merge-back PR URL */
    public function mergeBackUrls(): array
    {
        return $this->mergeBackUrls;
    }

    /**
     * @param array<int,ConstraintEditPlan> $edits  edits whose parentPackage == $package
     * @param array<string,string>          $repoPaths
     */
    private function processRepo(
        string $package,
        string $chosenVersion,
        array $edits,
        array $repoPaths,
        string $shopTo,
        ?string $bodyOverride,
        bool $deleteNextBump
    ): void {
        if (!isset($repoPaths[$package])) {
            throw new RuntimeException(sprintf(
                'no local repo path supplied for %s — pass --repo-path %s=/path/to/clone',
                $package,
                $package
            ));
        }
        $repoPath = $repoPaths[$package];
        $branch = ($this->branchResolver)($package);

        $this->log(sprintf('[%s] applying %d constraint edit(s)', $package, count($edits)));
        $stagePaths = $this->writer->apply($repoPath . '/composer.json', $edits);

        $themePaths = $this->themeWriter->apply($repoPath, $chosenVersion);
        if ($themePaths !== []) {
            $this->log(sprintf('[%s] bumped theme.php version to %s', $package, $chosenVersion));
            $stagePaths = array_merge($stagePaths, $themePaths);
        }

        $commitMessage = $this->buildCommitMessage($package, $chosenVersion, $shopTo, count($edits));

        if ($stagePaths !== [] || $deleteNextBump) {
            $this->log(sprintf('[%s] commit + push to %s', $package, $branch));
            $this->actions->commitChangesAndPush(
                $repoPath,
                $branch,
                $stagePaths,
                $deleteNextBump,
                $commitMessage
            );
        }

        $this->log(sprintf('[%s] tag %s', $package, $chosenVersion));
        $this->actions->createTag($repoPath, $chosenVersion);

        $this->log(sprintf('[%s] create draft GitHub release for %s', $package, $chosenVersion));
        $url = $this->actions->createDraftRelease($package, $chosenVersion, $bodyOverride);
        $this->releaseUrls[$package] = $url;
        $this->log(sprintf('[%s] draft release: %s', $package, $url));
    }

    /**
     * @param array<string,string> $repoPaths
     */
    private function openMergeBackPrs(ReleasePlan $plan, array $repoPaths): void
    {
        $packages = [];
        foreach ($plan->candidates() as $candidate) {
            if ($candidate->tagCut() !== null) {
                $packages[] = $candidate->package();
            }
        }
        $packages[] = self::O3_SHOP_PROJECT;

        foreach ($packages as $package) {
            if (!isset($repoPaths[$package])) {
                continue;
            }
            $branch = ($this->branchResolver)($package);
            $this->log(sprintf('[%s] open merge-back PR (%s -> main)', $package, $branch));
            try {
                $url = $this->actions->openMergeBackPr($package, $branch, $plan->toTag());
                $this->mergeBackUrls[$package] = $url;
                $this->log(sprintf('[%s] merge-back PR: %s', $package, $url));
            } catch (Throwable $e) {
                $this->log(sprintf('[%s] merge-back PR failed: %s', $package, $e->getMessage()));
                throw $e;
            }
        }
    }

    /**
     * @param  array<int,ConstraintEditPlan> $edits
     * @return array<string,array<int,ConstraintEditPlan>> parent package => edits in that file
     */
    private function groupEditsByParent(array $edits): array
    {
        $byParent = [];
        foreach ($edits as $edit) {
            $byParent[$edit->parentPackage()][] = $edit;
        }
        return $byParent;
    }

    private function buildCommitMessage(string $package, string $chosenVersion, string $shopTo, int $editCount): string
    {
        if ($editCount === 0) {
            return sprintf('release: %s for shop %s', $chosenVersion, $shopTo);
        }
        return sprintf(
            'release: %s for shop %s (%d constraint edit%s)',
            $chosenVersion,
            $shopTo,
            $editCount,
            $editCount === 1 ? '' : 's'
        );
    }

    private function log(string $message): void
    {
        ($this->progress)($message);
    }
}
