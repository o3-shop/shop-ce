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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Planning;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawComposerJsonFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFetchException;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\RawRepoFileFetcher;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdater;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Graph\DepTreeWalker;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesAggregator;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Notes\ReleaseNotesProvider;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlanner;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshotBuilder;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\CandidateVersionResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\RemoteRepoIntrospector;
use PHPUnit\Framework\TestCase;

class ReleasePlannerTest extends TestCase
{
    public function testPlannerProducesExpectedReleasePlanForSimpleScenario(): void
    {
        // o3-shop@v1.6.0 (pre-fold-in) → metapackage indirection →
        // tier-0 pins (shop-ce, shop-facts) → planned upgrade to v1.6.1-RC1.
        // shop-ce is the special case (uses --to verbatim);
        // shop-facts gets a default-patch bump.
        $manifests = [
            // --from snapshot (Section 4)
            'o3-shop/o3-shop|v1.6.0' => [
                'require' => ['o3-shop/shop-metapackage-ce' => 'v1.6.0'],
            ],
            'o3-shop/shop-metapackage-ce|v1.6.0' => [
                'require' => [
                    'o3-shop/shop-ce' => 'v1.6.0',
                    'o3-shop/shop-facts' => 'v1.0.4',
                ],
            ],
            // Walk on the release branch (Section 5)
            'o3-shop/o3-shop|b-1.6' => [
                'require' => [
                    'o3-shop/shop-ce' => 'v1.6.0',
                    'o3-shop/shop-facts' => 'v1.0.4',
                ],
            ],
            'o3-shop/shop-ce|b-1.6' => ['require' => []],
            'o3-shop/shop-facts|b-1.6' => ['require' => []],
        ];

        $planner = $this->wirePlanner(
            $manifests,
            tagsByPackage: [
                'o3-shop/shop-ce' => ['v1.6.0' => 'sha-shop-ce'],
                'o3-shop/shop-facts' => ['v1.0.4' => 'sha-facts'],
            ],
            branchHeads: [
                'o3-shop/shop-ce' => ['b-1.6' => 'sha-shop-ce-newer'], // commits beyond tag
                'o3-shop/shop-facts' => ['b-1.6' => 'sha-facts'],       // unchanged
            ]
        );

        $plan = $planner->plan('v1.6.0', 'v1.6.1-RC1', []);

        // From-snapshot: pre-fold-in indirection applied
        $this->assertTrue($plan->fromSnapshot()->usedPreFoldInIndirection());
        $this->assertSame('v1.6.0', $plan->fromSnapshot()->preFoldInMetapackageVersion());

        // Two candidates: shop-ce (case-3, --to verbatim) and shop-facts (case-1, unchanged)
        $candidates = $plan->candidates();
        $this->assertCount(2, $candidates);

        $byPackage = [];
        foreach ($candidates as $c) {
            $byPackage[$c->package()] = $c;
        }

        $this->assertSame('v1.6.0', $byPackage['o3-shop/shop-ce']->fromPin());
        $this->assertSame('v1.6.1-RC1', $byPackage['o3-shop/shop-ce']->chosenVersion());
        $this->assertSame('cut-new-tag', $byPackage['o3-shop/shop-ce']->caseLabel());

        $this->assertSame('v1.0.4', $byPackage['o3-shop/shop-facts']->fromPin());
        $this->assertSame('v1.0.4', $byPackage['o3-shop/shop-facts']->chosenVersion());
        $this->assertSame('unchanged', $byPackage['o3-shop/shop-facts']->caseLabel());

        // Constraint edits: only shop-ce changed (v1.6.0 -> v1.6.1-RC1)
        $edits = $plan->constraintEdits();
        $this->assertCount(1, $edits);
        $this->assertSame('o3-shop/o3-shop', $edits[0]->parentPackage());
        $this->assertSame('o3-shop/shop-ce', $edits[0]->depPackage());
        $this->assertSame('v1.6.0', $edits[0]->update()->oldConstraint());
        $this->assertSame('v1.6.1-RC1', $edits[0]->update()->newConstraint());
    }

