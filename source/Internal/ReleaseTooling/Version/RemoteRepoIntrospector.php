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

/**
 * Probes a remote o3-shop git repo for tag and branch state.
 *
 * Reference impl is `git ls-remote --tags --heads <repo-url>`. Tests
 * inject a fake to avoid live network. The Version-resolution logic
 * (`CandidateVersionResolver`) only needs tag-name -> SHA and a
 * single ref-name -> SHA lookup to decide between Cases 1, 2, 3.
 */
interface RemoteRepoIntrospector
{
    /**
     * @return array<string,string> tag-name => SHA
     */
    public function tags(string $package): array;

    /**
     * @param string $ref branch or tag name
     * @return string|null SHA at the given ref; null when the ref does not exist
     */
    public function refCommit(string $package, string $ref): ?string;
}
