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

/**
 * One pre-flight gate. PreFlightRunner runs all configured gates per
 * repo before any state-changing action.
 */
interface PreFlightGate
{
    /**
     * Stable short name (used in diagnostics: `[<name>] <message>`).
     */
    public function name(): string;

    /**
     * Evaluate the gate against one repo's working tree.
     *
     * @param string $repoPath        absolute path to the repo
     * @param string $expectedBranch  the release branch this repo SHOULD be on
     * @param string $packageName     the o3-shop/<repo> identifier (for gh queries that target the GitHub repo)
     */
    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome;
}
