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
 * Per spec (10.11/10.12): only final shop releases trigger merge-back
 * PRs. Pre-release shop releases (suffix `-rc` / `-alpha` / `-beta` and
 * variants) do not.
 *
 * The check operates on `--to` (the shop version being cut, never on
 * the dependency tags), since the merge-back PR is about the shop's
 * release branch -> main reconciliation.
 */
final class MergeBackPolicy
{
    /**
     * Captures the suffix forms o3-shop has actually used or could
     * plausibly use. Strict enough to not match arbitrary noise; loose
     * enough that uppercase / lowercase / dotted variants all count.
     */
    public const PRE_RELEASE_PATTERN = '/-(rc|alpha|beta|dev|preview|pre|p)(?:[.-]?\d*)?$/i';

    public static function shouldOpenForShopTo(string $shopTo): bool
    {
        return preg_match(self::PRE_RELEASE_PATTERN, $shopTo) !== 1;
    }
}
