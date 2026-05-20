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
 * The canonical merge-back PR title is
 *   "Merge v<x>.<y>.<z> release into main"
 * (per the wiki convention; preserved by this change). Both gates
 * (10.6 detect existing) and actions (10.11 open new) use this
 * helper to keep the pattern in one place.
 */
final class MergeBackPrTitlePattern
{
    public const PATTERN = '/^Merge\s+(v\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?)\s+release\s+into\s+main$/';

    public static function matches(string $title): bool
    {
        return (bool) preg_match(self::PATTERN, $title);
    }

    /**
     * Returns the version captured from a matching title, or null if
     * the title does not match.
     */
    public static function extractVersion(string $title): ?string
    {
        if (preg_match(self::PATTERN, $title, $m) !== 1) {
            return null;
        }
        return $m[1];
    }

    /**
     * Builds the title for a given shop version (e.g. v1.6.1 →
     * "Merge v1.6.1 release into main").
     */
    public static function buildTitle(string $shopVersion): string
    {
        return sprintf('Merge %s release into main', $shopVersion);
    }
}
