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
 * Gate 10.4: per-repo test suite passes.
 *
 * The test command is repo-specific (some use phpunit, some have a
 * Makefile target, shop-ce uses ./docker.sh test). The maintainer
 * supplies the command per-repo via a resolver callable so the gate
 * stays generic.
 */
class TestSuiteGate implements PreFlightGate
{
    public const NAME = 'tests-pass';

    private ProcessExecutor $exec;

    /** @var callable(string $packageName, string $repoPath): array<int,string>|null */
    private $commandResolver;

    private int $timeoutSeconds;

    /**
     * @param callable(string,string):array<int,string>|null $commandResolver
     *     Returns the argv-array test command for a given (package, repo path),
     *     or null to skip the gate for that repo.
     */
    public function __construct(ProcessExecutor $exec, callable $commandResolver, int $timeoutSeconds = 1800)
    {
        $this->exec = $exec;
        $this->commandResolver = $commandResolver;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        $command = ($this->commandResolver)($packageName, $repoPath);
        if ($command === null) {
            return GateOutcome::passed(self::NAME); // explicitly skipped
        }
        $outcome = $this->exec->execute($command, $repoPath, $this->timeoutSeconds);
        if ($outcome->isSuccess()) {
            return GateOutcome::passed(self::NAME);
        }
        $tail = $this->lastLines($outcome->stdout() . "\n" . $outcome->stderr(), 20);
        return GateOutcome::abort(self::NAME, [
            sprintf('test suite failed in %s (exit %d):', $repoPath, $outcome->exitCode()),
            $tail,
        ]);
    }

    private function lastLines(string $text, int $count): string
    {
        $lines = explode("\n", rtrim($text, "\n"));
        if (count($lines) <= $count) {
            return implode("\n", $lines);
        }
        return implode("\n", array_slice($lines, -$count));
    }
}
