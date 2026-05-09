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

use OutOfBoundsException;

/**
 * Output of `DepTreeWalker::walk()`. Captures the o3-shop-only
 * dependency DAG, where each pin lives, the topological order
 * (leaves first), and the per-node tier number.
 */
final class WalkResult
{
    /** @var array<string,array<int,string>>  package => list of o3-shop deps (deduplicated, in encounter order) */
    private array $edges;

    /** @var array<string,array<int,PinLocation>>  package => list of locations where this package is pinned */
    private array $pinLocations;

    /** @var array<int,string> topologically ordered package names; leaves first */
    private array $topologicalOrder;

    /** @var array<string,int> package => tier (0 = leaf, increases up the chain) */
    private array $tiers;

    /** @var array<int,array{from:string,to:string}> back-edges to ancestor nodes encountered during DFS */
    private array $backEdges;

    /**
     * @param array<string,array<int,string>>          $edges
     * @param array<string,array<int,PinLocation>>     $pinLocations
     * @param array<int,string>                        $topologicalOrder
     * @param array<string,int>                        $tiers
     * @param array<int,array{from:string,to:string}>  $backEdges
     */
    public function __construct(
        array $edges,
        array $pinLocations,
        array $topologicalOrder,
        array $tiers,
        array $backEdges = []
    ) {
        $this->edges = $edges;
        $this->pinLocations = $pinLocations;
        $this->topologicalOrder = $topologicalOrder;
        $this->tiers = $tiers;
        $this->backEdges = $backEdges;
    }

    /** @return array<int,string> */
    public function nodes(): array
    {
        return array_keys($this->edges);
    }

    /** @return array<int,string> */
    public function dependencies(string $package): array
    {
        return $this->edges[$package] ?? [];
    }

    /** @return array<int,PinLocation> */
    public function pinLocations(string $package): array
    {
        return $this->pinLocations[$package] ?? [];
    }

    /** @return array<int,string> leaves first */
    public function topologicalOrder(): array
    {
        return $this->topologicalOrder;
    }

    public function tier(string $package): int
    {
        if (!isset($this->tiers[$package])) {
            throw new OutOfBoundsException("Package '{$package}' was not visited by the walker");
        }
        return $this->tiers[$package];
    }

    /** @return array<string,int> */
    public function tiers(): array
    {
        return $this->tiers;
    }

    /** @return array<int,array{from:string,to:string}> */
    public function backEdges(): array
    {
        return $this->backEdges;
    }

    public function hasBackEdges(): bool
    {
        return $this->backEdges !== [];
    }
}
