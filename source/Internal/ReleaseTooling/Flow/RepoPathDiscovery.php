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
 * in. Three path sources, in priority order:
 *
 *   1. **Nested clone inside shop-ce.** `./docker.sh start` clones
 *      a few o3-shop repos into shop-ce's working tree (themes
 *      under `source/Application/views/<theme>`, demodata under
 *      `/shop-demodata-ce`, possibly more in future). These are
 *      real git working trees of the respective repos; bin/release
 *      should use them rather than auto-cloning a duplicate sibling.
 *      Discovery scans shop-ce for `.git/` directories, reads each
 *      one's origin URL, and reverse-maps to a composer package
 *      name (handling `PackageRepoSlug` case-renames). No
 *      configuration — the map is rebuilt from filesystem state on
 *      every run, so adding a new nested clone in
 *      `docker/entrypoint.sh` requires no bin/release change.
 *   2. **Existing clone under the conventional sibling layout.**
 *      For each package not found nested, check
 *      `<base>/<github-repo-name>` (`<github-repo-name>` honors
 *      `PackageRepoSlug` case-renames). If the dir exists and
 *      contains `.git/`, that's the path — reuses the maintainer's
 *      existing git config / signing keys / credentials.
 *   3. **Auto-clone into the same sibling dir.** If neither nested
 *      nor sibling exists, `git clone --branch <release-branch>`
 *      into the sibling location. URL scheme mirrors the running
 *      shop-ce clone's origin (HTTPS or SSH) so auth keeps working.
 *
 * Special-cases:
 *   - `o3-shop/shop-ce` always maps to the running clone (the path
 *     `bin/release` was invoked from). We don't auto-clone over
 *     ourselves and we skip shop-ce's own `.git/` during the
 *     nested scan.
 *   - A sibling dir that exists but lacks `.git/` aborts with a
 *     clear message — the orchestrator can't safely commit/tag in
 *     an ambiguous state. (For NESTED dirs without `.git/`, we
 *     fall through to sibling/auto-clone — those are typically
 *     composer-plugin install artifacts that happen to live at the
 *     same path.)
 *
 * Per-package action ("using nested clone" / "found existing
 * sibling" / "auto-cloning") is streamed to the supplied progress
 * callable so the maintainer sees the resolution on-the-fly.
 */
final class RepoPathDiscovery
{
    public const SHOP_CE_PACKAGE = 'o3-shop/shop-ce';

    /**
     * Directory names skipped during the nested-clone scan. These are
     * either build artifacts, vendor trees (which legitimately contain
     * .git references for some composer install configs), or shop-ce's
     * own metadata that we don't want to descend into.
     *
     * @var array<int,string>
     */
    private const NESTED_SCAN_SKIP_DIRS = [
        'vendor', 'node_modules', 'cache', 'tmp', 'log', 'logs', 'out',
        'coverage', 'tools', 'docs',
    ];

    /**
     * Maximum depth (relative to shop-ce root) the scanner descends to.
     * Nested clones live at depth 1 (`/shop-demodata-ce`) or 3
     * (`/source/Application/views/<theme>`); 4 covers both with margin.
     */
    private const NESTED_SCAN_MAX_DEPTH = 4;

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

        $nestedMap = $this->scanNestedClones($shopCePath);

        $resolved = [];
        foreach ($packages as $package) {
            $resolved[$package] = $this->resolveOne($package, $baseDir, $shopCePath, $nestedMap);
        }
        return $resolved;
    }

    /**
     * @param array<string,string> $nestedMap composer package => abs path of nested git working tree
     */
    private function resolveOne(string $package, string $baseDir, string $shopCePath, array $nestedMap): string
    {
        if ($package === self::SHOP_CE_PACKAGE) {
            ($this->progress)(sprintf('[%s] using running clone at %s', $package, $shopCePath));
            return $shopCePath;
        }

        if (isset($nestedMap[$package])) {
            ($this->progress)(sprintf(
                '[%s] using nested clone (set up by ./docker.sh) at %s',
                $package,
                $nestedMap[$package]
            ));
            return $nestedMap[$package];
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

    /**
     * Walk shop-ce's working tree to find nested git clones (themes
     * under `source/Application/views/`, the demodata satellite at
     * `/shop-demodata-ce`, etc. — set up by `docker/entrypoint.sh`
     * when the maintainer runs `./docker.sh start`). For each
     * working tree found, read its `.git/config` to extract the
     * `origin` remote URL, then reverse-map the GitHub slug to a
     * composer package name via `PackageRepoSlug::RENAMES`.
     *
     * Returns `package => abs path` for each nested clone whose
     * origin maps to an `o3-shop/*` package. Non-o3-shop origins
     * are ignored (the maintainer might have other repos checked
     * out for unrelated reasons).
     *
     * @return array<string,string>
     */
    private function scanNestedClones(string $shopCePath): array
    {
        $map = [];
        $stack = [[rtrim($shopCePath, '/'), 0]];
        while ($stack !== []) {
            [$dir, $depth] = array_pop($stack);
            if ($depth > self::NESTED_SCAN_MAX_DEPTH) {
                continue;
            }
            // A `.git/` directory at this level means $dir is a working
            // tree (skip shop-ce itself — that's the running clone).
            if ($dir !== rtrim($shopCePath, '/') && is_dir($dir . '/.git')) {
                $package = $this->packageFromGitOrigin($dir);
                if ($package !== null) {
                    $map[$package] = $dir;
                }
                // Don't recurse into nested clones — they have their
                // own subtrees that aren't ours to walk.
                continue;
            }
            $entries = @scandir($dir);
            if ($entries === false) {
                continue;
            }
            foreach ($entries as $entry) {
                if ($entry === '' || $entry[0] === '.') {
                    continue; // skip hidden incl. .git
                }
                if (in_array($entry, self::NESTED_SCAN_SKIP_DIRS, true)) {
                    continue;
                }
                $childPath = $dir . '/' . $entry;
                if (is_dir($childPath)) {
                    $stack[] = [$childPath, $depth + 1];
                }
            }
        }
        return $map;
    }

    /**
     * Read `<repoPath>/.git/config`, extract the `[remote "origin"]`
     * url, and reverse-map a GitHub slug like `o3-shop/o3-Theme` to
     * the composer package name `o3-shop/o3-theme`. Returns null
     * when the URL doesn't look like a github.com `o3-shop/*` repo.
     */
    private function packageFromGitOrigin(string $repoPath): ?string
    {
        $configFile = $repoPath . '/.git/config';
        if (!is_file($configFile)) {
            return null;
        }
        $contents = @file_get_contents($configFile);
        if ($contents === false) {
            return null;
        }
        // Match the url under the [remote "origin"] section. The
        // value can be HTTPS or SSH; both strip to the same slug.
        if (!preg_match(
            '/\[remote\s+"origin"\][^\[]*?\burl\s*=\s*(\S+)/s',
            $contents,
            $m
        )) {
            return null;
        }
        $url = $m[1];
        if (!preg_match(
            '#(?:https://github\.com/|git@github\.com:)([\w.-]+)/([\w.-]+?)(?:\.git)?/?$#',
            $url,
            $m
        )) {
            return null;
        }
        $owner = $m[1];
        $repo = $m[2];
        if ($owner !== 'o3-shop') {
            return null;
        }
        return self::reverseSlugToPackage($owner . '/' . $repo);
    }

    /**
     * Inverse of `PackageRepoSlug::resolve()`: given a GitHub
     * `owner/repo` slug, return the composer package name. Handles
     * the case-rename map (e.g. `o3-shop/o3-Theme` →
     * `o3-shop/o3-theme`); identity for slugs without a rename.
     */
    private static function reverseSlugToPackage(string $githubSlug): string
    {
        foreach (PackageRepoSlug::RENAMES as $composerName => $githubName) {
            if ($githubName === $githubSlug) {
                return $composerName;
            }
        }
        return $githubSlug;
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
