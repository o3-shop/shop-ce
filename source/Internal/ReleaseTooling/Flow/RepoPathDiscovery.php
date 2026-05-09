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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use RuntimeException;

/**
 * Resolves every release-eligible package to a local clone path so
 * the rest of the live-execution flow has paths to commit/tag/push
 * in. Two path sources, in priority order:
 *
 *   1. **Existing clone under the conventional sibling layout.**
 *      For each package we check `<base>/<github-repo-name>` (where
 *      `<github-repo-name>` honors `PackageRepoSlug` case-renames).
 *      If the dir exists and contains `.git/`, that's the path —
 *      reuses the maintainer's existing git config / signing keys /
 *      credentials.
 *   2. **Auto-clone into the same sibling dir.** If the dir is
 *      missing entirely, we `git clone --branch <release-branch>`
 *      into it. URL scheme mirrors the running shop-ce clone's
 *      origin (HTTPS or SSH) so auth keeps working.
 *
 * Special-cases:
 *   - `o3-shop/shop-ce` always maps to the running clone (the path
 *     `bin/release` was invoked from). We don't auto-clone over
 *     ourselves.
 *   - A dir that exists but lacks `.git/` aborts with a clear
 *     message — the orchestrator can't safely commit/tag in an
 *     ambiguous state.
 *
 * Per-package action ("found existing" / "auto-cloning") is streamed
 * to the supplied progress callable so the maintainer sees which
 * clones are being created on-the-fly.
 */
final class RepoPathDiscovery
{
    public const SHOP_CE_PACKAGE = 'o3-shop/shop-ce';

    private ProcessExecutor $exec;
    private RepoCloneUrlResolver $urlResolver;
    private DefaultBranchResolver $branchResolver;
    /** @var callable(string):void */
    private $progress;

    public function __construct(
        ProcessExecutor $exec,
        RepoCloneUrlResolver $urlResolver,
        DefaultBranchResolver $branchResolver,
        ?callable $progress = null
    ) {
        $this->exec = $exec;
        $this->urlResolver = $urlResolver;
        $this->branchResolver = $branchResolver;
        $this->progress = $progress ?? static function (string $_msg): void {
        };
    }

    /**
     * @param  string                $baseDir       absolute path; siblings of shop-ce live here
     * @param  string                $shopCePath    absolute path of the running shop-ce clone
     * @param  array<int,string>     $packages      composer package names; if empty, defaults to every
     *                                              key of `DefaultBranchResolver::PACKAGE_TO_BRANCH`
     * @return array<string,string>                 package => abs path
     */
    public function discoverAll(string $baseDir, string $shopCePath, array $packages = []): array
    {
        if ($packages === []) {
            $packages = array_keys(DefaultBranchResolver::PACKAGE_TO_BRANCH);
        }
        if (!is_dir($baseDir)) {
            throw new RuntimeException(sprintf(
                '--repo-base %s is not a directory; create it or pass an explicit path',
                $baseDir
            ));
        }

        $resolved = [];
        foreach ($packages as $package) {
            $resolved[$package] = $this->resolveOne($package, $baseDir, $shopCePath);
        }
        return $resolved;
    }

    private function resolveOne(string $package, string $baseDir, string $shopCePath): string
    {
        if ($package === self::SHOP_CE_PACKAGE) {
            ($this->progress)(sprintf('[%s] using running clone at %s', $package, $shopCePath));
            return $shopCePath;
        }

        $slug = PackageRepoSlug::resolve($package); // e.g. "o3-shop/o3-Theme"
        $repoName = (string) substr($slug, strpos($slug, '/') + 1); // strip "o3-shop/"
        $candidatePath = rtrim($baseDir, '/') . '/' . $repoName;

        if (is_dir($candidatePath . '/.git')) {
            ($this->progress)(sprintf('[%s] found existing clone at %s', $package, $candidatePath));
            return $candidatePath;
        }
        if (is_dir($candidatePath)) {
            throw new RuntimeException(sprintf(
                '%s exists but is not a git working tree; remove or rename it, '
                . 'or pass --repo-path %s=/your/path to point elsewhere',
                $candidatePath,
                $package
            ));
        }

        $branch = ($this->branchResolver)($package);
        $url = $this->urlResolver->urlFor($package);
        ($this->progress)(sprintf(
            '[%s] auto-cloning %s (branch %s) into %s',
            $package,
            $url,
            $branch,
            $candidatePath
        ));
        $this->cloneRepo($url, $branch, $candidatePath, $package);

        return $candidatePath;
    }

    private function cloneRepo(string $url, string $branch, string $targetPath, string $package): void
    {
        $args = ['git', 'clone', '--branch', $branch, $url, $targetPath];
        $outcome = $this->exec->execute($args, null, 600);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'auto-clone failed for %s (%s): %s',
                $package,
                $url,
                trim($outcome->stderr())
            ));
        }
    }
}
