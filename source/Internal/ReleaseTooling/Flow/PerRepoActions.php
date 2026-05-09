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

use RuntimeException;

/**
 * State-changing actions per repo, sequenced per spec
 * 10.8 → 10.9 → 10.10 → 10.11 (when applicable).
 *
 *   commitChangesAndPush()  add + commit (+ optional .next-bump rm) + push
 *   createTag()             git tag + push tag
 *   createDraftRelease()    gh release create --draft
 *   openMergeBackPr()       gh pr create --base main --head <release-branch>
 *
 * No method does any decision-making; the orchestrator (Section 11)
 * decides whether to call openMergeBackPr() based on MergeBackPolicy.
 *
 * Each action runs at most one shell command and bubbles failures
 * as RuntimeException so the orchestrator can roll back / report.
 */
class PerRepoActions
{
    private ProcessExecutor $exec;
    private string $ghBin;

    public function __construct(ProcessExecutor $exec, string $ghBin = 'gh')
    {
        $this->exec = $exec;
        $this->ghBin = $ghBin;
    }

    /**
     * 10.8: stage the supplied paths, optionally remove `.next-bump`
     * (when consumed per Section 7), commit with the supplied message,
     * and push directly to the release branch (no PR for constraint
     * bumps).
     *
     * @param array<int,string> $stagePaths repo-relative paths to git-add
     */
    public function commitChangesAndPush(
        string $repoPath,
        string $branch,
        array $stagePaths,
        bool $deleteNextBump,
        string $commitMessage
    ): void {
        if ($deleteNextBump) {
            $this->run(['git', 'rm', '--ignore-unmatch', '.next-bump'], $repoPath);
        }
        if ($stagePaths !== []) {
            $args = array_merge(['git', 'add'], $stagePaths);
            $this->run($args, $repoPath);
        }
        $this->run(['git', 'commit', '-m', $commitMessage], $repoPath);
        $this->run(['git', 'push', 'origin', $branch], $repoPath);
    }

    /**
     * 10.9: cut a tag at the current HEAD and push it.
     */
    public function createTag(string $repoPath, string $tag, string $tagMessage = ''): void
    {
        $args = ['git', 'tag', '-a', $tag, '-m', $tagMessage !== '' ? $tagMessage : ('Release ' . $tag)];
        $this->run($args, $repoPath);
        $this->run(['git', 'push', 'origin', $tag], $repoPath);
    }

    /**
     * 10.10: create a draft GitHub release at the tag, letting GitHub
     * auto-generate the body. Returns the release URL printed by gh.
     */
    public function createDraftRelease(string $packageName, string $tag, ?string $bodyOverride = null): string
    {
        $args = [
            $this->ghBin, 'release', 'create', $tag,
            '--repo', $packageName,
            '--draft',
            '--title', $tag,
        ];
        if ($bodyOverride !== null) {
            $args[] = '--notes';
            $args[] = $bodyOverride;
        } else {
            $args[] = '--generate-notes';
        }
        $outcome = $this->exec->execute($args, null, 120);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'gh release create failed for %s tag %s: %s',
                $packageName,
                $tag,
                trim($outcome->stderr())
            ));
        }
        return trim($outcome->stdout());
    }

    /**
     * 10.11: auto-open the canonical merge-back PR. Caller (Section 11)
     * is responsible for checking `MergeBackPolicy::shouldOpenForShopTo`
     * before invoking — this method just performs the action.
     */
    public function openMergeBackPr(string $packageName, string $releaseBranch, string $shopVersion): string
    {
        $title = MergeBackPrTitlePattern::buildTitle($shopVersion);
        $body = sprintf(
            "Auto-opened by bin/release after cutting %s.\n\n"
            . "Merge the release-branch state back to main so subsequent\n"
            . 'releases see the same code path.',
            $shopVersion
        );
        $args = [
            $this->ghBin, 'pr', 'create',
            '--repo', $packageName,
            '--base', 'main',
            '--head', $releaseBranch,
            '--title', $title,
            '--body', $body,
        ];
        $outcome = $this->exec->execute($args, null, 120);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'gh pr create failed for %s on %s: %s',
                $packageName,
                $releaseBranch,
                trim($outcome->stderr())
            ));
        }
        return trim($outcome->stdout());
    }

    private function run(array $command, string $repoPath): void
    {
        $outcome = $this->exec->execute($command, $repoPath, 120);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                '%s failed in %s (exit %d): %s',
                implode(' ', $command),
                $repoPath,
                $outcome->exitCode(),
                trim($outcome->stderr())
            ));
        }
    }
}