    public function testPlannerEmitsAggregatedNotesUsingProvidedNotesProvider(): void
    {
        $manifests = [
            'o3-shop/o3-shop|v1.6.1' => [
                'require' => ['o3-shop/shop-ce' => 'v1.6.1'],
            ],
            'o3-shop/o3-shop|b-1.6' => [
                'require' => ['o3-shop/shop-ce' => 'v1.6.1'],
            ],
            'o3-shop/shop-ce|b-1.6' => ['require' => []],
        ];

        $planner = $this->wirePlanner(
            $manifests,
            tagsByPackage: [
                'o3-shop/shop-ce' => ['v1.6.1' => 'sha'],
            ],
            branchHeads: [
                'o3-shop/shop-ce' => ['b-1.6' => 'sha-newer'], // forces case 3 -> v1.6.2-RC1
            ],
            cannedNotes: [
                'o3-shop/shop-ce|v1.6.1|v1.6.2-RC1' => "## Changes\n* shop-ce update\n",
            ]
        );

        $plan = $planner->plan('v1.6.1', 'v1.6.2-RC1', []);
        $this->assertStringContainsString('## o3-shop/shop-ce', $plan->aggregatedNotes());
        $this->assertStringContainsString('shop-ce update', $plan->aggregatedNotes());
    }

    public function testPlannerSkipsPreFlightWhenNoRepoPathsSupplied(): void
    {
        $manifests = [
            'o3-shop/o3-shop|v1.6.1' => ['require' => ['o3-shop/shop-ce' => 'v1.6.1']],
            'o3-shop/o3-shop|b-1.6' => ['require' => ['o3-shop/shop-ce' => 'v1.6.1']],
            'o3-shop/shop-ce|b-1.6' => ['require' => []],
        ];
        $planner = $this->wirePlanner(
            $manifests,
            tagsByPackage: ['o3-shop/shop-ce' => ['v1.6.1' => 'sha']],
            branchHeads: ['o3-shop/shop-ce' => ['b-1.6' => 'sha']]
        );

        $plan = $planner->plan('v1.6.1', 'v1.6.1-RC1', []);

        $this->assertSame([], $plan->preFlightReports());
        $this->assertFalse($plan->shouldAbort());
    }

    public function testPlannerPropagatesFetcherFailures(): void
    {
        $planner = $this->wirePlanner(
            manifests: [
                // no fixtures at all → fetcher throws on first call
            ],
            tagsByPackage: [],
            branchHeads: []
        );
        $this->expectException(RawRepoFetchException::class);
        $planner->plan('v9.9.9', 'v10.0.0-RC1', []);
    }

    /**
     * Regression for the "^^v1.2.0" garbage constraint output. Before the
     * fix, an unchanged candidate's chosenVersion (which is the existing
     * constraint string, not a version) was passed through
     * ConstraintUpdater::update(), which detected non-satisfaction (because
     * Semver::satisfies expects a version on the left) and wrapped the
     * existing caret in another caret. The fix: the planner skips the
     * constraint-edit loop entirely for unchanged candidates.
     */
    public function testPlannerSkipsConstraintEditsForUnchangedCandidates(): void
    {
        // o3-shop's require-dev pins testing-library at "^1.2.0".
        // testing-library has v1.2.0 as latest tag, branch HEAD == tag SHA.
        // Resolution: case 1 (unchanged) → chosenVersion = fromPin = "^1.2.0".
        // No constraint edit should be emitted.
        $manifests = [
            'o3-shop/o3-shop|v1.6.1' => [
                'require-dev' => ['o3-shop/testing-library' => '^1.2.0'],
            ],
            'o3-shop/o3-shop|b-1.6' => [
                'require-dev' => ['o3-shop/testing-library' => '^1.2.0'],
            ],
            'o3-shop/testing-library|b-1.6' => ['require' => []],
        ];
        $planner = $this->wirePlanner(
            $manifests,
            tagsByPackage: [
                'o3-shop/testing-library' => ['v1.2.0' => 'sha-tagged'],
            ],
            branchHeads: [
                'o3-shop/testing-library' => ['b-1.6' => 'sha-tagged'],  // unchanged
            ]
        );

        $plan = $planner->plan('v1.6.1', 'v1.6.1-RC2', []);

        // The candidate is unchanged
        $candidates = $plan->candidates();
        $this->assertCount(1, $candidates);
        $this->assertFalse($candidates[0]->isChanged());
        $this->assertSame('^1.2.0', $candidates[0]->fromPin());
        $this->assertSame('^1.2.0', $candidates[0]->chosenVersion());

        // No constraint edits emitted — the existing "^1.2.0" stays untouched.
        $this->assertSame([], $plan->constraintEdits());
    }

