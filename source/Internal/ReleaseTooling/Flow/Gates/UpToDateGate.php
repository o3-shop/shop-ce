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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;

/**
 * Gate §16.3: local working tree is in a known state relative to
 * `origin/<release-branch>`. Fetches origin first (read-only, safe),
 * then compares local HEAD to the just-updated `origin/<branch>` ref
 * via `git rev-list --left-right --count`.
 *
 * Four outcomes:
 *   - in-sync       → pass
 *   - ahead only    → warning ("local commits will ship — confirm intent")
 *   - behind only   → fast-forward to origin, then warning ("updated")
 *   - diverged      → abort ("rebase or merge required")
 *
 * The "ahead, warn-not-abort" path is deliberate: a maintainer who
 * has been working on the release branch locally and not yet pushed
 * is the common case — those commits are the very thing that's
 * supposed to ship. Surfacing them visibly lets the maintainer
 * confirm intent without blocking the run.
 *
 * The "behind, fast-forward-not-abort" path removes the manual
 * "pull and re-run" round-trip. Auto-cloned repos are current by
 * virtue of the fresh clone; folded-in clones (themes under
 * `source/Application/views/*`, the demodata satellite, …) and
 * previously-cloned siblings are reused as-is and routinely lag
 * behind origin. Rather than abort on every stale clone, this gate
 * fast-forwards a behind-only branch to `origin/<branch>` (ff-only,
 * never a merge commit) so the release proceeds from the same state
 * a fresh clone would have produced. The repo set comes from the
 * filesystem search in `RepoPathDiscovery`, so this applies uniformly
 * to every discovered clone without any hard-coded list. A dirty tree
 * is left untouched (WorkingTreeGate reports that separately) and a
 * non-fast-forwardable branch still aborts.
 */
class UpToDateGate implements PreFlightGate
{
    public const NAME = 'up-to-date-with-origin';

    private ProcessExecutor $exec;

    public function __construct(ProcessExecutor $exec)
    {
        $this->exec = $exec;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        $fetch = $this->exec->execute(
            ['git', 'fetch', 'origin', $expectedBranch],
            $repoPath,
            120
        );
        if (!$fetch->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'git fetch origin %s failed in %s: %s',
                    $expectedBranch,
                    $repoPath,
                    trim($fetch->stderr())
                ),
            ]);
        }

        // `git rev-list --left-right --count A...B` prints "<left>\t<right>"
        // where left = commits in A not in B (i.e. behind from B's POV),
        // right = commits in B not in A. We pass A=origin/<branch>, B=HEAD,
        // so left = behind, right = ahead.
        $compareRange = sprintf('origin/%s...HEAD', $expectedBranch);
        $count = $this->exec->execute(
            ['git', 'rev-list', '--left-right', '--count', $compareRange],
            $repoPath,
            30
        );
        if (!$count->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'git rev-list %s failed in %s: %s',
                    $compareRange,
                    $repoPath,
                    trim($count->stderr())
                ),
            ]);
        }

        $parts = preg_split('/\s+/', trim($count->stdout()));
        if ($parts === false || count($parts) < 2) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'unexpected git rev-list output in %s: %s',
                    $repoPath,
                    trim($count->stdout())
                ),
            ]);
        }
        $behind = (int) $parts[0];
        $ahead = (int) $parts[1];

        if ($behind === 0 && $ahead === 0) {
            return GateOutcome::passed(self::NAME);
        }
        if ($behind === 0 && $ahead > 0) {
            return GateOutcome::warning(self::NAME, [
                sprintf(
                    '%s: local %s is %d commit%s ahead of origin/%s; '
                    . 'those commits will ship in this release',
                    $packageName,
                    $expectedBranch,
                    $ahead,
                    $ahead === 1 ? '' : 's',
                    $expectedBranch
                ),
            ]);
        }
        if ($behind > 0 && $ahead === 0) {
            return $this->fastForwardToOrigin($repoPath, $expectedBranch, $packageName, $behind);
        }
        return GateOutcome::abort(self::NAME, [
            sprintf(
                '%s: local %s has diverged from origin/%s (%d ahead, %d behind); '
                . 'rebase or merge and re-run',
                $packageName,
                $expectedBranch,
                $expectedBranch,
                $ahead,
                $behind
            ),
        ]);
    }

    /**
     * Behind-only branch: fast-forward it to `origin/<branch>` so the
     * release runs from the same state a fresh clone would produce.
     * Only ever a fast-forward (no merge commit). A dirty tree is left
     * untouched — WorkingTreeGate reports that separately, and merging
     * over local changes is exactly what we must not do here.
     */
    private function fastForwardToOrigin(
        string $repoPath,
        string $expectedBranch,
        string $packageName,
        int $behind
    ): GateOutcome {
        $plural = $behind === 1 ? '' : 's';

        $status = $this->exec->execute(['git', 'status', '--porcelain'], $repoPath, 30);
        if (!$status->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'git status --porcelain failed in %s: %s',
                    $repoPath,
                    trim($status->stderr())
                ),
            ]);
        }
        if (trim($status->stdout()) !== '') {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    '%s: local %s is %d commit%s behind origin/%s but the working tree '
                    . 'is dirty; commit or stash, then re-run so it can be fast-forwarded',
                    $packageName,
                    $expectedBranch,
                    $behind,
                    $plural,
                    $expectedBranch
                ),
            ]);
        }

        $merge = $this->exec->execute(
            ['git', 'merge', '--ff-only', 'origin/' . $expectedBranch],
            $repoPath,
            120
        );
        if (!$merge->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    '%s: local %s is %d commit%s behind origin/%s and could not be '
                    . 'fast-forwarded automatically (%s); pull manually and re-run',
                    $packageName,
                    $expectedBranch,
                    $behind,
                    $plural,
                    $expectedBranch,
                    trim($merge->stderr())
                ),
            ]);
        }

        return GateOutcome::warning(self::NAME, [
            sprintf(
                '%s: local %s was %d commit%s behind origin/%s; fast-forwarded to match',
                $packageName,
                $expectedBranch,
                $behind,
                $plural,
                $expectedBranch
            ),
        ]);
    }
}
