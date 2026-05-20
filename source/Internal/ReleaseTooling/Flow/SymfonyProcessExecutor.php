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

use Symfony\Component\Process\Process;

/**
 * Reference implementation: delegates to Symfony Process. Captures
 * stdout, stderr and the exit code into a ProcessOutcome.
 */
class SymfonyProcessExecutor implements ProcessExecutor
{
    public function execute(array $command, ?string $cwd = null, int $timeout = 120): ProcessOutcome
    {
        $process = new Process($command, $cwd);
        $process->setTimeout($timeout);
        $process->run();
        $exit = $process->getExitCode();
        return new ProcessOutcome(
            $exit === null ? -1 : $exit,
            $process->getOutput(),
            $process->getErrorOutput()
        );
    }
}
