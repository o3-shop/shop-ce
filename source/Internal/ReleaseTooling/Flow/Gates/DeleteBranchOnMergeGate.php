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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;

/**
 * Verifies a release repo keeps `delete_branch_on_merge = false`, so
 * merging the merge-back PR (whose head IS the release branch) cannot
 * delete the maintenance line.
 *
 * Remote-only: queries GitHub via `gh api` keyed on the repo slug, so
 * it needs no local checkout (and `$repoPath` is intentionally unused).
 * Fails CLOSED — any unverifiable result aborts.
 *
 * Scoped to repos that HAVE a separate maintenance line. When the
 * release branch is `main`, the merge-back PR would need `main` as
 * both base and head, so no such PR can exist and no release branch
 * is reachable by auto-delete — there `delete_branch_on_merge = true`
 * is ordinary feature-branch hygiene, not a hazard. Aborting on it
 * would turn a benign repo setting anywhere in the network into a
 * full release blocker, so those repos are skipped outright.
 *
 * Output assumption: `--jq .delete_branch_on_merge` renders the JSON
 * boolean as the lowercase strings `true`/`false`; anything else
 * (e.g. `null`) is treated as unverifiable and aborts.
 */
class DeleteBranchOnMergeGate implements PreFlightGate
{
    public const NAME = 'delete-branch-on-merge';

    private ProcessExecutor $exec;
    private string $ghBin;

    public function __construct(ProcessExecutor $exec, string $ghBin = 'gh')
    {
        $this->exec = $exec;
        $this->ghBin = $ghBin;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        if ($expectedBranch === MergeBackPrGate::MERGE_BACK_BASE) {
            return GateOutcome::passed(self::NAME); // no merge-back possible, nothing to guard
        }

        $slug = PackageRepoSlug::resolve($packageName);
        $outcome = $this->exec->execute(
            [$this->ghBin, 'api', 'repos/' . $slug, '--jq', '.delete_branch_on_merge'],
            null,
            60
        );
        if (!$outcome->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'gh api failed for %s; cannot verify delete_branch_on_merge. Aborting: %s',
                    $packageName,
                    trim($outcome->stderr())
                ),
            ]);
        }

        $value = trim($outcome->stdout());
        if ($value === 'false') {
            return GateOutcome::passed(self::NAME);
        }
        if ($value === 'true') {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    '%s has delete_branch_on_merge = true; merging the merge-back PR '
                    . "would delete the release branch '%s'.",
                    $packageName,
                    $expectedBranch
                ),
                sprintf('  Fix: gh api -X PATCH repos/%s -F delete_branch_on_merge=false', $slug),
            ]);
        }

        return GateOutcome::abort(self::NAME, [
            sprintf(
                "gh api returned unexpected value '%s' for %s delete_branch_on_merge; "
                . 'cannot verify. Aborting.',
                $value,
                $packageName
            ),
        ]);
    }
}
