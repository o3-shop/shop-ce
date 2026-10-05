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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Graph;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawComposerJsonFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFetchException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\DepTreeWalker;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\PinLocation;
use PHPUnit\Framework\TestCase;

class DepTreeWalkerTest extends TestCase
{
    private function walker(array $fixtures, callable $refResolver = null): DepTreeWalker
    {
        return new DepTreeWalker(
            new ManifestArrayFetcher($fixtures),
            $refResolver ?? static fn (string $pkg): string => 'main'
        );
    }

    public function testLinearChainProducesExpectedTiersAndOrder(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/shop-ce' => 'v1.6.1']],
            'o3-shop/shop-ce|main' => ['require' => ['o3-shop/smarty' => '~2.6.34']],
            'o3-shop/smarty|main'  => ['require' => []],
        ])->walk();

        $this->assertSame(0, $result->tier('o3-shop/smarty'));
        $this->assertSame(1, $result->tier('o3-shop/shop-ce'));
        $this->assertSame(2, $result->tier('o3-shop/o3-shop'));
        $this->assertSame(
            ['o3-shop/smarty', 'o3-shop/shop-ce', 'o3-shop/o3-shop'],
            $result->topologicalOrder()
        );
        // tiers() getter — bulk view that mirrors per-package tier()
        $tiers = $result->tiers();
        ksort($tiers);
        $this->assertSame([
            'o3-shop/o3-shop' => 2,
            'o3-shop/shop-ce' => 1,
            'o3-shop/smarty' => 0,
        ], $tiers);
    }

    public function testTierThrowsWhenPackageNeverVisited(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/shop-ce' => 'v1.6.1']],
            'o3-shop/shop-ce|main' => ['require' => []],
        ])->walk();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("Package 'o3-shop/never-visited' was not visited by the walker");
        $result->tier('o3-shop/never-visited');
    }

    public function testDiamondVisitsSharedLeafExactlyOnce(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => [
                'require' => [
                    'o3-shop/shop-ce' => 'v1.6.1',
                    'o3-shop/testing-library' => '^1.2.0',
                ],
            ],
            'o3-shop/shop-ce|main' => [
                'require' => ['o3-shop/shop-facts' => 'v1.0.4'],
            ],
            'o3-shop/testing-library|main' => [
                'require' => ['o3-shop/shop-facts' => '^1.0.0'],
            ],
            'o3-shop/shop-facts|main' => ['require' => []],
        ]);

        $fetcher = new ManifestArrayFetcher([
            'o3-shop/o3-shop|main' => [
                'require' => [
                    'o3-shop/shop-ce' => 'v1.6.1',
                    'o3-shop/testing-library' => '^1.2.0',
                ],
            ],
            'o3-shop/shop-ce|main' => [
                'require' => ['o3-shop/shop-facts' => 'v1.0.4'],
            ],
            'o3-shop/testing-library|main' => [
                'require' => ['o3-shop/shop-facts' => '^1.0.0'],
            ],
            'o3-shop/shop-facts|main' => ['require' => []],
        ]);
        $walker = new DepTreeWalker($fetcher, static fn (string $pkg): string => 'main');
        $result = $walker->walk();

        // shop-facts visited once
        $this->assertSame(1, count(array_filter(
            $fetcher->calls,
            static fn (string $call): bool => $call === 'o3-shop/shop-facts|main'
        )));
        // shop-facts pinned in two locations (one per parent)
        $pins = $result->pinLocations('o3-shop/shop-facts');
        $this->assertCount(2, $pins);
        $parents = array_map(static fn (PinLocation $p): string => $p->parentPackage(), $pins);
        $this->assertEqualsCanonicalizing(
            ['o3-shop/shop-ce', 'o3-shop/testing-library'],
            $parents
        );
        $this->assertSame(0, $result->tier('o3-shop/shop-facts'));
        $this->assertSame(1, $result->tier('o3-shop/shop-ce'));
        $this->assertSame(1, $result->tier('o3-shop/testing-library'));
        $this->assertSame(2, $result->tier('o3-shop/o3-shop'));
    }

    public function testRequireDevOnlyCandidateAppearsInGraph(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => [
                'require' => ['o3-shop/shop-ce' => 'v1.6.1'],
                'require-dev' => ['o3-shop/shop-ide-helper' => '^1.0.0'],
            ],
            'o3-shop/shop-ce|main' => ['require' => []],
            'o3-shop/shop-ide-helper|main' => ['require' => []],
        ])->walk();

        $this->assertContains('o3-shop/shop-ide-helper', $result->nodes());
        $pins = $result->pinLocations('o3-shop/shop-ide-helper');
        $this->assertCount(1, $pins);
        $this->assertSame(PinLocation::KEY_REQUIRE_DEV, $pins[0]->key());
        $this->assertSame('^1.0.0', $pins[0]->constraint());
    }

    public function testNonO3ShopDepsAreIgnoredDuringWalk(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => [
                'require' => [
                    'symfony/console' => 'v3.4.47',
                    'doctrine/dbal' => '2.12.1',
                    'o3-shop/shop-ce' => 'v1.6.1',
                ],
            ],
            'o3-shop/shop-ce|main' => ['require' => []],
        ])->walk();

        $this->assertSame(['o3-shop/o3-shop', 'o3-shop/shop-ce'], $result->nodes());
        $this->assertSame(['o3-shop/shop-ce'], $result->dependencies('o3-shop/o3-shop'));
    }

    public function testMissingDepFetchPropagatesAsException(): void
    {
        $walker = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/never-existed' => '*']],
            // never-existed has no fixture
        ]);
        $this->expectException(RawRepoFetchException::class);
        $walker->walk();
    }

    public function testTwoPackageBackEdgeIsRecordedNotThrown(): void
    {
        // Simulates testing-library's "require: shop-ce" while shop-ce
        // pulls testing-library via require-dev — a structural reality
        // of the o3-shop network. The walker must tolerate this and
        // record the back-edge so Section 8 can still update the
        // constraint at the back-edge pin location.
        $result = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/a' => '*']],
            'o3-shop/a|main' => ['require' => ['o3-shop/b' => '*']],
            'o3-shop/b|main' => ['require' => ['o3-shop/a' => '*']],
        ])->walk();

        $this->assertTrue($result->hasBackEdges());
        $backEdges = $result->backEdges();
        $this->assertCount(1, $backEdges);
        $this->assertSame(['from' => 'o3-shop/b', 'to' => 'o3-shop/a'], $backEdges[0]);

        // Pin location is still recorded so Step 5 (constraint update)
        // can rewrite a's constraint in b's composer.json if needed.
        $aPins = $result->pinLocations('o3-shop/a');
        $this->assertCount(2, $aPins);
        $parents = array_map(static fn (PinLocation $p): string => $p->parentPackage(), $aPins);
        $this->assertEqualsCanonicalizing(['o3-shop/o3-shop', 'o3-shop/b'], $parents);

        // Topological order still produced; back-edge does not break sort.
        $this->assertContains('o3-shop/a', $result->topologicalOrder());
        $this->assertContains('o3-shop/b', $result->topologicalOrder());
    }

    public function testThreePackageBackEdgeIsRecordedNotThrown(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/a' => '*']],
            'o3-shop/a|main' => ['require' => ['o3-shop/b' => '*']],
            'o3-shop/b|main' => ['require' => ['o3-shop/c' => '*']],
            'o3-shop/c|main' => ['require' => ['o3-shop/a' => '*']],
        ])->walk();

        $this->assertTrue($result->hasBackEdges());
        $backEdges = $result->backEdges();
        $this->assertCount(1, $backEdges);
        $this->assertSame(['from' => 'o3-shop/c', 'to' => 'o3-shop/a'], $backEdges[0]);
    }

    public function testNoBackEdgesWhenGraphIsAcyclic(): void
    {
        $result = $this->walker([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/a' => '*']],
            'o3-shop/a|main' => ['require' => ['o3-shop/b' => '*']],
            'o3-shop/b|main' => ['require' => []],
        ])->walk();

        $this->assertFalse($result->hasBackEdges());
        $this->assertSame([], $result->backEdges());
    }

    public function testRefResolverIsCalledPerPackage(): void
    {
        $callsByPackage = [];
        $resolver = function (string $pkg) use (&$callsByPackage): string {
            $callsByPackage[] = $pkg;
            return $pkg === 'o3-shop/shop-ce' ? 'b-1.6' : 'main';
        };
        $fetcher = new ManifestArrayFetcher([
            'o3-shop/o3-shop|main' => ['require' => ['o3-shop/shop-ce' => 'v1.6.1']],
            'o3-shop/shop-ce|b-1.6' => ['require' => []],
        ]);
        (new DepTreeWalker($fetcher, $resolver))->walk();

        $this->assertContains('o3-shop/o3-shop', $callsByPackage);
        $this->assertContains('o3-shop/shop-ce', $callsByPackage);
        $this->assertContains('o3-shop/o3-shop|main', $fetcher->calls);
        $this->assertContains('o3-shop/shop-ce|b-1.6', $fetcher->calls);
    }

    public function testSingleNodeWithNoO3ShopDepsHasTierZero(): void
    {
        $result = $this->walker([
            'o3-shop/leaf|main' => ['require' => ['symfony/console' => 'v3.4.47']],
        ])->walk('o3-shop/leaf');

        $this->assertSame(['o3-shop/leaf'], $result->nodes());
        $this->assertSame(0, $result->tier('o3-shop/leaf'));
        $this->assertSame(['o3-shop/leaf'], $result->topologicalOrder());
    }
}

/**
 * Test double for RawComposerJsonFetcher: returns canned manifests.
 */
final class ManifestArrayFetcher implements RawComposerJsonFetcher
{
    /** @var array<string,array<string,mixed>> */
    private array $fixtures;

    /** @var array<int,string> */
    public array $calls = [];

    /**
     * @param array<string,array<string,mixed>> $fixtures key "<package>|<ref>" => parsed composer.json
     */
    public function __construct(array $fixtures)
    {
        $this->fixtures = $fixtures;
    }

    public function fetch(string $packageName, string $ref): array
    {
        $key = $packageName . '|' . $ref;
        $this->calls[] = $key;
        if (!isset($this->fixtures[$key])) {
            throw new RawRepoFetchException("no fixture for {$key}");
        }
        return $this->fixtures[$key];
    }
}
