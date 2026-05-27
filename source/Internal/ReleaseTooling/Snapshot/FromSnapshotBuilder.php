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
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFetchException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\TagFromConstraint;

/**
 * Algorithm Step 1: read `o3-shop/composer.json` at `--from` and
 * build the `from_pin[]` map of dependencies.
 *
 * The walk is recursive across the o3-shop subset of the dep tree
 * so tier-0 leaves of shop-ce (shop-doctrine-migration-wrapper,
 * shop-db-views-generator, etc.) appear in `from_pin[]` even though
 * they're not direct deps of o3-shop. Each child is fetched at the
 * constraint's bare-version form (e.g. `^v1.3.0` → `v1.3.0`); when a
 * constraint can't be resolved to a fetchable ref (wildcards, dev-*
 * etc.) or when a child fetch 404s, the recursion silently skips that
 * subtree — the caller still gets every pin it COULD harvest.
 *
 * `o3-shop/shop-metapackage-ce` is an ordinary node in this walk: the
 * thin `o3-shop` project requires it, so it lands in `from_pin[]` as a
 * release candidate and is recursed into to harvest the packages it
 * pins (shop-ce, themes, bundled modules, framework deps). A `--from`
 * where o3-shop instead pins those packages directly (a fold-in-era
 * tag) is handled by the same recursion — the metapackage simply
 * won't appear.
 *
 * The output `FromSnapshot` is pure data.
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class FromSnapshotBuilder
{
    public const O3_SHOP_PROJECT = 'o3-shop/o3-shop';
    public const O3_SHOP_PREFIX = 'o3-shop/';

    private RawComposerJsonFetcher $fetcher;

    /** @var callable(string):void */
    private $progress;

    /**
     * @param callable(string):void|null $progress invoked once per fetched manifest
     */
    public function __construct(RawComposerJsonFetcher $fetcher, ?callable $progress = null)
    {
        $this->fetcher = $fetcher;
        $this->progress = $progress ?? static function (string $message): void {
        };
    }

    public function build(string $fromTag): FromSnapshot
    {
        ($this->progress)(sprintf('  fetching o3-shop/o3-shop@%s', $fromTag));
        // Root fetch — failure here propagates (the CLI cannot continue
        // without the snapshot's anchor).
        $rootManifest = $this->fetcher->fetch(self::O3_SHOP_PROJECT, $fromTag);

        $rootRequire = $this->extractO3ShopPins($rootManifest['require'] ?? []);
        $rootRequireDev = $this->extractO3ShopPins($rootManifest['require-dev'] ?? []);
        $fromPin = array_merge($rootRequire, $rootRequireDev);

        // Recursive harvest: for every o3-shop/* slug currently in
        // fromPin, fetch its composer.json at the constraint's resolved
        // ref and add its o3-shop/* deps. Keeps walking until the queue
        // is empty. `shop-metapackage-ce` is an ordinary node here: when
        // o3-shop requires it, it lands in from_pin as a release
        // candidate AND is recursed into so the packages it pins
        // (shop-ce, themes, bundled modules, …) are harvested too.
        //
        // First-write-wins ordering note: root `require` is merged before
        // `require-dev`, and the queue is FIFO, so the metapackage (in
        // root `require`) is processed before testing-library (in root
        // `require-dev`). Its EXACT `shop-ce` pin is therefore recorded
        // before testing-library's looser `shop-ce: ^1.2` is seen, and
        // the exact pin wins.
        $visited = [self::O3_SHOP_PROJECT => true];
        $queue = [];
        foreach ($fromPin as $slug => $constraint) {
            $ref = $this->constraintToRef($constraint);
            if ($ref !== null) {
                $queue[] = ['package' => self::O3_SHOP_PREFIX . $slug, 'ref' => $ref];
            }
        }

        while ($queue !== []) {
            $current = array_shift($queue);
            $package = $current['package'];
            $ref = $current['ref'];
            if (isset($visited[$package])) {
                continue;
            }
            $visited[$package] = true;

            ($this->progress)(sprintf('  fetching %s@%s', $package, $ref));
            try {
                $manifest = $this->fetcher->fetch($package, $ref);
            } catch (RawRepoFetchException $e) {
                // 404 / transport failure: skip this subtree silently.
                // We've still harvested whatever we could from earlier
                // levels; missing a leaf doesn't invalidate the snapshot.
                continue;
            }

            $require = $this->extractO3ShopPins($manifest['require'] ?? []);
            $requireDev = $this->extractO3ShopPins($manifest['require-dev'] ?? []);

            foreach (array_merge($require, $requireDev) as $childSlug => $childConstraint) {
                // First-write wins: parent pins (visited earlier in the
                // queue) take precedence over deeper-level pins.
                if (!isset($fromPin[$childSlug])) {
                    $fromPin[$childSlug] = $childConstraint;
                }
                if (!isset($visited[self::O3_SHOP_PREFIX . $childSlug])) {
                    $childRef = $this->constraintToRef($childConstraint);
                    if ($childRef !== null) {
                        $queue[] = ['package' => self::O3_SHOP_PREFIX . $childSlug, 'ref' => $childRef];
                    }
                }
            }
        }

        return new FromSnapshot($fromPin);
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

    /**
     * Delegates to the shared `TagFromConstraint` helper so the
     * constraint→tag mapping is consistent across the algorithm.
     */
    private function constraintToRef(string $constraint): ?string
    {
        return TagFromConstraint::resolve($constraint);
    }
}
