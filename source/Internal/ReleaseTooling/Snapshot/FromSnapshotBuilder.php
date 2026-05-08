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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawComposerJsonFetcher;

/**
 * Algorithm Step 1: read `o3-shop/composer.json` at `--from` and
 * build the `from_pin[]` map of tier-0 dependencies.
 *
 * Pre-fold-in `--from` (composer.json still requires
 * `o3-shop/shop-metapackage-ce`): recurse one level into the
 * metapackage's composer.json at the version pinned by `--from`
 * and merge its tier-0 pins. One-time transitional path covering the
 * v1.6.0 → v1.6.1-RC1 cut; bypassed for any post-fold-in `--from`.
 *
 * The output `FromSnapshot` is pure data — the CLI layer is responsible
 * for emitting the indirection log line based on the snapshot's
 * `usedPreFoldInIndirection()` flag.
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class FromSnapshotBuilder
{
    public const O3_SHOP_PROJECT = 'o3-shop/o3-shop';
    public const O3_SHOP_PREFIX = 'o3-shop/';
    public const METAPACKAGE_PACKAGE = 'o3-shop/shop-metapackage-ce';

    private RawComposerJsonFetcher $fetcher;

    public function __construct(RawComposerJsonFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function build(string $fromTag): FromSnapshot
    {
        $rootManifest = $this->fetcher->fetch(self::O3_SHOP_PROJECT, $fromTag);

        $rootRequire = $this->extractO3ShopPins($rootManifest['require'] ?? []);
        $rootRequireDev = $this->extractO3ShopPins($rootManifest['require-dev'] ?? []);

        $usedIndirection = false;
        $metapackageVersion = null;

        $rootDeclares = array_merge($rootRequire, $rootRequireDev);

        if (isset($rootDeclares['shop-metapackage-ce'])) {
            $metapackageVersion = $rootDeclares['shop-metapackage-ce'];
            $metaManifest = $this->fetcher->fetch(self::METAPACKAGE_PACKAGE, $metapackageVersion);
            $metaRequire = $this->extractO3ShopPins($metaManifest['require'] ?? []);
            $metaRequireDev = $this->extractO3ShopPins($metaManifest['require-dev'] ?? []);

            // Order matters: metapackage's pins land first, then the
            // root's own pins overwrite (root is more direct). The
            // metapackage entry itself is dropped — it does not appear
            // in from_pin[].
            $merged = array_merge($metaRequire, $metaRequireDev, $rootRequire, $rootRequireDev);
            unset($merged['shop-metapackage-ce']);
            $usedIndirection = true;
        } else {
            $merged = $rootDeclares;
        }

        return new FromSnapshot($merged, $usedIndirection, $metapackageVersion);
    }

    /**
     * Filters a composer.json `require` map to o3-shop/* entries,
     * stripping the `o3-shop/` prefix from the keys.
     *
     * @param array<string,mixed> $packages
     * @return array<string,string>
     */
    private function extractO3ShopPins(array $packages): array
    {
        $pins = [];
        foreach ($packages as $name => $constraint) {
            if (!is_string($name) || !is_string($constraint)) {
                continue;
            }
            if (strncmp($name, self::O3_SHOP_PREFIX, strlen(self::O3_SHOP_PREFIX)) !== 0) {
                continue;
            }
            $slug = substr($name, strlen(self::O3_SHOP_PREFIX));
            if ($slug === '' || $slug === false) {
                continue;
            }
            $pins[$slug] = $constraint;
        }
        return $pins;
    }
}
