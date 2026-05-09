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

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawComposerJsonFetcher;

/**
 * Algorithm Step 2: walks the o3-shop-only dependency DAG starting
 * from `o3-shop/o3-shop`, recursing through `require` and
 * `require-dev`. For each package the walker fetches its
 * composer.json on the ref returned by the supplied resolver
 * (typically the package's release branch). Non-`o3-shop/*` deps
 * are ignored.
 *
 * The walker is single-shot per instance — call `walk()` to drive
 * one DFS pass and return a `WalkResult`. It does not retain state
 * between invocations.
 *
 * See: openspec/.../release-graph-derivation/spec.md
 */
class DepTreeWalker
{
    public const O3_SHOP_PROJECT = 'o3-shop/o3-shop';
    public const O3_SHOP_PREFIX = 'o3-shop/';

    private const COLOR_GRAY = 1;   // currently being visited (DFS stack)
    private const COLOR_BLACK = 2;  // fully processed

    private RawComposerJsonFetcher $fetcher;

    /** @var callable(string):string */
    private $refResolver;

    /** @var array<string,array<int,string>> */
    private array $edges = [];

    /** @var array<string,array<int,PinLocation>> */
    private array $pinLocations = [];

    /** @var array<string,int> */
    private array $color = [];

    /** @var array<int,string> DFS stack used to identify which package is making a back-edge */
    private array $visiting = [];

    /** @var array<int,array{from:string,to:string}> back-edges recorded during the walk */
    private array $backEdges = [];

    /**
     * @param callable(string):string $refResolver maps a package name to the git ref to fetch its composer.json from
     */
    public function __construct(RawComposerJsonFetcher $fetcher, callable $refResolver)
    {
        $this->fetcher = $fetcher;
        $this->refResolver = $refResolver;
    }

    public function walk(string $startingPackage = self::O3_SHOP_PROJECT): WalkResult
    {
        $this->edges = [];
        $this->pinLocations = [];
        $this->color = [];
        $this->visiting = [];
        $this->backEdges = [];

        $this->dfs($startingPackage);

        $topologicalOrder = $this->topologicalSort();
        $tiers = $this->computeTiers($topologicalOrder);

        return new WalkResult(
            $this->edges,
            $this->pinLocations,
            $topologicalOrder,
            $tiers,
            $this->backEdges
        );
    }

    private function dfs(string $package): void
    {
        if (isset($this->color[$package])) {
            if ($this->color[$package] === self::COLOR_GRAY) {
                // Back-edge to an ancestor in the DFS stack. This is
                // structurally common in the o3-shop network: themes,
                // tooling and test-libs declare `require: o3-shop/shop-ce`
                // even though shop-ce is the parent that pulled them in.
                // Such back-edges encode peer/compatibility constraints,
                // not topological dependencies — record the edge for
                // diagnostics (Section 8 still updates the constraint via
                // pin-locations recorded at the call site) and continue
                // without recursing.
                $from = end($this->visiting);
                if (is_string($from) && $from !== $package) {
                    $this->backEdges[] = ['from' => $from, 'to' => $package];
                }
            }
            return;
        }

        $this->color[$package] = self::COLOR_GRAY;
        $this->visiting[] = $package;
        if (!isset($this->edges[$package])) {
            $this->edges[$package] = [];
        }

        $ref = ($this->refResolver)($package);
        $manifest = $this->fetcher->fetch($package, $ref);

        foreach ([PinLocation::KEY_REQUIRE, PinLocation::KEY_REQUIRE_DEV] as $key) {
            $deps = $manifest[$key] ?? [];
            if (!is_array($deps)) {
                continue;
            }
            foreach ($deps as $depName => $constraint) {
                if (!is_string($depName) || !is_string($constraint)) {
                    continue;
                }
                if (!$this->isO3Shop($depName)) {
                    continue;
                }

                if (!in_array($depName, $this->edges[$package], true)) {
                    $this->edges[$package][] = $depName;
                }
                $this->pinLocations[$depName][] = new PinLocation($package, $key, $constraint);

                $this->dfs($depName);
            }
        }

        $this->color[$package] = self::COLOR_BLACK;
        array_pop($this->visiting);
    }

    private function isO3Shop(string $name): bool
    {
        return strncmp($name, self::O3_SHOP_PREFIX, strlen(self::O3_SHOP_PREFIX)) === 0;
    }

    /**
     * Reverse-topological order (leaves first) via post-order DFS.
     *
     * @return array<int,string>
     */
    private function topologicalSort(): array
    {
        $order = [];
        $visited = [];

        $visit = null;
        $visit = function (string $node) use (&$visit, &$order, &$visited): void {
            if (isset($visited[$node])) {
                return;
            }
            $visited[$node] = true;
            foreach ($this->edges[$node] ?? [] as $dep) {
                $visit($dep);
            }
            $order[] = $node;
        };
        foreach (array_keys($this->edges) as $node) {
            $visit($node);
        }
        return $order;
    }

    /**
     * tier(leaf) = 0; tier(node) = max(tier(dep) for dep in deps(node)) + 1.
     *
     * @param array<int,string> $topologicalOrder leaves first
     * @return array<string,int>
     */
    private function computeTiers(array $topologicalOrder): array
    {
        $tiers = [];
        foreach ($topologicalOrder as $node) {
            $maxDepTier = -1;
            foreach ($this->edges[$node] ?? [] as $dep) {
                $depTier = $tiers[$dep] ?? -1;
                if ($depTier > $maxDepTier) {
                    $maxDepTier = $depTier;
                }
            }
            $tiers[$node] = $maxDepTier + 1;
        }
        return $tiers;
    }
}
