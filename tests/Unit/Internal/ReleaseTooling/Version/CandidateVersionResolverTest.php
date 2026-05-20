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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Version;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\CandidateVersionResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\RemoteRepoIntrospector;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\VersionResolution;
use PHPUnit\Framework\TestCase;

class CandidateVersionResolverTest extends TestCase
{
    private function resolverWith(array $tags, ?string $branchHead = null, string $branchName = 'b-1.6'): CandidateVersionResolver
    {
        $repos = new InMemoryRepoIntrospector([
            'o3-shop/shop-facts' => [
                'tags' => $tags,
                'branches' => [$branchName => $branchHead],
            ],
        ]);
        return new CandidateVersionResolver($repos);
    }

    /* ---------- Case 1 — Unchanged-since-from ---------- */

    public function testCaseUnchangedWhenLatestTagEqualsFromPinAndBranchPointsAtThatTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-pinned'],
            'sha-pinned',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_UNCHANGED, $resolution->case());
        $this->assertSame('v1.0.4', $resolution->chosenVersion());
    }

    public function testCaseUnchangedWhenFromPinIsCaretAndLatestSatisfiesNoNewCommits(): void
    {
        $resolver = $this->resolverWith(
            ['v1.4.0' => 'sha'],
            'sha',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', '^v1.4.0', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_UNCHANGED, $resolution->case());
        $this->assertSame('^v1.4.0', $resolution->chosenVersion());
    }

    /* ---------- Case 2 — Changed-with-usable-tag ---------- */

    public function testCaseUsableTagWhenLatestTagIsNewerThanFromPinAndBranchAtThatTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-old', 'v1.0.5' => 'sha-new'],
            'sha-new',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $resolution->case());
        $this->assertSame('v1.0.5', $resolution->chosenVersion());
    }

    public function testCaseUsableTagPicksHighestSemverTagAcrossOutOfOrderListing(): void
    {
        $resolver = $this->resolverWith(
            [
                'v1.0.4' => 'sha-old',
                'v1.1.0' => 'sha-newer',
                'v1.0.5' => 'sha-mid',
            ],
            'sha-newer',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $resolution->case());
        $this->assertSame('v1.1.0', $resolution->chosenVersion());
    }

    /* ---------- Case 3 — Changed-without-usable-tag ---------- */

    public function testCaseNeedsNewTagWhenCommitsExistBeyondLatestTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-tagged'],
            'sha-newer-than-tag',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_NEEDS_NEW_TAG, $resolution->case());
        $this->assertNull($resolution->chosenVersion());
    }

    public function testCaseNeedsNewTagWhenRepoHasNoSemverTagsYet(): void
    {
        $resolver = $this->resolverWith(
            [],
            'sha-initial',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_NEEDS_NEW_TAG, $resolution->case());
        $this->assertNull($resolution->chosenVersion());
    }

    /* ---------- Stability check ---------- */

    public function testFinalShopReleaseRejectsPreReleaseDepTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-old', 'v1.1.0-RC3' => 'sha-rc'],
            'sha-rc',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.7.0', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_NEEDS_NEW_TAG, $resolution->case());
    }

    public function testRcShopReleaseAcceptsPreReleaseDepTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-old', 'v1.1.0-RC3' => 'sha-rc'],
            'sha-rc',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.7.0-RC4', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $resolution->case());
        $this->assertSame('v1.1.0-RC3', $resolution->chosenVersion());
    }

    public function testRcShopReleaseAcceptsFinalDepTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-old', 'v1.1.0' => 'sha-final'],
            'sha-final',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.7.0-RC4', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $resolution->case());
        $this->assertSame('v1.1.0', $resolution->chosenVersion());
    }

    public function testFinalShopReleaseAcceptsFinalDepTag(): void
    {
        $resolver = $this->resolverWith(
            ['v1.0.4' => 'sha-old', 'v1.1.0' => 'sha-final'],
            'sha-final',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.7.0', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_USABLE_TAG, $resolution->case());
        $this->assertSame('v1.1.0', $resolution->chosenVersion());
    }

    /* ---------- Stability helper unit ---------- */

    public function testIsPreReleaseDetectsRcAlphaBeta(): void
    {
        $this->assertTrue(CandidateVersionResolver::isPreRelease('v1.0.0-RC1'));
        $this->assertTrue(CandidateVersionResolver::isPreRelease('v1.0.0-alpha.1'));
        $this->assertTrue(CandidateVersionResolver::isPreRelease('v1.0.0-beta.2'));
        $this->assertTrue(CandidateVersionResolver::isPreRelease('v1.0.0-rc.3'));
    }

    public function testIsPreReleaseRejectsFinalTags(): void
    {
        $this->assertFalse(CandidateVersionResolver::isPreRelease('v1.0.0'));
        $this->assertFalse(CandidateVersionResolver::isPreRelease('v1.6.1'));
        $this->assertFalse(CandidateVersionResolver::isPreRelease('v0.0.1'));
    }

    public function testStabilityCompatibleHonorsRcAndFinalContracts(): void
    {
        // Final shop rejects pre-release dep
        $this->assertFalse(
            CandidateVersionResolver::stabilityCompatible('v1.0.0-RC1', 'v1.6.1')
        );
        // Final shop accepts final dep
        $this->assertTrue(
            CandidateVersionResolver::stabilityCompatible('v1.0.0', 'v1.6.1')
        );
        // RC shop accepts either
        $this->assertTrue(
            CandidateVersionResolver::stabilityCompatible('v1.0.0-RC1', 'v1.6.1-RC1')
        );
        $this->assertTrue(
            CandidateVersionResolver::stabilityCompatible('v1.0.0', 'v1.6.1-RC1')
        );
    }

    /* ---------- Tag-list filtering ---------- */

    public function testNonSemverTagsAreSkippedFromHighestSelection(): void
    {
        // Repos sometimes have non-semver tags (e.g. CI markers, lightweight tags).
        // The walker should pick the highest *semver-shaped* tag and ignore the rest.
        $resolver = $this->resolverWith(
            [
                'release-2025-01' => 'sha-noise',
                'v1.0.4' => 'sha-pinned',
                'tip' => 'sha-noise2',
            ],
            'sha-pinned',
        );
        $resolution = $resolver->resolve('o3-shop/shop-facts', 'v1.0.4', 'v1.6.1-RC1', 'b-1.6');

        $this->assertSame(VersionResolution::CASE_UNCHANGED, $resolution->case());
        $this->assertSame('v1.0.4', $resolution->chosenVersion());
    }
}

/**
 * In-memory test double for RemoteRepoIntrospector.
 *
 * Fixture shape:
 *   [
 *     'o3-shop/shop-facts' => [
 *       'tags'     => ['v1.0.4' => 'sha-a', 'v1.0.5' => 'sha-b'],
 *       'branches' => ['b-1.6' => 'sha-b'],
 *     ],
 *   ]
 */
final class InMemoryRepoIntrospector implements RemoteRepoIntrospector
{
    /** @var array<string,array{tags:array<string,string>,branches:array<string,string|null>}> */
    private array $fixtures;

    public function __construct(array $fixtures)
    {
        $this->fixtures = $fixtures;
    }

    public function tags(string $package): array
    {
        return $this->fixtures[$package]['tags'] ?? [];
    }

    public function refCommit(string $package, string $ref): ?string
    {
        $tags = $this->fixtures[$package]['tags'] ?? [];
        if (isset($tags[$ref])) {
            return $tags[$ref];
        }
        return $this->fixtures[$package]['branches'][$ref] ?? null;
    }
}
