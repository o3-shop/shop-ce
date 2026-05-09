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
 * Audit:
 *   - Composer 2.7+ runs a security audit during install. The
 *     `$skipAudit` constructor flag (wired to the `--no-audit` CLI
 *     flag on `bin/release`) appends `--no-audit` to the install
 *     command, suppressing the post-install advisory report.
 *     Default: audit stays on.
 *
 * Composer binary:
 *   - The default `$composerBin` is just `'composer'` (PATH lookup),
 *     but `ReleaseCommand` resolves shop-ce's bundled
 *     `vendor/bin/composer` (composer 2.2.x via the transitive
 *     `o3-shop/shop-composer-plugin` dep) and passes that here.
 *     Using the bundled composer avoids host-PATH version skew —
 *     production o3-shop installs run the same 2.2.x.
 */
class ComposerInstallGate implements PreFlightGate
{
    public const NAME = 'composer-resolution';

    private ProcessExecutor $exec;
    private string $composerBin;
    private bool $skipAudit;

    public function __construct(
        ProcessExecutor $exec,
        string $composerBin = 'composer',
        bool $skipAudit = false
    ) {
        $this->exec = $exec;
        $this->composerBin = $composerBin;
        $this->skipAudit = $skipAudit;
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
        if ($this->skipAudit) {
            $args[] = '--no-audit';
        }
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
