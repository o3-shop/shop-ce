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

use LogicException;

/**
 * Line-versioned packages: modules with one release line per shop line
 * (o3-shop/o3-shop#274). On branch `b-X.Y` such a package only carries
 * `vX.Y.n` tags; bin/release enforces that for the latest-tag lookup and
 * for new tags. (The `o3-shop/shop-ce` `~X.Y.0` requirement the convention
 * asks for in the module's `composer.json` is not checked here.) Other
 * repos keep repo-wide tags: the shop-line repos branch
 * `b-1.7` but tag their own versions (e.g. testing-library v1.2.x).
 *
 * The list is opt-in on purpose. Deriving it from "has vX.Y.* tags"
 * would treat the first release of a new line (no tags yet) as a normal
 * repo and bump from the previous line's highest tag.
 */
final class ReleaseLine
{
    /** @var list<string> composer package names */
    public const LINE_VERSIONED_PACKAGES = [
        'o3-shop/paypal-checkout',
    ];

    private const LINE_BRANCH_PATTERN = '/^b-(\d+)\.(\d+)$/';

    /**
     * Returns the tag prefix of the line (`v1.7.`) when $package is
     * line-versioned and $branch is a line branch (`b-1.7`); null when the
     * package is not line-versioned.
     *
     * The prefix follows the branch name alone, so paypal-checkout's
     * pre-convention `b-1.0` would get `v1.0.*`.
     *
     * @throws LogicException for a line-versioned package on any other
     *         branch, `main` included (the fallback for a missing map key;
     *         `main` only follows the newest line and is never released
     *         from) or OXID-style `b-7.0.x`: the line check would otherwise
     *         switch itself off silently.
     */
    public static function tagPrefix(string $package, string $branch): ?string
    {
        if (!in_array($package, self::LINE_VERSIONED_PACKAGES, true)) {
            return null;
        }
        if (preg_match(self::LINE_BRANCH_PATTERN, $branch, $m) !== 1) {
            throw new LogicException(sprintf(
                "%s is line-versioned but maps to branch '%s'; its release branches are named b-X.Y (see ReleaseLine).",
                $package,
                $branch
            ));
        }
        return sprintf('v%d.%d.', (int) $m[1], (int) $m[2]);
    }
}
