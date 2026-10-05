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
 * Gate 10.2: the local clone is on the expected release branch.
 * Aborts when HEAD points elsewhere — guards against accidentally
 * cutting from a feature branch.
 */
class BranchGate implements PreFlightGate
{
    public const NAME = 'on-release-branch';

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
        $outcome = $this->exec->execute(
            ['git', 'rev-parse', '--abbrev-ref', 'HEAD'],
            $repoPath,
            30
        );
        if (!$outcome->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf('git rev-parse failed in %s: %s', $repoPath, trim($outcome->stderr())),
            ]);
        }
        $current = trim($outcome->stdout());
        if ($current === $expectedBranch) {
            return GateOutcome::passed(self::NAME);
        }
        return GateOutcome::abort(self::NAME, [
            sprintf(
                "%s is on branch '%s'; expected '%s' (release branch for this repo)",
                $repoPath,
                $current,
                $expectedBranch
            ),
        ]);
    }
}
