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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use RuntimeException;

/**
 * State-changing actions per repo, sequenced per spec
 * 10.8 → 10.9 → 10.10 → 10.11 (when applicable).
 *
 *   commitChangesAndPush()  add + commit (+ optional .next-bump rm) + push
 *   createTag()             git tag + push tag
 *   createDraftRelease()    gh release create --draft
 *   openMergeBackPr()       push merge-back-<tag> at the tag, then
 *                           gh pr create --base main --head merge-back-<tag>
 *
 * No method does any decision-making; the orchestrator (Section 11)
 * decides whether to call openMergeBackPr() based on MergeBackPolicy.
 *
 * Each action runs at most one shell command and bubbles failures
 * as RuntimeException so the orchestrator can roll back / report.
 */
class PerRepoActions
{
    public const MERGE_BACK_BRANCH_PREFIX = 'merge-back-';

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
            '--repo', PackageRepoSlug::resolve($packageName),
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
     *
     * The PR head is a branch pinned to the package's release tag
     * (`merge-back-<tag>`), never the release branch itself: a release
     * branch keeps moving, so a merge-back left open would otherwise
     * pick up unreleased commits. An existing `merge-back-<tag>` branch
     * is reused only when it already points at the tag.
     */
    public function openMergeBackPr(string $packageName, string $repoPath, string $tag, string $shopVersion): string
    {
        $tagCommit = trim($this->run(['git', 'rev-parse', '--verify', $tag . '^{commit}'], $repoPath));
        $branch = self::MERGE_BACK_BRANCH_PREFIX . $tag;

        $ref = 'refs/heads/' . $branch;
        $remoteCommit = $this->remoteBranchCommit(
            $this->run(['git', 'ls-remote', '--heads', 'origin', $ref], $repoPath),
            $ref
        );
        if ($remoteCommit === null) {
            $this->run(['git', 'push', 'origin', $tagCommit . ':' . $ref], $repoPath);
        } elseif ($remoteCommit !== $tagCommit) {
            throw new RuntimeException(sprintf(
                'branch %s already exists on origin of %s but does not point at %s (%s); '
                . 'delete or fix it, then re-run',
                $branch,
                $packageName,
                $tag,
                $tagCommit
            ));
        }

        $title = MergeBackPrTitlePattern::buildTitle($shopVersion);
        $body = sprintf(
            "Auto-opened by bin/release after cutting %s.\n\n"
            . "Merges exactly the %s tag back to main, so main matches the\n"
            . "release and subsequent releases see the same code path.\n\n"
            . "Merge with \"Create a merge commit\" (never rebase), so the tag\n"
            . "becomes part of main's history. Then delete the %s branch.",
            $shopVersion,
            $tag,
            $branch
        );
        $args = [
            $this->ghBin, 'pr', 'create',
            '--repo', PackageRepoSlug::resolve($packageName),
            '--base', 'main',
            '--head', $branch,
            '--title', $title,
            '--body', $body,
        ];
        $outcome = $this->exec->execute($args, null, 120);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'gh pr create failed for %s on %s: %s. Branch %s is on origin at %s; '
                . 'open the PR by hand (base main, head %s, title "%s")',
                $packageName,
                $branch,
                trim($outcome->stderr()),
                $branch,
                $tag,
                $branch,
                $title
            ));
        }
        return trim($outcome->stdout());
    }

    /**
     * Picks the commit for exactly `$ref` from `git ls-remote` output.
     * ls-remote matches its pattern as a path suffix, so other refs
     * ending in the same name may be listed too.
     */
    private function remoteBranchCommit(string $lsRemoteOutput, string $ref): ?string
    {
        foreach (preg_split('/\R/', trim($lsRemoteOutput)) ?: [] as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if ($parts !== false && count($parts) === 2 && $parts[1] === $ref) {
                return $parts[0];
            }
        }
        return null;
    }

    /**
     * @return string the command's stdout
     */
    private function run(array $command, string $repoPath): string
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
        return $outcome->stdout();
    }
}
