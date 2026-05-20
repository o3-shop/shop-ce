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
 * Fetches an arbitrary raw file from an o3-shop repo at a given ref.
 * Used for `.next-bump` (Step 4 / Section 7) and any other small
 * configuration files that travel alongside composer.json.
 *
 * Distinguishes "file not present" (returns null, normal) from
 * transport/protocol failure (throws RawRepoFetchException) so callers
 * can differentiate "no .next-bump committed" from "GitHub is
 * unreachable".
 */
interface RawRepoFileFetcher
{
    /**
     * @param string $packageName e.g. "o3-shop/shop-facts"
     * @param string $ref         a git tag, branch, or commit SHA
     * @param string $path        repo-relative path, e.g. ".next-bump"
     *
     * @return string|null raw file contents (without trimming), or
     *     null when the file does not exist (HTTP 404)
     *
     * @throws RawRepoFetchException on transport failure or non-404 HTTP error
     */
    public function fetchFile(string $packageName, string $ref, string $path): ?string;
}
