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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer;

/**
 * Normalizes a Composer constraint string to a canonical o3-shop tag
 * name (v-prefixed semver). Used wherever the algorithm needs a git
 * ref or tag name from a constraint string captured in from_pin or
 * a composer.json require map:
 *
 *   - FromSnapshotBuilder, when recursing into a child's composer.json
 *     at the version pinned by its parent
 *   - ReleaseNotesAggregator, when passing from_pin as `previous_tag_name`
 *     to GitHub's `releases/generate-notes` API
 *
 * Strips a leading `^` or `~` operator and prepends `v` when the
 * matched semver doesn't already have it. Returns null when the
 * constraint cannot be reduced to a single tag (wildcards, `dev-*`
 * branch refs, ranges, OR expressions).
 */
final class TagFromConstraint
{
    public static function resolve(string $constraint): ?string
    {
        $trimmed = ltrim(trim($constraint), '^~');
        if (preg_match('/^v?(\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?)$/', $trimmed, $m) === 1) {
            return 'v' . $m[1];
        }
        return null;
    }
}
