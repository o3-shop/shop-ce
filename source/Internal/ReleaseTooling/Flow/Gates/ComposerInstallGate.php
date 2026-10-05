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
 * Gate 10.3: deps resolve.
 *
 * Runs `composer install --dry-run --no-scripts --no-interaction` so
 * the working tree is left untouched. Aborts on resolution failure.
 *
 * Skip rules:
 *   - **No `composer.lock`** → skipped (passed, no-op). Libraries in
 *     this org don't ship a lock file; their installable shape is
 *     verified through their consumers (shop-ce, o3-shop). Running
 *     `composer install` standalone on a library puts it in
 *     "resolve from scratch" mode which doesn't reflect production
 *     installs, so the gate stays out of the way.
 *
 * Composer binary + audit:
 *   - The default `$composerBin` is `'composer'` (PATH lookup), but
 *     `ReleaseCommand` resolves shop-ce's bundled
 *     `vendor/bin/composer` (composer 2.2.x via the transitive
 *     `o3-shop/shop-composer-plugin` dep) and passes that here.
 *     Using the bundled composer avoids host-PATH version skew —
 *     production o3-shop installs run the same 2.2.x.
 *   - Composer 2.2.x predates the audit feature (added in 2.6+),
 *     so no audit-skip flag is passed. If the bundled composer is
 *     ever upgraded past 2.6, the gate will need a version-aware
 *     audit-skip flag (`--no-audit` for 2.7-2.8, `--no-security-
 *     blocking` for 2.9+).
 */
class ComposerInstallGate implements PreFlightGate
{
    public const NAME = 'composer-resolution';

    private ProcessExecutor $exec;
    private string $composerBin;

    public function __construct(
        ProcessExecutor $exec,
        string $composerBin = 'composer'
    ) {
        $this->exec = $exec;
        $this->composerBin = $composerBin;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        if (!is_file($repoPath . '/composer.lock')) {
            // Library / lock-less repo — installable shape is verified
            // through consumers (shop-ce, o3-shop), not standalone.
            return GateOutcome::passed(self::NAME);
        }

        $args = [$this->composerBin, 'install', '--dry-run', '--no-scripts', '--no-interaction'];
        $outcome = $this->exec->execute($args, $repoPath, 300);
        if ($outcome->isSuccess()) {
            return GateOutcome::passed(self::NAME);
        }
        return GateOutcome::abort(self::NAME, [
            sprintf(
                'composer install --dry-run failed in %s (exit %d): %s',
                $repoPath,
                $outcome->exitCode(),
                trim($outcome->stderr() !== '' ? $outcome->stderr() : $outcome->stdout())
            ),
        ]);
    }
}
