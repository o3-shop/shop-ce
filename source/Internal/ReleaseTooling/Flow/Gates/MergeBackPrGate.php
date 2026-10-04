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
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPolicy;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPrTitlePattern;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;

/**
 * Gate 10.6: detect open PRs into `main` matching the canonical
 * merge-back title pattern, whichever branch they come from.
 *
 * Per spec this is a HARD ABORT: the previous release's merge-back
 * must be merged before the next release runs, otherwise main drifts
 * arbitrarily far behind the release line.
 */
class MergeBackPrGate implements PreFlightGate
{
    public const NAME = 'merge-back-pending';

    /** Server-side pre-filter for the canonical merge-back title. */
    public const TITLE_SEARCH = '"release into main" in:title';

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
        if ($expectedBranch === MergeBackPolicy::BASE_BRANCH) {
            return GateOutcome::passed(self::NAME);
        }

        // No --head filter: merge-backs come from a tag-pinned
        // `merge-back-<tag>` branch (older ones from the release branch),
        // so the canonical title is the only reliable signal. --search
        // narrows on the server so unrelated PRs into main cannot push a
        // pending merge-back past --limit; the regex below stays exact.
        // GitHub search is eventually consistent: a merge-back merged or
        // opened seconds ago may still show its old state. A pending
        // merge-back of ANY release line blocks, since main drifts either way.
        $outcome = $this->exec->execute(
            [
                $this->ghBin, 'pr', 'list',
                '--repo', PackageRepoSlug::resolve($packageName),
                '--state', 'open',
                '--base', MergeBackPolicy::BASE_BRANCH,
                '--search', self::TITLE_SEARCH,
                '--json', 'number,title,url',
                '--limit', '50',
            ],
            $repoPath,
            60
        );
        if (!$outcome->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'gh pr list failed for %s; cannot verify no unmerged merge-back PR exists. Aborting: %s',
                    $packageName,
                    trim($outcome->stderr())
                ),
            ]);
        }

        $decoded = json_decode($outcome->stdout(), true);
        if (!is_array($decoded) || $decoded === []) {
            return GateOutcome::passed(self::NAME);
        }

        $matching = [];
        foreach ($decoded as $pr) {
            $title = (string) ($pr['title'] ?? '');
            if (MergeBackPrTitlePattern::matches($title)) {
                $matching[] = $pr;
            }
        }
        if ($matching === []) {
            return GateOutcome::passed(self::NAME);
        }

        $messages = [sprintf(
            '%s has %d unmerged merge-back PR(s); merge before running this release:',
            $packageName,
            count($matching)
        )];
        foreach ($matching as $pr) {
            $messages[] = sprintf(
                '  #%s — %s (%s)',
                (string) ($pr['number'] ?? '?'),
                (string) ($pr['title'] ?? ''),
                (string) ($pr['url'] ?? '')
            );
        }
        return GateOutcome::abort(self::NAME, $messages);
    }
}
