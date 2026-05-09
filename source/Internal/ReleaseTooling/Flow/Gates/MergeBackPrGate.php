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
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPrTitlePattern;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;

/**
 * Gate 10.6: detect open PRs from the release branch into `main`
 * matching the canonical merge-back title pattern.
 *
 * Per spec this is a HARD ABORT: the previous release's merge-back
 * must be merged before the next release runs, otherwise main drifts
 * arbitrarily far behind the release line.
 */
class MergeBackPrGate implements PreFlightGate
{
    public const NAME = 'merge-back-pending';

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
        $outcome = $this->exec->execute(
            [
                $this->ghBin, 'pr', 'list',
                '--repo', PackageRepoSlug::resolve($packageName),
                '--state', 'open',
                '--base', 'main',
                '--head', $expectedBranch,
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