    /**
     * Wires a `ReleasePlanner` from an in-memory fixture set so tests
     * never touch the network or shell out.
     *
     * @param array<string,array<string,mixed>>           $manifests        composer.json fixtures keyed by "<package>|<ref>"
     * @param array<string,array<string,string>>          $tagsByPackage    package => [tag => sha]
     * @param array<string,array<string,string>>          $branchHeads      package => [branch => sha]
     * @param array<string,string>                        $cannedNotes      "<pkg>|<previous>|<new>" => markdown body
     */
    private function wirePlanner(
        array $manifests,
        array $tagsByPackage = [],
        array $branchHeads = [],
        array $cannedNotes = []
    ): ReleasePlanner {
        $composerFetcher = new InMemoryComposerJsonFetcher($manifests);
        $fileFetcher = new InMemoryFileFetcher([]); // no .next-bump fixtures
        $branchResolver = static fn (string $pkg): string => 'b-1.6';

        $snapshotBuilder = new FromSnapshotBuilder($composerFetcher);
        $walker = new DepTreeWalker($composerFetcher, $branchResolver);
        $repos = new InMemoryRepoIntrospector($tagsByPackage, $branchHeads);
        $versionResolver = new CandidateVersionResolver($repos);
        $tagCutter = new TagCutter($fileFetcher);
        $constraintUpdater = new ConstraintUpdater();
        $notesAggregator = new ReleaseNotesAggregator(new InMemoryNotesProvider($cannedNotes));

        return new ReleasePlanner(
            $snapshotBuilder,
            $walker,
            $versionResolver,
            $tagCutter,
            $constraintUpdater,
            $notesAggregator,
            $branchResolver,
            null
        );
    }
}

final class InMemoryComposerJsonFetcher implements RawComposerJsonFetcher
{
    /** @var array<string,array<string,mixed>> */
    private array $fixtures;

    public function __construct(array $fixtures)
    {
        $this->fixtures = $fixtures;
    }

    public function fetch(string $packageName, string $ref): array
    {
        $key = $packageName . '|' . $ref;
        if (!isset($this->fixtures[$key])) {
            throw new RawRepoFetchException("no manifest fixture for {$key}");
        }
        return $this->fixtures[$key];
    }
}

final class InMemoryFileFetcher implements RawRepoFileFetcher
{
    /** @var array<string,string> */
    private array $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function fetchFile(string $packageName, string $ref, string $path): ?string
    {
        return $this->files[$packageName . '|' . $ref . '|' . $path] ?? null;
    }
}

final class InMemoryRepoIntrospector implements RemoteRepoIntrospector
{
    /** @var array<string,array<string,string>> */
    private array $tags;
    /** @var array<string,array<string,string>> */
    private array $branches;

    public function __construct(array $tags, array $branches)
    {
        $this->tags = $tags;
        $this->branches = $branches;
    }

    public function tags(string $package): array
    {
        return $this->tags[$package] ?? [];
    }

    public function refCommit(string $package, string $ref): ?string
    {
        $t = $this->tags[$package] ?? [];
        if (isset($t[$ref])) {
            return $t[$ref];
        }
        return $this->branches[$package][$ref] ?? null;
    }
}

final class InMemoryNotesProvider implements ReleaseNotesProvider
{
    /** @var array<string,string> */
    private array $bodies;

    public function __construct(array $bodies)
    {
        $this->bodies = $bodies;
    }

    public function notesFor(string $package, string $previousTag, string $newTag): string
    {
        return $this->bodies[$package . '|' . $previousTag . '|' . $newTag] ?? '';
    }
}
