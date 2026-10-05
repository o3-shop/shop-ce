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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;

/**
 * In-memory ProcessExecutor for tests. Looks up canned outcomes by
 * the joined command string so test fixtures stay readable; falls
 * back to a default "exit 0, empty stdout/stderr" when nothing
 * matches so individual tests only need to set the responses they
 * actually care about.
 *
 * Records every invocation in `$calls` so tests can assert call
 * shape (which command, which cwd).
 */
final class FakeProcessExecutor implements ProcessExecutor
{
    /** @var array<string,ProcessOutcome> */
    private array $responses;

    /** @var array<int,array{command:array<int,string>,cwd:string|null,timeout:int}> */
    public array $calls = [];

    private ProcessOutcome $defaultOutcome;

    /**
     * @param array<string,ProcessOutcome> $responses keyed by " ".join(command)
     */
    public function __construct(array $responses = [], ?ProcessOutcome $defaultOutcome = null)
    {
        $this->responses = $responses;
        $this->defaultOutcome = $defaultOutcome ?? new ProcessOutcome(0, '', '');
    }

    public function execute(array $command, ?string $cwd = null, int $timeout = 120): ProcessOutcome
    {
        $this->calls[] = ['command' => $command, 'cwd' => $cwd, 'timeout' => $timeout];
        $key = implode(' ', $command);
        return $this->responses[$key] ?? $this->defaultOutcome;
    }

    /** @return array<int,array<int,string>> the command argv arrays in call order */
    public function commands(): array
    {
        return array_map(static fn (array $call): array => $call['command'], $this->calls);
    }
}
