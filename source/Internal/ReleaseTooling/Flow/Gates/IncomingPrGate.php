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
 * Gate 10.5: detect open PRs *targeting* the release branch
 * (incoming feature work).
 *
 * Per spec these are warnings, not aborts — the maintainer chose to
 * cut with that work pending; we surface the URLs but proceed.
 */
class IncomingPrGate implements PreFlightGate
{
    public const NAME = 'incoming-prs';

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
                '--base', $expectedBranch,
                '--json', 'number,title,url',
                '--limit', '100',
            ],
            $repoPath,
            60
        );
        if (!$outcome->isSuccess()) {
            return GateOutcome::warning(self::NAME, [
                sprintf(
                    'gh pr list failed for %s; could not check incoming PRs: %s',
                    $packageName,
                    trim($outcome->stderr())
                ),
            ]);
        }

        $stdout = trim($outcome->stdout());
        if ($stdout === '' || $stdout === '[]') {
            return GateOutcome::passed(self::NAME);
        }

        $decoded = json_decode($stdout, true);
        if (!is_array($decoded) || $decoded === []) {
            return GateOutcome::passed(self::NAME);
        }

        $messages = [sprintf(
            '%s has %d open PR(s) targeting %s; proceeding (warning only):',
            $packageName,
            count($decoded),
            $expectedBranch
        )];
        foreach ($decoded as $pr) {
            $messages[] = sprintf(
                '  #%s — %s (%s)',
                (string) ($pr['number'] ?? '?'),
                (string) ($pr['title'] ?? ''),
                (string) ($pr['url'] ?? '')
            );
        }
        return GateOutcome::warning(self::NAME, $messages);
    }
}
