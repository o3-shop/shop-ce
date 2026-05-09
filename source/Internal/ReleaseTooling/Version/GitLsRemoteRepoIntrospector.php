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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;
use RuntimeException;

/**
 * Reference impl: shells `git ls-remote --tags --heads
 * https://github.com/<package>` and parses the
 *   <sha>\trefs/(tags|heads)/<name>
 * lines.
 *
 * Tag lines that include `^{}` (peeled annotated tags) are unified
 * with their unpeeled counterparts so the reported SHA is the
 * commit the tag actually points at, not the tag object itself.
 */
class GitLsRemoteRepoIntrospector implements RemoteRepoIntrospector
{
    public const GITHUB_BASE = 'https://github.com';

    private ProcessExecutor $exec;

    /** @var array<string,array{tags:array<string,string>,heads:array<string,string>}> */
    private array $cache = [];

    public function __construct(ProcessExecutor $exec)
    {
        $this->exec = $exec;
    }

    /**
     * @return array<string,string>
     */
    public function tags(string $package): array
    {
        return $this->snapshot($package)['tags'];
    }

    public function refCommit(string $package, string $ref): ?string
    {
        $snapshot = $this->snapshot($package);
        return $snapshot['tags'][$ref] ?? $snapshot['heads'][$ref] ?? null;
    }

    /**
     * @return array{tags:array<string,string>,heads:array<string,string>}
     */
    private function snapshot(string $package): array
    {
        if (isset($this->cache[$package])) {
            return $this->cache[$package];
        }
        $url = self::GITHUB_BASE . '/' . PackageRepoSlug::resolve($package);
        $outcome = $this->exec->execute(['git', 'ls-remote', '--tags', '--heads', $url], null, 60);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'git ls-remote failed for %s: %s',
                $url,
                trim($outcome->stderr())
            ));
        }
        $tags = [];
        $heads = [];
        $peeled = []; // peeled annotated tag commits keyed by tag name
        foreach (explode("\n", $outcome->stdout()) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^([0-9a-f]+)\s+refs\/tags\/(.+?)(\^\{\})?$/', $line, $m) === 1) {
                $sha = $m[1];
                $name = $m[2];
                if (isset($m[3]) && $m[3] === '^{}') {
                    $peeled[$name] = $sha;
                } else {
                    $tags[$name] = $sha;
                }
                continue;
            }
            if (preg_match('/^([0-9a-f]+)\s+refs\/heads\/(.+)$/', $line, $m) === 1) {
                $heads[$m[2]] = $m[1];
            }
        }
        // Prefer peeled SHAs where available — they point at the commit, not the tag object.
        foreach ($peeled as $name => $sha) {
            $tags[$name] = $sha;
        }
        return $this->cache[$package] = ['tags' => $tags, 'heads' => $heads];
    }
}
