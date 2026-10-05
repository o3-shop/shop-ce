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
 * Fetches composer.json files for o3-shop packages at specific git refs.
 *
 * The reference implementation reads from
 * `raw.githubusercontent.com/<package>/<ref>/composer.json`. Tests
 * inject a fake to avoid live HTTP.
 */
interface RawComposerJsonFetcher
{
    /**
     * @param string $packageName e.g. "o3-shop/o3-shop"
     * @param string $ref         a git tag, branch, or commit SHA
     *
     * @return array<string,mixed> parsed composer.json
     *
     * @throws RawRepoFetchException when the URL cannot be
     *     fetched or the body is not valid JSON
     */
    public function fetch(string $packageName, string $ref): array;
}
