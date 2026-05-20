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
use RuntimeException;

/**
 * Builds clone URLs for o3-shop packages, mirroring the maintainer's
 * existing remote URL scheme. The maintainer's local shop-ce clone is
 * the reference: if its `origin` is HTTPS, every clone URL we produce
 * is HTTPS; if SSH, SSH. This way auto-clones inherit the same auth
 * setup (credential helper for HTTPS, SSH agent for SSH) without the
 * maintainer reconfiguring anything.
 *
 * Composer-package-name to GitHub-repo-name conversion goes through
 * `PackageRepoSlug::resolve()` so case-renamed repos
 * (`o3-shop/o3-theme` → `o3-shop/o3-Theme`) end up at the right URL.
 */
final class RepoCloneUrlResolver
{
    public const SCHEME_HTTPS = 'https';
    public const SCHEME_SSH = 'ssh';

    private string $scheme;

    public function __construct(string $scheme)
    {
        if ($scheme !== self::SCHEME_HTTPS && $scheme !== self::SCHEME_SSH) {
            throw new RuntimeException(sprintf('unknown clone-URL scheme: %s', $scheme));
        }
        $this->scheme = $scheme;
    }

    /**
     * Reads the origin URL of the supplied repo path and infers the
     * scheme. Throws when the URL doesn't look like a github.com remote
     * — that's a configuration the auto-clone flow can't handle and
     * the maintainer should resolve before re-running.
     */
    public static function fromRepoOrigin(ProcessExecutor $exec, string $repoPath): self
    {
        $outcome = $exec->execute(['git', 'config', '--get', 'remote.origin.url'], $repoPath, 30);
        if (!$outcome->isSuccess()) {
            throw new RuntimeException(sprintf(
                'could not read git remote.origin.url in %s: %s',
                $repoPath,
                trim($outcome->stderr())
            ));
        }
        $url = trim($outcome->stdout());
        if (strpos($url, 'https://github.com/') === 0) {
            return new self(self::SCHEME_HTTPS);
        }
        if (strpos($url, 'git@github.com:') === 0) {
            return new self(self::SCHEME_SSH);
        }
        throw new RuntimeException(sprintf(
            'unsupported origin URL %s (expected https://github.com/... or git@github.com:...)',
            $url
        ));
    }

    public function urlFor(string $packageName): string
    {
        $slug = PackageRepoSlug::resolve($packageName); // e.g. "o3-shop/o3-Theme"
        if ($this->scheme === self::SCHEME_HTTPS) {
            return sprintf('https://github.com/%s.git', $slug);
        }
        return sprintf('git@github.com:%s.git', $slug);
    }

    public function scheme(): string
    {
        return $this->scheme;
    }
}
